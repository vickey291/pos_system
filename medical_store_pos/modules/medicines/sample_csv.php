<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="medicines_sample_template.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
$headers = [
    'Medicine Code',
    'Medicine Name', 
    'Company',
    'Batch Number',
    'Expiry Date',
    'Purchase Price',
    'Sale Price',
    'Stock Quantity',
    'Min Stock Level'
];
fputcsv($output, $headers);

// Sample data rows
$sample_data = [
    [
        'MED001',
        'Paracetamol 500mg',
        'GSK',
        'BATCH001',
        '2025-12-31',
        '5.00',
        '8.00',
        '100',
        '10'
    ],
    [
        'MED002',
        'Amoxicillin 250mg',
        'Pfizer',
        'BATCH002',
        '2025-10-31',
        '15.00',
        '25.00',
        '50',
        '10'
    ],
    [
        'MED003',
        'Ibuprofen 200mg',
        'Johnson & Johnson',
        'BATCH003',
        '2026-01-15',
        '8.00',
        '12.00',
        '75',
        '15'
    ],
    [
        'MED004',
        'Cetirizine 10mg',
        'Novartis',
        'BATCH004',
        '2025-11-30',
        '3.00',
        '6.00',
        '200',
        '20'
    ],
    [
        'MED005',
        'Omeprazole 20mg',
        'AstraZeneca',
        'BATCH005',
        '2026-02-28',
        '10.00',
        '18.00',
        '60',
        '10'
    ],
    [
        'MED006',
        'Aspirin 75mg',
        'Bayer',
        'BATCH006',
        '2025-09-15',
        '4.00',
        '7.00',
        '150',
        '15'
    ],
    [
        'MED007',
        'Azithromycin 500mg',
        'Pfizer',
        'BATCH007',
        '2025-08-20',
        '25.00',
        '40.00',
        '30',
        '5'
    ],
    [
        'MED008',
        'Ciprofloxacin 500mg',
        'Bayer',
        'BATCH008',
        '2025-12-10',
        '12.00',
        '20.00',
        '45',
        '10'
    ],
    [
        'MED009',
        'Diclofenac 50mg',
        'Novartis',
        'BATCH009',
        '2026-03-05',
        '6.00',
        '10.00',
        '80',
        '10'
    ],
    [
        'MED010',
        'Loratadine 10mg',
        'GSK',
        'BATCH010',
        '2025-11-25',
        '2.50',
        '5.00',
        '200',
        '20'
    ]
];

// Write sample data to CSV
foreach($sample_data as $row) {
    fputcsv($output, $row);
}

// Add instruction comments at the end (as CSV rows)
fputcsv($output, []);
fputcsv($output, ['# INSTRUCTIONS:']);
fputcsv($output, ['# 1. Do not change the column headers (first row)']);
fputcsv($output, ['# 2. Medicine Code must be unique']);
fputcsv($output, ['# 3. Expiry Date format: YYYY-MM-DD']);
fputcsv($output, ['# 4. All prices should be numbers without currency symbols']);
fputcsv($output, ['# 5. Delete the sample rows and add your own data']);
fputcsv($output, ['# 6. Save the file as CSV (Comma delimited)']);

// Close the output stream
fclose($output);
exit();
?>