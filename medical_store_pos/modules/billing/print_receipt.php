<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$invoice_number = isset($_GET['invoice']) ? $_GET['invoice'] : '';

$database = new Database();
$db = $database->getConnection();

// Get sale details
$query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, u.full_name as cashier 
          FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          LEFT JOIN users u ON s.created_by = u.id 
          WHERE s.invoice_number = :invoice";
$stmt = $db->prepare($query);
$stmt->bindParam(':invoice', $invoice_number);
$stmt->execute();
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$sale) {
    die('Invoice not found');
}

// Get sale items
$query = "SELECT si.*, m.name, m.medicine_code 
          FROM sale_items si 
          JOIN medicines m ON si.medicine_id = m.id 
          WHERE si.sale_id = :sale_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':sale_id', $sale['id']);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $invoice_number; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', 'Segoe UI', monospace;
            background: #f0f0f0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .receipt-container {
            max-width: 350px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #333;
        }
        
        .receipt-header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .receipt-header h3 {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .receipt-header p {
            font-size: 10px;
            color: #666;
            margin: 2px 0;
        }
        
        .receipt-info {
            margin-bottom: 15px;
            font-size: 10px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        
        .receipt-info .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .receipt-info .label {
            font-weight: bold;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
        }
        
        .items-table th,
        .items-table td {
            padding: 5px 0;
            text-align: left;
            border-bottom: 1px dotted #ccc;
        }
        
        .items-table th {
            font-weight: bold;
            border-bottom: 1px solid #333;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .totals {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #333;
        }
        
        .totals .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .totals .grand-total {
            font-size: 14px;
            font-weight: bold;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #333;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px dashed #ccc;
            padding-top: 15px;
        }
        
        .footer p {
            margin: 3px 0;
        }
        
        .thankyou {
            text-align: center;
            margin: 15px 0;
            font-size: 12px;
            font-weight: bold;
            color: #2ecc71;
        }
        
        .button-container {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .print-btn, .close-btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: inherit;
        }
        
        .print-btn {
            background: #3498db;
            color: white;
        }
        
        .print-btn:hover {
            background: #2980b9;
        }
        
        .close-btn {
            background: #95a5a6;
            color: white;
        }
        
        .close-btn:hover {
            background: #7f8c8d;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                box-shadow: none;
                padding: 10px;
                max-width: 100%;
            }
            .button-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>🏥 MEDICAL STORE</h1>
            <h3>Your Health Our Priority</h3>
            <p>123 Main Street, City, Country</p>
            <p>📞 +92 123 4567890 | ✉ info@medicalstore.com</p>
            <p>GST: XXXXXXXX | License: XXXXXXXX</p>
        </div>
        
        <div class="receipt-info">
            <div class="row">
                <span class="label">Invoice No:</span>
                <span><?php echo $sale['invoice_number']; ?></span>
            </div>
            <div class="row">
                <span class="label">Date:</span>
                <span><?php echo date('d-m-Y h:i A', strtotime($sale['sale_date'])); ?></span>
            </div>
            <div class="row">
                <span class="label">Cashier:</span>
                <span><?php echo $sale['cashier']; ?></span>
            </div>
            <div class="row">
                <span class="label">Customer:</span>
                <span><?php echo $sale['customer_name'] ?? 'Walk-in Customer'; ?></span>
            </div>
            <?php if($sale['customer_phone']): ?>
            <div class="row">
                <span class="label">Phone:</span>
                <span><?php echo $sale['customer_phone']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars(substr($item['name'], 0, 25)); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <div class="row">
                <span>Subtotal:</span>
                <span>₨ <?php echo number_format($sale['subtotal'], 2); ?></span>
            </div>
            <div class="row">
                <span>Discount:</span>
                <span>- ₨ <?php echo number_format($sale['discount'], 2); ?></span>
            </div>
            <div class="row grand-total">
                <span><strong>TOTAL:</strong></span>
                <span><strong>₨ <?php echo number_format($sale['total_amount'], 2); ?></strong></span>
            </div>
            <div class="row">
                <span>Paid:</span>
                <span>₨ <?php echo number_format($sale['paid_amount'], 2); ?></span>
            </div>
            <div class="row">
                <span>Change:</span>
                <span>₨ <?php echo number_format($sale['change_amount'], 2); ?></span>
            </div>
            <div class="row">
                <span>Payment:</span>
                <span><?php echo ucfirst($sale['payment_method']); ?></span>
            </div>
        </div>
        
        <div class="thankyou">
            ⭐ THANK YOU FOR SHOPPING ⭐
        </div>
        
        <div class="footer">
            <p>** Goods once sold will not be taken back **</p>
            <p>** Please check your items before leaving **</p>
            <p>** For complaints, contact store manager **</p>
            <p>📱 Follow us on social media for offers</p>
            <p style="margin-top: 5px;">✨ Visit Again! ✨</p>
        </div>
    </div>
    
    <div class="button-container">
        <button class="print-btn" onclick="window.print()">
            🖨️ Print Receipt
        </button>
        <button class="close-btn" onclick="window.close()">
            ✖ Close
        </button>
    </div>
    
    <script>
        // Auto print on load
        window.onload = function() {
            // Auto print after 1 second (optional)
            setTimeout(function() {
                if(confirm('Print receipt now?')) {
                    window.print();
                }
            }, 500);
        };
    </script>
</body>
</html>