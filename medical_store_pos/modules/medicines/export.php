<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM medicines ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="medicines_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Code', 'Name', 'Company', 'Batch Number', 'Expiry Date', 'Purchase Price', 'Sale Price', 'Stock', 'Min Stock Level']);

foreach($medicines as $medicine) {
    fputcsv($output, [
        $medicine['medicine_code'],
        $medicine['name'],
        $medicine['company'],
        $medicine['batch_number'],
        $medicine['expiry_date'],
        $medicine['purchase_price'],
        $medicine['sale_price'],
        $medicine['stock_quantity'],
        $medicine['min_stock_level']
    ]);
}

fclose($output);
exit();
?>