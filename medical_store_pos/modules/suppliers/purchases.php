<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$supplier_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get supplier info
$query = "SELECT * FROM suppliers WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $supplier_id);
$stmt->execute();
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$supplier) {
    header("Location: index.php");
    exit();
}

// Get purchase history
$query = "SELECT * FROM purchases WHERE supplier_id = :supplier_id ORDER BY purchase_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':supplier_id', $supplier_id);
$stmt->execute();
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total summary
$totalQuery = "SELECT COUNT(*) as total_orders, SUM(total_amount) as total_amount, SUM(paid_amount) as total_paid, SUM(due_amount) as total_due 
               FROM purchases WHERE supplier_id = :supplier_id";
$totalStmt = $db->prepare($totalQuery);
$totalStmt->bindParam(':supplier_id', $supplier_id);
$totalStmt->execute();
$summary = $totalStmt->fetch(PDO::FETCH_ASSOC);

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
        margin-bottom: 5px;
    }
    
    .page-header p {
        color: #64748b;
        font-size: 14px;
    }
    
    .supplier-info {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .supplier-detail {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .supplier-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea20, #764ba220);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .supplier-icon i {
        font-size: 28px;
        color: #667eea;
    }
    
    .supplier-detail h3 {
        font-size: 18px;
        color: #1e293b;
    }
    
    .supplier-detail p {
        color: #64748b;
        font-size: 13px;
        margin-top: 5px;
    }
    
    .stats-mini {
        display: flex;
        gap: 30px;
    }
    
    .stat-mini {
        text-align: center;
    }
    
    .stat-mini .number {
        font-size: 22px;
        font-weight: 700;
        color: #667eea;
    }
    
    .stat-mini .label {
        font-size: 12px;
        color: #64748b;
    }
    
    .back-btn {
        background: #64748b;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }
    
    .back-btn:hover {
        background: #475569;
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
    
    .data-table th {
        padding: 12px;
        text-align: left;
        font-size: 12px;
    }
    
    .data-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    
    .data-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-pending {
        background: #fed7aa;
        color: #92400e;
    }
    
    .empty-state {
        text-align: center;
        padding: 50px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    .btn-sm {
        padding: 4px 10px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .btn-sm-info {
        background: #8b5cf6;
        color: white;
    }
    
    @media (max-width: 768px) {
        .history-container {
            padding: 15px;
        }
        
        .supplier-info {
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
        <p>View all purchase orders from this supplier</p>
    </div>
    
    <!-- Supplier Information -->
    <div class="supplier-info">
        <div class="supplier-detail">
            <div class="supplier-icon">
                <i class="fas fa-building"></i>
            </div>
            <div>
                <h3><?php echo htmlspecialchars($supplier['name']); ?></h3>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($supplier['phone']); ?> 
                   <i class="fas fa-envelope" style="margin-left: 15px;"></i> <?php echo htmlspecialchars($supplier['email'] ?: 'N/A'); ?></p>
            </div>
        </div>
        <div class="stats-mini">
            <div class="stat-mini">
                <div class="number"><?php echo $summary['total_orders'] ?? 0; ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-mini">
                <div class="number">₨ <?php echo number_format($summary['total_amount'] ?? 0, 2); ?></div>
                <div class="label">Total Amount</div>
            </div>
            <div class="stat-mini">
                <div class="number">₨ <?php echo number_format($summary['total_due'] ?? 0, 2); ?></div>
                <div class="label">Due Amount</div>
            </div>
        </div>
        <div>
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Suppliers</a>
        </div>
    </div>
    
    <!-- Purchases Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Purchase Date</th>
                    <th>Total Amount</th>
                    <th>Paid Amount</th>
                    <th>Due Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($purchases) > 0): ?>
                    <?php foreach($purchases as $purchase): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($purchase['invoice_number']); ?></strong></td>
                        <td><?php echo date('d-m-Y', strtotime($purchase['purchase_date'])); ?></td>
                        <td>₨ <?php echo number_format($purchase['total_amount'], 2); ?></td>
                        <td>₨ <?php echo number_format($purchase['paid_amount'], 2); ?></td>
                        <td>₨ <?php echo number_format($purchase['due_amount'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $purchase['status'] == 'completed' ? 'badge-completed' : 'badge-pending'; ?>">
                                <?php echo ucfirst($purchase['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="../purchases/view.php?id=<?php echo $purchase['id']; ?>" class="btn-sm btn-sm-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-shopping-cart"></i>
                                <h3>No Purchase Orders Found</h3>
                                <p>No purchase orders have been created for this supplier yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>