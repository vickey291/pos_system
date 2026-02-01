<?php
session_start();
include "db.php";

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $res = mysqli_query($conn,"SELECT * FROM users WHERE username='$username'");
    if(mysqli_num_rows($res) > 0){
        $user = mysqli_fetch_assoc($res);
        if($password == $user['password']){
            $_SESSION['user'] = $user['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect Password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login - POS System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
body { background:#f1f4f9; display:flex; align-items:center; justify-content:center; height:100vh; }
.card { padding:30px; border-radius:15px; box-shadow:0 0 15px rgba(0,0,0,0.2); width:400px; }
.btn-login { background:#28a745; color:white; }
.btn-login:hover { background:#218838; }
</style>
</head>
<body>
<div class="card">
    <h2 class="text-center mb-4">POS System Login</h2>
    <?php if(isset($error)){ echo "<div class='alert alert-danger'>$error</div>"; } ?>
    <form method="POST">
        <div class="mb-3">
            <label>Username:</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password:</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" name="login" class="btn btn-login w-100">Login</button>
    </form>
</div>
</body>
</html>
