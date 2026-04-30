<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Handle delete with protection
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if purchase has items
    $checkQuery = "SELECT COUNT(*) as count FROM purchase_items WHERE purchase_id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0) {
        // First delete purchase items
        $delItems = "DELETE FROM purchase_items WHERE purchase_id = :id";
        $delStmt = $db->prepare($delItems);
        $delStmt->bindParam(':id', $id);
        $delStmt->execute();
    }
    
    $query = "DELETE FROM purchases WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if($stmt->execute()) {
        $_SESSION['success'] = "Purchase deleted successfully!";
    }
    header("Location: index.php");
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get all purchases with supplier info
$query = "SELECT p.*, s.name as supplier_name 
          FROM purchases p 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE 1=1";
$params = [];

if(!empty($search)) {
    $query .= " AND (p.invoice_number LIKE :search OR s.name LIKE :search)";
    $params[':search'] = "%$search%";
}
if(!empty($status_filter)) {
    $query .= " AND p.status = :status";
    $params[':status'] = $status_filter;
}

$query .= " ORDER BY p.purchase_date DESC, p.id DESC";

$stmt = $db->prepare($query);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_purchases = count($purchases);
$total_amount = array_sum(array_column($purchases, 'total_amount'));
$total_due = array_sum(array_column($purchases, 'due_amount'));
$completed = count(array_filter($purchases, function($p) { return $p['status'] == 'completed'; }));
$pending = count(array_filter($purchases, function($p) { return $p['status'] == 'pending'; }));

require_once '../../includes/header.php';
?>

<style>
    .purchases-container {
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
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 55px;
        height: 55px;
        background: linear-gradient(135deg, #667eea15, #764ba215);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon i {
        font-size: 26px;
        color: #667eea;
    }
    
    .stat-info h3 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 5px;
    }
    
    .stat-info p {
        font-size: 13px;
        color: #64748b;
    }
    
    /* Alerts */
    .alert {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border-left: 4px solid #10b981;
    }
    
    /* Action Bar */
    .action-bar {
        background: white;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .btn {
        padding: 8px 18px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .btn-info {
        background: #3b82f6;
        color: white;
    }
    
    .btn-primary:hover, .btn-info:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.3);
    }
    
    .filter-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-group input, .filter-group select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 13px;
    }
    
    .filter-group input:focus, .filter-group select:focus {
        outline: none;
        border-color: #667eea;
    }
    
    /* Table Styles */
    .table-responsive {
        background: white;
        border-radius: 12px;
        overflow-x: auto;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    
    .data-table thead {
        background: #1e293b;
        color: white;
    }
    
    .data-table th {
        padding: 14px 12px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
    }
    
    .data-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        color: #334155;
    }
    
    .data-table tbody tr:hover {
        background: #f8fafc;
    }
    
    /* Badges */
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-warning {
        background: #fed7aa;
        color: #92400e;
    }
    
    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .text-danger {
        color: #ef4444;
        font-weight: 600;
    }
    
    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .btn-sm {
        padding: 5px 10px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
    }
    
    .btn-sm-info { background: #8b5cf6; color: white; }
    .btn-sm-primary { background: #3b82f6; color: white; }
    .btn-sm-danger { background: #ef4444; color: white; }
    .btn-sm-success { background: #10b981; color: white; }
    .btn-sm:hover { transform: scale(1.05); }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 50px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .purchases-container {
            padding: 15px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .stat-card {
            padding: 15px;
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
        }
        
        .stat-icon i {
            font-size: 20px;
        }
        
        .stat-info h3 {
            font-size: 18px;
        }
        
        .action-bar {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-group {
            flex-direction: column;
        }
        
        .filter-group input, .filter-group select {
            width: 100%;
        }
        
        .action-btns {
            flex-wrap: wrap;
        }
    }
</style>

<div class="purchases-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-shopping-cart"></i> Purchase Management</h1>
        <p>Track all medicine purchases from suppliers</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_purchases; ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo number_format($total_amount, 2); ?></h3>
                <p>Total Amount</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3><?php echo $completed; ?></h3>
                <p>Completed</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3><?php echo $pending; ?></h3>
                <p>Pending</p>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <div class="action-bar">
        <div style="display: flex; gap: 10px;">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Purchase Order
            </a>
            <a href="supplier_orders.php" class="btn btn-info">
                <i class="fas fa-truck"></i> Supplier Orders
            </a>
        </div>
        
        <form method="GET" action="" class="filter-group">
            <input type="text" name="search" placeholder="🔍 Search invoice or supplier..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">All Status</option>
                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                <i class="fas fa-search"></i> Filter
            </button>
            <?php if(!empty($search) || !empty($status_filter)): ?>
                <a href="index.php" class="btn btn-primary" style="background: #64748b;">
                    <i class="fas fa-sync-alt"></i> Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Purchases Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Supplier</th>
                    <th>Purchase Date</th>
                    <th>Total Amount</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($purchases) > 0): ?>
                    <?php foreach($purchases as $purchase): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($purchase['invoice_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($purchase['supplier_name'] ?: 'N/A'); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($purchase['purchase_date'])); ?></td>
                        <td>₨ <?php echo number_format($purchase['total_amount'], 2); ?></td>
                        <td>₨ <?php echo number_format($purchase['paid_amount'], 2); ?></td>
                        <td class="<?php echo $purchase['due_amount'] > 0 ? 'text-danger' : ''; ?>">
                            ₨ <?php echo number_format($purchase['due_amount'], 2); ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $purchase['status']; ?>">
                                <?php echo ucfirst($purchase['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="view.php?id=<?php echo $purchase['id']; ?>" class="btn-sm btn-sm-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $purchase['id']; ?>" class="btn-sm btn-sm-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $purchase['id']; ?>" class="btn-sm btn-sm-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this purchase?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <a href="print.php?id=<?php echo $purchase['id']; ?>" class="btn-sm btn-sm-success" title="Print" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-shopping-cart"></i>
                                <h3>No Purchase Orders Found</h3>
                                <p><?php echo (!empty($search) || !empty($status_filter)) ? 'No purchases match your filters.' : 'Click "New Purchase Order" to get started.'; ?></p>
                                <?php if(empty($search) && empty($status_filter)): ?>
                                    <a href="add.php" class="btn btn-primary" style="margin-top: 15px;">
                                        <i class="fas fa-plus"></i> New Purchase Order
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>