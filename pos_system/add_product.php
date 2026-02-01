<?php include "db.php"; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>

    <!-- Bootstrap CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            height: 100vh;
        }
        .container {
            margin-top: 80px;
            max-width: 500px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.3);
        }
        .btn-custom {
            background: #6a11cb;
            color: #fff;
        }
        .btn-custom:hover {
            background: #2575fc;
            color: white;
        }
    </style>

</head>
<body>

<div class="container">
    <div class="card p-4">
        <h2 class="text-center mb-4" style="color:#6a11cb; font-weight:bold;">Add New Product</h2>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Product Name:</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Product Price:</label>
                <input type="number" name="price" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Quantity:</label>
                <input type="number" name="quantity" class="form-control" required>
            </div>

            <button type="submit" name="add" class="btn btn-custom w-100">Add Product</button>
        </form>

        <div class="mt-3">
        <?php
        if(isset($_POST['add'])){
            $name = $_POST['name'];
            $price = $_POST['price'];
            $qty = $_POST['quantity'];

            $sql = "INSERT INTO products(name, price, quantity) VALUES('$name', '$price', '$qty')";
            
            if(mysqli_query($conn, $sql)){
                echo "<p class='text-success text-center mt-2'>✔ Product Added Successfully!</p>";
            } else {
                echo "<p class='text-danger text-center mt-2'>Error: " . mysqli_error($conn) . "</p>";
            }
        }
        ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
