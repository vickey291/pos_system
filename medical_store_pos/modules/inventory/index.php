<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get low stock medicines
$query = "SELECT * FROM medicines WHERE stock_quantity <= min_stock_level ORDER BY stock_quantity ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get expiring medicines (next 30 days)
$query = "SELECT * FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
          AND expiry_date >= CURDATE() ORDER BY expiry_date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get expired medicines
$query = "SELECT * FROM medicines WHERE expiry_date < CURDATE() ORDER BY expiry_date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get inventory summary
$query = "SELECT COUNT(*) as total, SUM(stock_quantity) as total_stock, SUM(stock_quantity * purchase_price) as total_value 
          FROM medicines";
$stmt = $db->prepare($query);
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Include header after all processing
require_once '../../includes/header.php';
?>

<style>
    .inventory-container {
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
    
    .stat-card.warning .stat-icon i {
        color: #f59e0b;
    }
    
    /* Sections */
    .inventory-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .inventory-section h3 {
        font-size: 18px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .section-warning h3 i {
        color: #f59e0b;
    }
    
    .section-danger h3 i {
        color: #ef4444;
    }
    
    /* Tables */
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table thead {
        background: #f8fafc;
    }
    
    .data-table th {
        padding: 12px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .data-table td {
        padding: 10px 12px;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
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
    
    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-warning {
        background: #fed7aa;
        color: #92400e;
    }
    
    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }
    
    /* Buttons */
    .btn-sm {
        padding: 5px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s;
    }
    
    .btn-sm-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-sm-danger:hover {
        background: #dc2626;
    }
    
    .btn-sm-primary {
        background: #3b82f6;
        color: white;
    }
    
    /* Two column layout */
    .two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 25px;
    }
    
    /* Stock level indicator */
    .stock-level {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .stock-bar {
        width: 80px;
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .stock-bar-fill {
        height: 100%;
        border-radius: 3px;
    }
    
    .stock-bar-fill.critical {
        background: #ef4444;
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .inventory-container {
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
        
        .two-columns {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .inventory-section {
            padding: 15px;
        }
        
        .data-table {
            font-size: 12px;
        }
        
        .data-table th,
        .data-table td {
            padding: 8px;
        }
    }
</style>

<div class="inventory-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-boxes"></i> Inventory Management</h1>
        <p>Track stock levels, monitor expiry dates, and manage your pharmacy inventory</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-cubes"></i></div>
            <div class="stat-info">
                <h3><?php echo $summary['total'] ?? 0; ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-box"></i></div>
            <div class="stat-info">
                <h3><?php echo $summary['total_stock'] ?? 0; ?></h3>
                <p>Total Stock Units</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo number_format($summary['total_value'] ?? 0, 2); ?></h3>
                <p>Inventory Value</p>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <h3><?php echo count($low_stock); ?></h3>
                <p>Low Stock Items</p>
            </div>
        </div>
    </div>

    <!-- Two Column Layout for Low Stock and Expiring -->
    <div class="two-columns">
        <!-- Low Stock Section -->
        <div class="inventory-section">
            <h3><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Low Stock Alert</h3>
            <?php if(count($low_stock) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Medicine Name</th>
                            <th>Current Stock</th>
                            <th>Min Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($low_stock as $item): 
                            $percentage = ($item['stock_quantity'] / max($item['min_stock_level'], 1)) * 100;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td>
                                <div class="stock-level">
                                    <span style="color: #ef4444; font-weight: 600;"><?php echo $item['stock_quantity']; ?></span>
                                    <div class="stock-bar">
                                        <div class="stock-bar-fill critical" style="width: <?php echo min(100, $percentage); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $item['min_stock_level']; ?></td>
                            <td><span class="badge badge-danger">Critical Stock</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 15px; text-align: right;">
                    <a href="../medicines/index.php?stock_filter=low" class="btn-sm btn-primary">View All Low Stock <i class="fas fa-arrow-right"></i></a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <p>No low stock items found. All medicines are well stocked!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Expiring Soon Section -->
        <div class="inventory-section">
            <h3><i class="fas fa-calendar-week" style="color: #f59e0b;"></i> Expiring Soon (30 days)</h3>
            <?php if(count($expiring) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Medicine Name</th>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th>Days Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($expiring as $item): 
                            $days_left = ceil((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                            $warningClass = $days_left <= 7 ? 'danger' : 'warning';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><code><?php echo $item['batch_number']; ?></code></td>
                            <td><?php echo date('d-m-Y', strtotime($item['expiry_date'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $warningClass; ?>">
                                    <?php echo $days_left; ?> days
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 15px; text-align: right;">
                    <a href="../medicines/index.php?expiry_filter=expiring" class="btn-sm btn-primary">View All Expiring <i class="fas fa-arrow-right"></i></a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <p>No expiring medicines found in next 30 days.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expired Medicines Section -->
    <div class="inventory-section">
        <h3><i class="fas fa-skull" style="color: #ef4444;"></i> Expired Medicines</h3>
        <?php if(count($expired) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Batch #</th>
                        <th>Expiry Date</th>
                        <th>Stock Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($expired as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                        <td><code><?php echo $item['batch_number']; ?></code></td>
                        <td><span class="badge badge-danger"><?php echo date('d-m-Y', strtotime($item['expiry_date'])); ?></span></td>
                        <td><?php echo $item['stock_quantity']; ?> units</td>
                        <td>
                            <a href="dispose.php?id=<?php echo $item['id']; ?>" class="btn-sm btn-sm-danger" onclick="return confirm('Are you sure you want to dispose this medicine?');">
                                <i class="fas fa-trash-alt"></i> Dispose
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color: #10b981;"></i>
                <p>No expired medicines found. All medicines are valid!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>