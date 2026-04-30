<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT id, medicine_code, name, sale_price, stock_quantity 
          FROM medicines 
          WHERE (name LIKE :search OR medicine_code LIKE :search) 
          AND stock_quantity > 0 
          ORDER BY name 
          LIMIT 10";

$stmt = $db->prepare($query);
$searchTerm = "%$search%";
$stmt->bindParam(':search', $searchTerm);
$stmt->execute();

$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($medicines);
?>