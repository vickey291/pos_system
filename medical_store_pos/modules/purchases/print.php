<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$id = isset($_GET['id']) ? $_GET['id'] : 0;

$database = new Database();
$db = $database->getConnection();

$query = "SELECT p.*, s.name as supplier_name, s.phone, s.email, s.address 
          FROM purchases p 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$purchase) {
    die('Purchase not found');
}

$query = "SELECT pi.*, m.name, m.medicine_code 
          FROM purchase_items pi 
          JOIN medicines m ON pi.medicine_id = m.id 
          WHERE pi.purchase_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - <?php echo $purchase['invoice_number']; ?></title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .info { margin-bottom: 20px; }
        .info-row { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .total { text-align: right; font-size: 16px; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; }
        @media print { .no-print { display: none; } }
        .print-btn { margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>PURCHASE ORDER</h2>
            <p>Medical Store - Purchase Order</p>
        </div>
        
        <div class="info">
            <div class="info-row"><strong>Invoice No:</strong> <?php echo $purchase['invoice_number']; ?></div>
            <div class="info-row"><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($purchase['purchase_date'])); ?></div>
            <div class="info-row"><strong>Supplier:</strong> <?php echo htmlspecialchars($purchase['supplier_name']); ?></div>
            <div class="info-row"><strong>Phone:</strong> <?php echo $purchase['phone']; ?></div>
        </div>
        
        <table>
            <thead>
                <tr><th>Item</th><th>Code</th><th>Qty</th><th>Price</th><th>Total</th></tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['medicine_code']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₨ <?php echo number_format($item['purchase_price'], 2); ?></td>
                    <td>₨ <?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4" style="text-align: right;"><strong>Total:</strong></td><td>₨ <?php echo number_format($purchase['total_amount'], 2); ?></td></tr>
            </tfoot>
        </table>
        
        <div class="footer">
            <p>Thank you for your business!</p>
        </div>
    </div>
    <div class="no-print" style="text-align: center;">
        <button class="print-btn" onclick="window.print()">Print</button>
        <button class="print-btn" onclick="window.close()">Close</button>
    </div>
    <script>setTimeout(function() { window.print(); }, 500);</script>
</body>
</html>