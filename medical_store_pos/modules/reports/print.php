<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$database = new Database();
$db = $database->getConnection();

$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

if($report_type == 'daily') {
    $query = "SELECT s.*, COUNT(si.id) as item_count, u.full_name as cashier 
              FROM sales s 
              LEFT JOIN sale_items si ON s.id = si.sale_id 
              LEFT JOIN users u ON s.created_by = u.id 
              WHERE DATE(s.sale_date) = :date 
              GROUP BY s.id 
              ORDER BY s.sale_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':date', $start_date);
} else {
    $query = "SELECT s.*, COUNT(si.id) as item_count, u.full_name as cashier 
              FROM sales s 
              LEFT JOIN sale_items si ON s.id = si.sale_id 
              LEFT JOIN users u ON s.created_by = u.id 
              WHERE DATE(s.sale_date) BETWEEN :start AND :end 
              GROUP BY s.id 
              ORDER BY s.sale_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start', $start_date);
    $stmt->bindParam(':end', $end_date);
}

$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_sales = array_sum(array_column($sales, 'total_amount'));
$total_items = array_sum(array_column($sales, 'item_count'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin-bottom: 10px;
        }
        .report-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            min-width: 150px;
        }
        .stat-box .number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #1e293b;
            color: white;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
        .print-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Medical Store - Sales Report</h1>
            <p><?php echo $report_type == 'daily' ? 'Daily Sales Report' : 'Date Range Sales Report'; ?></p>
            <p>Period: <?php echo date('d-m-Y', strtotime($start_date)); ?> to <?php echo date('d-m-Y', strtotime($end_date)); ?></p>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <div class="number">₨ <?php echo number_format($total_sales, 2); ?></div>
                <div>Total Sales</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo count($sales); ?></div>
                <div>Total Invoices</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $total_items; ?></div>
                <div>Items Sold</div>
            </div>
            <div class="stat-box">
                <div class="number">₨ <?php echo count($sales) > 0 ? number_format($total_sales / count($sales), 2) : '0.00'; ?></div>
                <div>Average Bill</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date & Time</th>
                    <th>Cashier</th>
                    <th>Items</th>
                    <th>Discount</th>
                    <th>Total</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sales as $sale): ?>
                <tr>
                    <td><?php echo $sale['invoice_number']; ?></td>
                    <td><?php echo date('d-m-Y h:i A', strtotime($sale['sale_date'])); ?></td>
                    <td><?php echo $sale['cashier']; ?></td>
                    <td><?php echo $sale['item_count']; ?></td>
                    <td>₨ <?php echo number_format($sale['discount'], 2); ?></td>
                    <td>₨ <?php echo number_format($sale['total_amount'], 2); ?></td>
                    <td><?php echo ucfirst($sale['payment_method']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>This is a computer generated report. Generated on: <?php echo date('d-m-Y H:i:s'); ?></p>
            <p>Medical Store - All Rights Reserved</p>
        </div>
    </div>
    <div class="no-print" style="text-align: center;">
        <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
        <button class="print-btn" onclick="window.close()">✖ Close</button>
    </div>
    <script>
        setTimeout(function() {
            window.print();
        }, 500);
    </script>
</body>
</html>