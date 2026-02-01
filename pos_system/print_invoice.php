<?php
include "db.php";
$id=$_GET['id'];
$res=mysqli_query($conn,"SELECT s.id AS sale_id, p.name AS product_name, s.quantity, s.total, s.date, c.name AS customer_name
    FROM sales s
    JOIN products p ON s.product_id=p.id
    JOIN customers c ON s.customer_id=c.id
    WHERE s.id=$id");
$items=[]; $grand=0; $invoice_date='';
while($row=mysqli_fetch_assoc($res)){
    $items[]=$row; $grand+=$row['total']; $invoice_date=$row['date']; $customer=$row['customer_name'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Invoice #<?= $id ?></title>
<style>
body{font-family:Arial;padding:30px;}
.invoice-box{max-width:700px;margin:auto;padding:30px;border:1px solid #eee;}
h1{text-align:center;color:#333;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
table,th,td{border:1px solid #ddd;padding:8px;}
th{background:#333;color:#fff;}
.total{text-align:right;font-weight:bold;}
.btn-print{padding:10px 20px;background:#007bff;color:white;border:none;cursor:pointer;margin-top:20px;}
.btn-print:hover{background:#0056b3;}
</style>
</head>
<body>
<div class="invoice-box">
<h1>Invoice #<?= $id ?></h1>
<p><strong>Customer:</strong> <?= $customer ?></p>
<p><strong>Date:</strong> <?= $invoice_date ?></p>
<table>
<thead><tr><th>#</th><th>Product</th><th>Quantity</th><th>Total</th></tr></thead>
<tbody>
<?php foreach($items as $i=>$it): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= $it['product_name'] ?></td>
<td><?= $it['quantity'] ?></td>
<td><?= $it['total'] ?></td>
</tr>
<?php endforeach; ?>
<tr><td colspan="3" class="total">Grand Total</td><td class="total"><?= $grand ?></td></tr>
</tbody>
</table>
<button class="btn-print" onclick="window.print()">🖨️ Print Invoice</button>
</div>
</body>
</html>
