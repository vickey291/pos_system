<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
include "db.php";

// Fetch Summary Data
$total_products = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM products"))['total'];
$total_customers = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM customers"))['total'];
$total_invoices = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM sales"))['total'];
$total_sales = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(total) AS total FROM sales"))['total'];

// Monthly Sales Data (Last 6 months)
$sales_labels = [];
$sales_data = [];

for($i=5; $i>=0; $i--){
    $month = date('Y-m', strtotime("-$i month"));
    $sales_labels[] = date('M Y', strtotime($month));
    $res = mysqli_query($conn, "SELECT SUM(total) AS total FROM sales WHERE DATE_FORMAT(date,'%Y-%m')='$month'");
    $total_month = mysqli_fetch_assoc($res)['total'];
    $sales_data[] = $total_month ? $total_month : 0;
}

// Product Stock Data
$product_labels = [];
$product_stock = [];
$res = mysqli_query($conn,"SELECT name, quantity FROM products");
while($row = mysqli_fetch_assoc($res)){
    $product_labels[] = $row['name'];
    $product_stock[] = $row['quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard - POS System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background:#f1f4f9; }
.card { border-radius:15px; box-shadow:0 0 12px rgba(0,0,0,0.2); margin-bottom:20px; }
.navbar { background:#6a11cb; color:white; }
.navbar a { color:white; margin-right:15px; text-decoration:none; }
.alert { font-weight:600; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar p-3">
    <span class="fs-4">POS Dashboard</span>
    <a href="create_invoice.php">New Invoice</a>
    <a href="view_products.php">Add Products</a>
    <a href="add_customer.php">Add Customers</a>
    <a href="invoices.php">view Invoices</a>
    <a href="logout.php" class="float-end">Logout</a>
</nav>


<!-- Summary Cards -->
<div class="container mt-4">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card p-3 text-center bg-primary text-white">
                <h5>Total Products</h5>
                <h2><?= $total_products ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center bg-success text-white">
                <h5>Total Customers</h5>
                <h2><?= $total_customers ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center bg-warning text-dark">
                <h5>Total Invoices</h5>
                <h2><?= $total_invoices ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center bg-danger text-white">
                <h5>Total Sales</h5>
                <h2><?= number_format($total_sales,2) ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="container mt-5">
    <div class="row">
        <div class="col-md-6">
            <div class="card p-3">
                <h5 class="text-center">Monthly Sales</h5>
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3">
                <h5 class="text-center">Product Stock</h5>
                <canvas id="stockChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($sales_labels) ?>,
        datasets: [{
            label: 'Sales in PKR',
            data: <?= json_encode($sales_data) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive:true }
});

const stockCtx = document.getElementById('stockChart').getContext('2d');
const stockChart = new Chart(stockCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($product_labels) ?>,
        datasets: [{
            label: 'Stock Quantity',
            data: <?= json_encode($product_stock) ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.7)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 1
        }]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
});
</script>

<!-- Recent Invoices Table -->
<div class="container mt-5">
    <div class="card p-3">
        <h5>Recent Invoices</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = mysqli_query($conn,"SELECT s.id, c.name AS customer, p.name AS product, s.quantity, s.total, s.date 
                                        FROM sales s 
                                        JOIN customers c ON s.customer_id=c.id
                                        JOIN products p ON s.product_id=p.id
                                        ORDER BY s.date DESC LIMIT 5");
            while($row = mysqli_fetch_assoc($res)){
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['customer']}</td>
                        <td>{$row['product']}</td>
                        <td>{$row['quantity']}</td>
                        <td>{$row['total']}</td>
                        <td>{$row['date']}</td>
                      </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Low Stock Alerts -->
<div class="container mt-4">
    <div class="card p-3">
        <h5>Low Stock Alerts</h5>
        <?php
        $res = mysqli_query($conn,"SELECT name, quantity FROM products WHERE quantity < 5");
        if(mysqli_num_rows($res) > 0){
            while($row = mysqli_fetch_assoc($res)){
                echo "<div class='alert alert-danger'>{$row['name']} - Only {$row['quantity']} left in stock!</div>";
            }
        } else {
            echo "<div class='alert alert-success'>No low stock products.</div>";
        }
        ?>
    </div>
</div>

</body>
</html>
