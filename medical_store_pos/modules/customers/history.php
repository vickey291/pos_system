<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$customer_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get customer info
$query = "SELECT * FROM customers WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $customer_id);
$stmt->execute();
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$customer) {
    header("Location: index.php");
    exit();
}

// Get purchase history
$query = "SELECT * FROM sales WHERE customer_id = :customer_id ORDER BY sale_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary
$total_sales = count($sales);
$total_amount = array_sum(array_column($sales, 'total_amount'));

require_once '../../includes/header.php';
?>

<style>
    .history-container {
        padding: 20px;
    }
    
    .page-header {
        margin-bottom: 25px;
    }
    
    .page-header h1 {
        font-size: 24px;
        color: #1e293b;
    }
    
    .customer-info {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
        color: white;
    }
    
    .customer-detail {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .customer-icon {
        width: 65px;
        height: 65px;
        background: rgba(255,255,255,0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .customer-icon i {
        font-size: 32px;
    }
    
    .customer-detail h3 {
        font-size: 22px;
        margin-bottom: 8px;
    }
    
    .stats-mini {
        display: flex;
        gap: 30px;
        align-items: center;
    }
    
    .stat-mini {
        text-align: center;
        background: rgba(255,255,255,0.15);
        padding: 12px 20px;
        border-radius: 12px;
    }
    
    .stat-mini .number {
        font-size: 24px;
        font-weight: 700;
    }
    
    .back-btn {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
    }
    
    .table-responsive {
        background: white;
        border-radius: 12px;
        overflow-x: auto;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }
    
    .data-table thead {
        background: #1e293b;
        color: white;
    }
    
    .data-table th, .data-table td {
        padding: 12px;
        text-align: left;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .empty-state {
        text-align: center;
        padding: 50px;
        color: #94a3b8;
    }
    
    @media (max-width: 768px) {
        .customer-info {
            flex-direction: column;
        }
        
        .stats-mini {
            justify-content: space-around;
        }
    }
</style>

<div class="history-container">
    <div class="page-header">
        <h1><i class="fas fa-history"></i> Purchase History</h1>
        <p>View all purchases made by this customer</p>
    </div>
    
    <div class="customer-info">
        <div class="customer-detail">
            <div class="customer-icon">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <h3><?php echo htmlspecialchars($customer['name']); ?></h3>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?></p>
            </div>
        </div>
        <div class="stats-mini">
            <div class="stat-mini">
                <div class="number"><?php echo $total_sales; ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-mini">
                <div class="number">₨ <?php echo number_format($total_amount, 2); ?></div>
                <div class="label">Total Spent</div>
            </div>
        </div>
        <div>
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if(count($sales) > 0): ?>
                    <?php foreach($sales as $sale): ?>
                    <tr>
                        <td><?php echo $sale['invoice_number']; ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($sale['sale_date'])); ?></td>
                        <td>₨ <?php echo number_format($sale['total_amount'], 2); ?></td>
                        <td><?php echo ucfirst($sale['payment_method']); ?></td>
                        <td><span class="badge badge-success">Completed</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-state">No purchase history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>