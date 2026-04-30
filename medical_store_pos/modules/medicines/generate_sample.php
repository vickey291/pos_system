<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="medicines_sample.csv"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, ['Medicine Code', 'Medicine Name', 'Company', 'Batch Number', 'Expiry Date', 'Purchase Price', 'Sale Price', 'Stock Quantity', 'Min Stock Level']);

// Sample data
$samples = [
    ['MED001', 'Paracetamol 500mg', 'GSK', 'BATCH001', '2025-12-31', '5.00', '8.00', '100', '10'],
    ['MED002', 'Amoxicillin 250mg', 'Pfizer', 'BATCH002', '2025-10-31', '15.00', '25.00', '50', '10'],
    ['MED003', 'Ibuprofen 200mg', 'Johnson & Johnson', 'BATCH003', '2026-01-15', '8.00', '12.00', '75', '15'],
    ['MED004', 'Cetirizine 10mg', 'Novartis', 'BATCH004', '2025-11-30', '3.00', '6.00', '200', '20'],
    ['MED005', 'Omeprazole 20mg', 'AstraZeneca', 'BATCH005', '2026-02-28', '10.00', '18.00', '60', '10'],
    ['MED006', 'Aspirin 75mg', 'Bayer', 'BATCH006', '2025-09-15', '4.00', '7.00', '150', '15'],
    ['MED007', 'Azithromycin 500mg', 'Pfizer', 'BATCH007', '2025-08-20', '25.00', '40.00', '30', '5'],
    ['MED008', 'Ciprofloxacin 500mg', 'Bayer', 'BATCH008', '2025-12-10', '12.00', '20.00', '45', '10'],
    ['MED009', 'Diclofenac 50mg', 'Novartis', 'BATCH009', '2026-03-05', '6.00', '10.00', '80', '10'],
    ['MED010', 'Loratadine 10mg', 'GSK', 'BATCH010', '2025-11-25', '2.50', '5.00', '200', '20'],
];

foreach($samples as $sample) {
    fputcsv($output, $sample);
}

fclose($output);
exit();
?>