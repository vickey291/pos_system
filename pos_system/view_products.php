<?php include "db.php"; ?>
<!DOCTYPE html>
<html>
<head>
    <title>View Products</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="card p-4 shadow-sm">
        <h2 class="text-center mb-4" style="color:#6a11cb;">All Products</h2>
        <div class="text-end mb-3">
            <a href="add_product.php" class="btn btn-success">+ Add Product</a>
        </div>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th><th>Name</th><th>Price</th><th>Qty</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = mysqli_query($conn,"SELECT * FROM products");
            while($row=mysqli_fetch_assoc($res)){
                echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['name']}</td>
                <td>{$row['price']}</td>
                <td>{$row['quantity']}</td>
                <td>
                <a href='edit_product.php?id={$row['id']}' class='btn btn-sm btn-warning'>Edit</a>
                <a href='delete_product.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete?\")'>Delete</a>
                </td>
                </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
