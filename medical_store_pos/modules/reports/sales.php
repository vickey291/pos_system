<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT s.*, c.name as customer_name, u.full_name as cashier 
          FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          LEFT JOIN users u ON s.created_by = u.id";
$countQuery = "SELECT COUNT(*) as total FROM sales s";

if(!empty($search)) {
    $query .= " WHERE s.invoice_number LIKE :search";
    $countQuery .= " WHERE invoice_number LIKE :search";
    $searchTerm = "%$search%";
}

$query .= " ORDER BY s.sale_date DESC LIMIT :offset, :limit";
$stmt = $db->prepare($query);
$countStmt = $db->prepare($countQuery);

if(!empty($search)) {
    $stmt->bindParam(':search', $searchTerm);
    $countStmt->bindParam(':search', $searchTerm);
}
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$countStmt->execute();

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalSales = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalSales / $limit);

// Calculate totals
$totalRevenue = array_sum(array_column($sales, 'total_amount'));

require_once '../../includes/header.php';
?>

<style>
    .sales-report-container {
        padding: 20px;
    }
    
    .page-header {
        margin-bottom: 25px;
    }
    
    .page-header h1 {
        font-size: 24px;
        color: #1e293b;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .page-header p {
        color: #64748b;
        font-size: 14px;
    }
    
    /* Stats Cards */
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
    }
    
    .stat-info p {
        font-size: 13px;
        color: #64748b;
    }
    
    /* Filter Bar */
    .filter-bar {
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
    
    .search-group {
        display: flex;
        gap: 10px;
    }
    
    .search-group input {
        padding: 10px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        width: 250px;
        font-size: 14px;
    }
    
    .search-group input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .btn-secondary {
        background: #64748b;
        color: white;
    }
    
    .btn-success {
        background: #10b981;
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    /* Table */
    .table-responsive {
        background: white;
        border-radius: 16px;
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
    
    .badge-cash { background: #d1fae5; color: #065f46; }
    .badge-card { background: #dbeafe; color: #1e40af; }
    .badge-online { background: #fef3c7; color: #92400e; }
    
    .receipt-link {
        background: #3b82f6;
        color: white;
        padding: 5px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .receipt-link:hover {
        background: #2563eb;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 25px;
        flex-wrap: wrap;
    }
    
    .pagination a, .pagination span {
        padding: 8px 14px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.3s;
    }
    
    .pagination a {
        background: white;
        color: #667eea;
        border: 1px solid #e2e8f0;
    }
    
    .pagination a:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .pagination .active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
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
    
    @media (max-width: 768px) {
        .sales-report-container { padding: 15px; }
        .filter-bar { flex-direction: column; }
        .search-group { width: 100%; }
        .search-group input { width: 100%; }
        .stats-cards { grid-template-columns: 1fr; }
    }
</style>

<div class="sales-report-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> Sales Report</h1>
        <p>View and manage all sales transactions</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div class="stat-info">
                <h3><?php echo $totalSales; ?></h3>
                <p>Total Transactions</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo number_format($totalRevenue, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="" class="search-group">
            <input type="text" name="search" placeholder="🔍 Search by invoice number..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            <?php if(!empty($search)): ?>
                <a href="sales.php" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
            <?php endif; ?>
        </form>
        <a href="export_sales.php" class="btn btn-success"><i class="fas fa-file-excel"></i> Export to Excel</a>
    </div>
    
    <!-- Sales Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date & Time</th>
                    <th>Customer</th>
                    <th>Cashier</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($sales) > 0): ?>
                    <?php foreach($sales as $sale): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($sale['invoice_number']); ?></strong></td>
                        <td><?php echo date('d-m-Y h:i A', strtotime($sale['sale_date'])); ?></td>
                        <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></td>
                        <td><?php echo htmlspecialchars($sale['cashier']); ?></td>
                        <td><strong>₨ <?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                        <td><span class="badge badge-<?php echo $sale['payment_method']; ?>"><?php echo ucfirst($sale['payment_method']); ?></span></td>
                        <td>
                            <a href="../billing/print_receipt.php?invoice=<?php echo urlencode($sale['invoice_number']); ?>" 
                               class="receipt-link" target="_blank">
                                <i class="fas fa-print"></i> Receipt
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No Sales Found</h3>
                                <p><?php echo !empty($search) ? 'No sales match your search term.' : 'No sales recorded yet.'; ?></p>
                                <?php if(empty($search)): ?>
                                    <a href="../billing/index.php" class="btn btn-primary" style="margin-top: 15px;">
                                        <i class="fas fa-cash-register"></i> Create First Sale
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>
        
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <?php if($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>