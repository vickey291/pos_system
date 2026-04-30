<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';


$database = new Database();
$db = $database->getConnection();

// Handle delete
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if supplier has any purchases
    $checkQuery = "SELECT COUNT(*) as count FROM purchases WHERE supplier_id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0) {
        $_SESSION['error'] = "Cannot delete supplier because they have purchase records!";
    } else {
        $query = "DELETE FROM suppliers WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        if($stmt->execute()) {
            $_SESSION['success'] = "Supplier deleted successfully!";
        }
    }
    header("Location: index.php");
    exit();
}

// Get all suppliers
$query = "SELECT * FROM suppliers ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_suppliers = count($suppliers);
$active_suppliers = 0;
$total_purchases = 0;

foreach($suppliers as $supplier) {
    $purchaseQuery = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM purchases WHERE supplier_id = :id";
    $purchaseStmt = $db->prepare($purchaseQuery);
    $purchaseStmt->bindParam(':id', $supplier['id']);
    $purchaseStmt->execute();
    $purchaseData = $purchaseStmt->fetch(PDO::FETCH_ASSOC);
    
    if($purchaseData['count'] > 0) {
        $active_suppliers++;
        $total_purchases += $purchaseData['total'];
    }
}

require_once '../../includes/header.php';
?>

<style>
    .suppliers-container {
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
    
    .btn-primary:hover {
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
    
    /* Supplier Name with Icon */
    .supplier-name {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .supplier-icon {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #667eea20, #764ba220);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .supplier-icon i {
        font-size: 16px;
        color: #667eea;
    }
    
    .supplier-info strong {
        display: block;
        color: #1e293b;
        margin-bottom: 3px;
    }
    
    .supplier-info small {
        font-size: 11px;
        color: #94a3b8;
    }
    
    /* Badge */
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
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
    
    .empty-state h3 {
        color: #3a4759;
        margin-bottom: 8px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .suppliers-container {
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

<div class="suppliers-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-truck"></i> Suppliers Management</h1>
        <p>Manage your medicine suppliers and track purchase history</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-truck"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_suppliers; ?></h3>
                <p>Total Suppliers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3><?php echo $active_suppliers; ?></h3>
                <p>Active Suppliers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo number_format($total_purchases, 2); ?></h3>
                <p>Total Purchases</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_suppliers; ?></h3>
                <p>Total Orders</p>
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
        <div>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Supplier
            </a>
        </div>
        
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Search suppliers by name, code or phone...">
            <button id="searchBtn" class="btn btn-primary" style="padding: 8px 16px;">
                <i class="fas fa-search"></i> Search
            </button>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="table-responsive">
        <table class="data-table" id="suppliersTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Supplier Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($suppliers) > 0): ?>
                    <?php foreach($suppliers as $supplier): 
                        // Check if supplier has purchases
                        $checkPurchase = "SELECT COUNT(*) as count FROM purchases WHERE supplier_id = :id";
                        $checkStmt = $db->prepare($checkPurchase);
                        $checkStmt->bindParam(':id', $supplier['id']);
                        $checkStmt->execute();
                        $hasPurchases = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                    ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($supplier['supplier_code']); ?></code></td>
                        <td>
                            <div class="supplier-name">
                                <div class="supplier-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="supplier-info">
                                    <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                    <small><?php echo htmlspecialchars($supplier['supplier_code']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($supplier['contact_person'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['email'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(substr($supplier['address'], 0, 40) ?: 'N/A'); ?></td>
                        <td>
                            <span class="badge <?php echo $hasPurchases ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $hasPurchases ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="btn-sm btn-sm-primary" title="Edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $supplier['id']; ?>" class="btn-sm btn-sm-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this supplier?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                                <a href="purchases.php?id=<?php echo $supplier['id']; ?>" class="btn-sm btn-sm-info" title="Purchase History">
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
                                <i class="fas fa-truck"></i>
                                <h3>No Suppliers Found</h3>
                                <p>Click "Add New Supplier" to get started.</p>
                                <a href="add.php" class="btn btn-primary" style="margin-top: 15px; display: inline-block;">
                                    <i class="fas fa-plus"></i> Add New Supplier
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const suppliersTable = document.getElementById('suppliersTable');
    
    function filterSuppliers() {
        const searchTerm = searchInput.value.toLowerCase();
        const rows = suppliersTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            if(row.querySelector('.empty-state')) return;
            
            const code = row.cells[0]?.innerText.toLowerCase() || '';
            const name = row.cells[1]?.innerText.toLowerCase() || '';
            const phone = row.cells[3]?.innerText.toLowerCase() || '';
            
            if(code.includes(searchTerm) || name.includes(searchTerm) || phone.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchBtn.addEventListener('click', filterSuppliers);
    searchInput.addEventListener('keypress', function(e) {
        if(e.key === 'Enter') filterSuppliers();
    });
</script>

<?php require_once '../../includes/footer.php'; ?>