<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT s.*, 
          COUNT(p.id) as total_orders,
          COALESCE(SUM(p.total_amount), 0) as total_amount,
          COALESCE(SUM(p.due_amount), 0) as total_due
          FROM suppliers s
          LEFT JOIN purchases p ON s.id = p.supplier_id
          GROUP BY s.id
          ORDER BY total_amount DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<style>
    .supplier-orders-container { padding: 20px; }
    .page-header { margin-bottom: 25px; }
    .page-header h1 { font-size: 24px; color: #1e293b; }
    
    .supplier-card {
        background: white;
        border-radius: 16px;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .supplier-header {
        background: linear-gradient(135deg, #1e293b, #2d3748);
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .supplier-stats {
        display: flex;
        gap: 30px;
    }
    
    .supplier-stats div { text-align: center; }
    .supplier-stats .number { font-size: 20px; font-weight: 700; }
    .supplier-stats .label { font-size: 11px; opacity: 0.8; }
    
    .supplier-body { padding: 20px; }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th, .data-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    
    .data-table th { background: #f8fafc; font-weight: 600; }
    
    .badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
    }
    .badge-completed { background: #d1fae5; color: #065f46; }
    .badge-pending { background: #fed7aa; color: #92400e; }
    
    .btn-sm {
        padding: 4px 8px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 11px;
        background: #3b82f6;
        color: white;
    }
    
    @media (max-width: 768px) {
        .supplier-header { flex-direction: column; }
        .supplier-stats { justify-content: space-around; }
    }
</style>

<div class="supplier-orders-container">
    <div class="page-header">
        <h1><i class="fas fa-truck"></i> Supplier Orders Report</h1>
        <p>View all purchase orders by supplier</p>
    </div>
    
    <?php foreach($suppliers as $supplier): ?>
    <div class="supplier-card">
        <div class="supplier-header">
            <div>
                <h3><i class="fas fa-building"></i> <?php echo htmlspecialchars($supplier['name']); ?></h3>
                <p style="font-size: 12px; margin-top: 5px;"><?php echo $supplier['phone']; ?> | <?php echo $supplier['email']; ?></p>
            </div>
            <div class="supplier-stats">
                <div><div class="number"><?php echo $supplier['total_orders']; ?></div><div class="label">Orders</div></div>
                <div><div class="number">₨ <?php echo number_format($supplier['total_amount'], 2); ?></div><div class="label">Total</div></div>
                <div><div class="number">₨ <?php echo number_format($supplier['total_due'], 2); ?></div><div class="label">Due</div></div>
            </div>
        </div>
        <div class="supplier-body">
            <?php
            $purchaseQuery = "SELECT * FROM purchases WHERE supplier_id = :id ORDER BY purchase_date DESC LIMIT 5";
            $purchaseStmt = $db->prepare($purchaseQuery);
            $purchaseStmt->bindParam(':id', $supplier['id']);
            $purchaseStmt->execute();
            $purchases = $purchaseStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if(count($purchases) > 0): ?>
            <table class="data-table">
                <thead><tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach($purchases as $purchase): ?>
                    <tr>
                        <td><?php echo $purchase['invoice_number']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($purchase['purchase_date'])); ?></td>
                        <td>₨ <?php echo number_format($purchase['total_amount'], 2); ?></td>
                        <td>₨ <?php echo number_format($purchase['paid_amount'], 2); ?></td>
                        <td>₨ <?php echo number_format($purchase['due_amount'], 2); ?></td>
                        <td><span class="badge badge-<?php echo $purchase['status']; ?>"><?php echo ucfirst($purchase['status']); ?></span></td>
                        <td><a href="view.php?id=<?php echo $purchase['id']; ?>" class="btn-sm">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color: #94a3b8; text-align: center;">No purchase orders found</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>