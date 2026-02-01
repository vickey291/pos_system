<?php include "db.php"; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Customer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:500px;">
    <div class="card p-4 shadow-sm">
        <h2 class="text-center mb-4">Add Customer</h2>
        <form method="POST">
            <div class="mb-3">
                <label>Name:</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Phone:</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <button type="submit" name="add" class="btn btn-primary w-100">Add Customer</button>
        </form>

        <?php
        if(isset($_POST['add'])){
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            if(mysqli_query($conn,"INSERT INTO customers(name,phone) VALUES('$name','$phone')")){
                echo "<p class='text-success mt-2 text-center'>✔ Customer Added Successfully!</p>";
            } else {
                echo "<p class='text-danger mt-2 text-center'>Error: ".mysqli_error($conn)."</p>";
            }
        }
        ?>
    </div>
</div>
</body>
</html>
