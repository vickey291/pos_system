<?php include "db.php"; ?>
<!DOCTYPE html>
<html>
<head>
<title>Invoices</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="card p-4 shadow-sm">
        <h2 class="text-center mb-4" style="color:#6a11cb;">All Invoices</h2>

        <div class="text-end mb-3">
            <a href="create_invoice.php" class="btn btn-success">+ New Invoice</a>
        </div>

        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-3"><input type="date" name="from" class="form-control" placeholder="From"></div>
            <div class="col-md-3"><input type="date" name="to" class="form-control" placeholder="To"></div>
            <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Product / Customer"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">Filter</button></div>
        </form>

        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr><th>ID</th><th>Customer</th><th>Product</th><th>Qty</th><th>Total</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php
            $where="1";
            if(!empty($_GET['from']) && !empty($_GET['to'])){
                $from=$_GET['from']; $to=$_GET['to']; $where.=" AND s.date BETWEEN '$from' AND '$to'";
            }
            if(!empty($_GET['search'])){
                $s=$_GET['search']; $where.=" AND (p.name LIKE '%$s%' OR c.name LIKE '%$s%')";
            }
            $res=mysqli_query($conn,"SELECT s.id,s.quantity,s.total,s.date,p.name AS product_name,c.name AS customer_name FROM sales s JOIN products p ON s.product_id=p.id JOIN customers c ON s.customer_id=c.id WHERE $where ORDER BY s.date DESC");
            while($r=mysqli_fetch_assoc($res)){
                echo "<tr>
                    <td>{$r['id']}</td>
                    <td>{$r['customer_name']}</td>
                    <td>{$r['product_name']}</td>
                    <td>{$r['quantity']}</td>
                    <td>{$r['total']}</td>
                    <td>{$r['date']}</td>
                    <td><a href='print_invoice.php?id={$r['id']}' target='_blank' class='btn btn-sm btn-primary'>Print</a></td>
                </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
