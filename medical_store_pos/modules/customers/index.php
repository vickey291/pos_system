<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Handle delete with protection
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if customer has any sales
    $checkQuery = "SELECT COUNT(*) as count FROM sales WHERE customer_id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0) {
        $_SESSION['error'] = "Cannot delete customer because they have sales records!";
    } else {
        $query = "DELETE FROM customers WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        if($stmt->execute()) {
            $_SESSION['success'] = "Customer deleted successfully!";
        }
    }
    header("Location: index.php");
    exit();
}

// Get all customers with search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM customers WHERE 1=1";
if(!empty($search)) {
    $query .= " AND (name LIKE :search OR customer_code LIKE :search OR phone LIKE :search)";
}
$query .= " ORDER BY total_purchases DESC, name";
$stmt = $db->prepare($query);
if(!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm);
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_customers = count($customers);
$total_sales = array_sum(array_column($customers, 'total_purchases'));
$avg_purchase = $total_customers > 0 ? $total_sales / $total_customers : 0;

require_once '../../includes/header.php';
?>

<style>
    .customers-container {
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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
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
    
    .btn-warning {
        background: #f59e0b;
        color: white;
    }
    
    .btn-primary:hover, .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.3);
    }
    
    .search-box {
        display: flex;
        gap: 10px;
    }
    
    .search-box input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        width: 250px;
        font-size: 13px;
    }
    
    .search-box input:focus {
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
        min-width: 800px;
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
    
    /* Customer Name with Icon */
    .customer-name {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .customer-icon {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #667eea20, #764ba220);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .customer-icon i {
        font-size: 16px;
        color: #667eea;
    }
    
    /* Tier Badge */
    .tier-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .tier-gold {
        background: #fef3c7;
        color: #92400e;
    }
    
    .tier-silver {
        background: #f1f5f9;
        color: #475569;
    }
    
    .tier-bronze {
        background: #fed7aa;
        color: #92400e;
    }
    
    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 8px;
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
    
    .btn-sm-primary {
        background: #3b82f6;
        color: white;
    }
    
    .btn-sm-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-sm-info {
        background: #8b5cf6;
        color: white;
    }
    
    .btn-sm:hover {
        transform: scale(1.05);
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .customers-container {
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
        
        .search-box {
            flex-direction: column;
        }
        
        .search-box input {
            width: 100%;
        }
        
        .action-btns {
            flex-wrap: wrap;
        }
    }
</style>

<div class="customers-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Customers Management</h1>
        <p>Manage your customer database and track purchase history</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_customers; ?></h3>
                <p>Total Customers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo number_format($total_sales, 2); ?></h3>
                <p>Total Sales</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo number_format($avg_purchase, 2); ?></h3>
                <p>Avg Purchase</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-trophy"></i></div>
            <div class="stat-info">
                <h3><?php 
                    $gold = count(array_filter($customers, function($c) { return $c['total_purchases'] >= 50000; }));
                    echo $gold;
                ?></h3>
                <p>Gold Members</p>
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

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <div class="action-bar">
        <div style="display: flex; gap: 10px;">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Customer
            </a>
            <a href="loyalty.php" class="btn btn-warning">
                <i class="fas fa-gift"></i> Loyalty Program
            </a>
        </div>
        
        <div class="search-box">
            <form method="GET" action="" style="display: flex; gap: 10px;">
                <input type="text" name="search" placeholder="🔍 Search by name, code or phone..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if(!empty($search)): ?>
                    <a href="index.php" class="btn btn-primary" style="background: #64748b;">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Total Purchases</th>
                    <th>Tier</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($customers) > 0): ?>
                    <?php foreach($customers as $customer): 
                        $total = $customer['total_purchases'];
                        if($total >= 50000) {
                            $tier = 'Gold';
                            $tierClass = 'tier-gold';
                        } elseif($total >= 15000) {
                            $tier = 'Silver';
                            $tierClass = 'tier-silver';
                        } else {
                            $tier = 'Bronze';
                            $tierClass = 'tier-bronze';
                        }
                    ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($customer['customer_code']); ?></code></td>
                        <td>
                            <div class="customer-name">
                                <div class="customer-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email'] ?: 'N/A'); ?></td>
                        <td>₨ <?php echo number_format($total, 2); ?></td>
                        <td><span class="tier-badge <?php echo $tierClass; ?>"><?php echo $tier; ?></span></td>
                        <td><?php echo date('d-m-Y', strtotime($customer['created_at'])); ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn-sm btn-sm-primary" title="Edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $customer['id']; ?>" class="btn-sm btn-sm-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this customer?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                                <a href="history.php?id=<?php echo $customer['id']; ?>" class="btn-sm btn-sm-info" title="Purchase History">
                                    <i class="fas fa-history"></i> History
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h3>No Customers Found</h3>
                                <p><?php echo !empty($search) ? 'No customers match your search term.' : 'Click "Add New Customer" to get started.'; ?></p>
                                <?php if(empty($search)): ?>
                                    <a href="add.php" class="btn btn-primary" style="margin-top: 15px;">
                                        <i class="fas fa-plus"></i> Add New Customer
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