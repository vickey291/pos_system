<?php
session_start();
require_once '../../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $db->beginTransaction();
    
    // Generate invoice number
    $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Insert sale
    $query = "INSERT INTO sales (invoice_number, customer_id, subtotal, discount, total_amount, paid_amount, change_amount, payment_method, created_by) 
              VALUES (:invoice, :customer, :subtotal, :discount, :total, :paid, :change, :payment, :user)";
    
    $change = $data['paid'] - $data['total'];
    $customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':invoice', $invoice_number);
    $stmt->bindParam(':customer', $customer_id);
    $stmt->bindParam(':subtotal', $data['subtotal']);
    $stmt->bindParam(':discount', $data['discount']);
    $stmt->bindParam(':total', $data['total']);
    $stmt->bindParam(':paid', $data['paid']);
    $stmt->bindParam(':change', $change);
    $stmt->bindParam(':payment', $data['payment_method']);
    $stmt->bindParam(':user', $_SESSION['user_id']);
    $stmt->execute();
    
    $sale_id = $db->lastInsertId();
    
    // Insert sale items and update stock
    foreach($data['cart'] as $item) {
        $query = "INSERT INTO sale_items (sale_id, medicine_id, quantity, price, total) 
                  VALUES (:sale_id, :medicine_id, :quantity, :price, :total)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':sale_id', $sale_id);
        $stmt->bindParam(':medicine_id', $item['id']);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':price', $item['price']);
        $stmt->bindParam(':total', $item['total']);
        $stmt->execute();
        
        // Update stock
        $query = "UPDATE medicines SET stock_quantity = stock_quantity - :qty WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':qty', $item['quantity']);
        $stmt->bindParam(':id', $item['id']);
        $stmt->execute();
    }
    
    // Update customer total purchases if customer exists
    if($customer_id) {
        $query = "UPDATE customers SET total_purchases = total_purchases + :total WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':total', $data['total']);
        $stmt->bindParam(':id', $customer_id);
        $stmt->execute();
    }
    
    $db->commit();
    
    echo json_encode(['success' => true, 'invoice_number' => $invoice_number]);
    
} catch(Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>