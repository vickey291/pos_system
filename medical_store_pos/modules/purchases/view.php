<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

$query = "SELECT p.*, s.name as supplier_name, s.phone, s.email, s.address 
          FROM purchases p 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$purchase) {
    header("Location: index.php");
    exit();
}

$query = "SELECT pi.*, m.name, m.medicine_code 
          FROM purchase_items pi 
          JOIN medicines m ON pi.medicine_id = m.id 
          WHERE pi.purchase_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<style>
    .view-container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .card-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
    }
    
    .info-row {
        display: flex;
        margin-bottom: 10px;
    }
    
    .info-label {
        width: 120px;
        font-weight: 600;
        color: #64748b;
    }
    
    .info-value {
        flex: 1;
        color: #1e293b;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th, .data-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .data-table th {
        background: #f8fafc;
        font-weight: 600;
    }
    
    .total-row {
        text-align: right;
        margin-top: 15px;
        font-size: 18px;
        font-weight: bold;
    }
    
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    .badge-completed { background: #d1fae5; color: #065f46; }
    .badge-pending { background: #fed7aa; color: #92400e; }
    
    .btn-back {
        background: #64748b;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
    }
</style>

<div class="view-container">
    <div class="card">
        <div class="card-header">
            <h2>Purchase Order Details</h2>
            <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <div class="info-row">
            <div class="info-label">Invoice Number:</div>
            <div class="info-value"><strong><?php echo $purchase['invoice_number']; ?></strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Supplier:</div>
            <div class="info-value"><?php echo htmlspecialchars($purchase['supplier_name']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Purchase Date:</div>
            <div class="info-value"><?php echo date('d-m-Y', strtotime($purchase['purchase_date'])); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value"><span class="badge badge-<?php echo $purchase['status']; ?>"><?php echo ucfirst($purchase['status']); ?></span></div>
        </div>
    </div>
    
    <div class="card">
        <h3>Items</h3>
        <table class="data-table">
            <thead>
                <tr><th>Medicine</th><th>Code</th><th>Quantity</th><th>Price</th><th>Total</th></tr>
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
        </table>
        <div class="total-row">
            Total Amount: ₨ <?php echo number_format($purchase['total_amount'], 2); ?>
        </div>
    </div>
    
    <div class="card">
        <h3>Payment Summary</h3>
        <div class="info-row">
            <div class="info-label">Total Amount:</div>
            <div class="info-value">₨ <?php echo number_format($purchase['total_amount'], 2); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Paid Amount:</div>
            <div class="info-value">₨ <?php echo number_format($purchase['paid_amount'], 2); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Due Amount:</div>
            <div class="info-value" style="color: <?php echo $purchase['due_amount'] > 0 ? '#ef4444' : '#10b981'; ?>">
                ₨ <?php echo number_format($purchase['due_amount'], 2); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>