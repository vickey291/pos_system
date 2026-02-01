<?php 
include "db.php"; 

// Getting product ID from URL
$id = $_GET['id'];

// Fetching product data
$result = mysqli_query($conn, "SELECT * FROM products WHERE id=$id");
$data = mysqli_fetch_assoc($result);

// Update Product
if(isset($_POST['update'])){
    $name   = $_POST['name'];
    $price  = $_POST['price'];
    $qty    = $_POST['quantity'];

    mysqli_query($conn, "UPDATE products SET name='$name', price='$price', quantity='$qty' WHERE id=$id");

    header("Location: view_products.php?updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            height: 100vh;
        }
        .container {
            margin-top: 80px;
            max-width: 500px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }
        .btn-save {
            background: #764ba2;
            color: #fff;
        }
        .btn-save:hover {
            background: #667eea;
            color: #fff;
        }
    </style>

</head>
<body>

<div class="container">
    <div class="card p-4">
        <h2 class="text-center mb-4" style="color:white;">Edit Product</h2>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-white">Product Name:</label>
                <input type="text" name="name" class="form-control" value="<?= $data['name'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-white">Product Price:</label>
                <input type="number" name="price" class="form-control" value="<?= $data['price'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-white">Quantity:</label>
                <input type="number" name="quantity" class="form-control" value="<?= $data['quantity'] ?>" required>
            </div>

            <button type="submit" name="update" class="btn btn-save w-100">Update Product</button>
        </form>

    </div>
</div>

</body>
</html>
