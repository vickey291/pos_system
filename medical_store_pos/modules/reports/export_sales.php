<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT s.invoice_number, s.sale_date, s.subtotal, s.discount, s.total_amount, 
                 s.paid_amount, s.change_amount, s.payment_method, u.full_name as cashier,
                 c.name as customer_name
          FROM sales s 
          LEFT JOIN users u ON s.created_by = u.id 
          LEFT JOIN customers c ON s.customer_id = c.id 
          ORDER BY s.sale_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Invoice #', 'Date', 'Customer', 'Cashier', 'Subtotal', 'Discount', 'Total', 'Paid', 'Change', 'Payment Method']);

foreach($sales as $sale) {
    fputcsv($output, [
        $sale['invoice_number'],
        date('d-m-Y H:i:s', strtotime($sale['sale_date'])),
        $sale['customer_name'] ?? 'Walk-in Customer',
        $sale['cashier'],
        $sale['subtotal'],
        $sale['discount'],
        $sale['total_amount'],
        $sale['paid_amount'],
        $sale['change_amount'],
        $sale['payment_method']
    ]);
}

fclose($output);
exit();
?>