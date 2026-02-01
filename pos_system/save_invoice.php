<?php
include "db.php";
$customer_id=$_POST['customer_id'];
$products=$_POST['product_id'];
$prices=$_POST['price'];
$qtys=$_POST['quantity'];
$totals=$_POST['total'];

for($i=0;$i<count($products);$i++){
    $pid=$products[$i];
    $qty=$qtys[$i];
    $total=$totals[$i];
    mysqli_query($conn,"INSERT INTO sales(customer_id,product_id,quantity,total) VALUES('$customer_id','$pid','$qty','$total')");
    mysqli_query($conn,"UPDATE products SET quantity=quantity-$qty WHERE id=$pid");
}

header("Location:create_invoice.php?success=1");
exit();
?>
