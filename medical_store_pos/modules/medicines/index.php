<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Handle delete
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $checkQuery = "SELECT COUNT(*) as count FROM sale_items WHERE medicine_id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0) {
        $_SESSION['error'] = "Cannot delete medicine because it has sales records!";
    } else {
        $query = "DELETE FROM medicines WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        if($stmt->execute()) {
            $_SESSION['success'] = "Medicine deleted successfully!";
        }
    }
    header("Location: index.php");
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';
$expiry_filter = isset($_GET['expiry_filter']) ? $_GET['expiry_filter'] : '';

// Build query
$query = "SELECT * FROM medicines WHERE 1=1";
$params = [];

if(!empty($search)) {
    $query .= " AND (name LIKE :search OR medicine_code LIKE :search OR company LIKE :search)";
    $params[':search'] = "%$search%";
}

if($stock_filter == 'low') {
    $query .= " AND stock_quantity <= min_stock_level";
} elseif($stock_filter == 'out') {
    $query .= " AND stock_quantity = 0";
}

if($expiry_filter == 'expiring') {
    $query .= " AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE()";
} elseif($expiry_filter == 'expired') {
    $query .= " AND expiry_date < CURDATE()";
}

$query .= " ORDER BY name ASC";

$stmt = $db->prepare($query);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN stock_quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
    SUM(CASE WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() THEN 1 ELSE 0 END) as expiring,
    SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired,
    SUM(stock_quantity) as total_stock,
    SUM(stock_quantity * sale_price) as total_value
    FROM medicines";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="medicines-container" style="padding: 15px;">
    <!-- Page Header -->
    <div class="page-header" style="margin-bottom: 20px;">
        <h1 style="font-size: 22px;"><i class="fas fa-pills"></i> Medicines Management</h1>
        <p style="font-size: 13px;">Manage your pharmacy inventory</p>
    </div>

    <!-- Statistics Cards - Responsive Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div style="background: white; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="width: 45px; height: 45px; background: #e0e7ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-capsules" style="color: #667eea; font-size: 20px;"></i>
            </div>
            <div>
                <h3 style="font-size: 20px; font-weight: 700;"><?php echo $stats['total']; ?></h3>
                <p style="font-size: 12px; color: #666;">Total Medicines</p>
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="width: 45px; height: 45px; background: #e0e7ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-boxes" style="color: #667eea; font-size: 20px;"></i>
            </div>
            <div>
                <h3 style="font-size: 20px; font-weight: 700;"><?php echo $stats['total_stock']; ?></h3>
                <p style="font-size: 12px; color: #666;">Total Stock</p>
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="width: 45px; height: 45px; background: #e0e7ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-rupee-sign" style="color: #667eea; font-size: 20px;"></i>
            </div>
            <div>
                <h3 style="font-size: 20px; font-weight: 700;">₨ <?php echo number_format($stats['total_value'], 0); ?></h3>
                <p style="font-size: 12px; color: #666;">Inventory Value</p>
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="width: 45px; height: 45px; background: #fed7aa; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 20px;"></i>
            </div>
            <div>
                <h3 style="font-size: 20px; font-weight: 700;"><?php echo $stats['low_stock']; ?></h3>
                <p style="font-size: 12px; color: #666;">Low Stock</p>
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="width: 45px; height: 45px; background: #fee2e2; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-calendar-times" style="color: #ef4444; font-size: 20px;"></i>
            </div>
            <div>
                <h3 style="font-size: 20px; font-weight: 700;"><?php echo $stats['expiring']; ?></h3>
                <p style="font-size: 12px; color: #666;">Expiring Soon</p>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if(isset($_SESSION['success'])): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 10px; margin-bottom: 15px;">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 10px; margin-bottom: 15px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Action Bar -->
<div style="background: white; border-radius: 12px; padding: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="add.php" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px;">
            <i class="fas fa-plus"></i> Add Medicine
        </a>
        <a href="import.php" class="btn btn-warning" style="background: #f59e0b; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px;">
            <i class="fas fa-upload"></i> Import CSV
        </a>
        <a href="export.php" class="btn btn-info" style="background: #3b82f6; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px;">
            <i class="fas fa-download"></i> Export
        </a>
    </div>
    
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <input type="text" id="searchInput" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; width: 200px;">
        <select id="stockFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;">
            <option value="">All Stock</option>
            <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock</option>
            <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
        </select>
        <select id="expiryFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;">
            <option value="">All Expiry</option>
            <option value="expiring" <?php echo $expiry_filter == 'expiring' ? 'selected' : ''; ?>>Expiring Soon</option>
            <option value="expired" <?php echo $expiry_filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
        </select>
        <button id="applyFilters" style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer;">Apply</button>
        <a href="index.php" style="background: #6c757d; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none;">Reset</a>
    </div>
</div>

    <!-- Table -->
    <div style="background: white; border-radius: 12px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <table style="width: 100%; border-collapse: collapse; min-width: 700px;">
            <thead>
                <tr>
                    <th style="padding: 12px; text-align: left; background: #1e293b; color: white; font-size: 12px;">Code</th>
                    <th style="padding: 12px; text-align: left; background: #1e293b; color: white; font-size: 12px;">Medicine Name</th>
                    <th style="padding: 12px; text-align: left; background: #1e293b; color: white; font-size: 12px;">Company</th>
                    <th style="padding: 12px; text-align: left; background: #1e293b; color: white; font-size: 12px;">Expiry</th>
                    <th style="padding: 12px; text-align: left; background: #1e293b; color: white; font-size: 12px;">Sale Price</th>
                    <th style="padding: 12px; text-align: left; background: #1e293b; color: white; font-size: 12px;">Stock</th>
                    <th style="padding: 12px; text-align: left; background: #1e293b; color: white; font-size: 12px;">Status</th>
                    <th style="padding: 12px; text-align: left; background: #1e293b; color: white; font-size: 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($medicines) > 0): ?>
                    <?php foreach($medicines as $medicine): 
                        $expiry = strtotime($medicine['expiry_date']);
                        $today = time();
                        $daysLeft = round(($expiry - $today) / (60 * 60 * 24));
                        
                        if($medicine['stock_quantity'] <= $medicine['min_stock_level']) {
                            $status = 'Low Stock';
                            $statusClass = 'danger';
                        } elseif($daysLeft <= 0) {
                            $status = 'Expired';
                            $statusClass = 'danger';
                        } elseif($daysLeft <= 30) {
                            $status = 'Expiring Soon';
                            $statusClass = 'warning';
                        } else {
                            $status = 'In Stock';
                            $statusClass = 'success';
                        }
                    ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px; font-size: 12px;"><code><?php echo htmlspecialchars($medicine['medicine_code']); ?></code></td>
                        <td style="padding: 10px; font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($medicine['name']); ?></td>
                        <td style="padding: 10px; font-size: 12px;"><?php echo htmlspecialchars($medicine['company'] ?: 'N/A'); ?></td>
                        <td style="padding: 10px; font-size: 12px;">
                            <?php echo date('d-m-Y', strtotime($medicine['expiry_date'])); ?>
                            <?php if($daysLeft <= 30 && $daysLeft > 0): ?>
                                <br><small style="color: #f59e0b;">(<?php echo $daysLeft; ?> days)</small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px; font-size: 13px; font-weight: 600;">₨ <?php echo number_format($medicine['sale_price'], 2); ?></td>
                        <td style="padding: 10px; font-size: 13px;"><?php echo $medicine['stock_quantity']; ?></td>
                        <td style="padding: 10px;">
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; 
                                <?php echo $statusClass == 'danger' ? 'background: #fee2e2; color: #991b1b;' : ($statusClass == 'warning' ? 'background: #fed7aa; color: #92400e;' : 'background: #d1fae5; color: #065f46;'); ?>">
                                <?php echo $status; ?>
                            </span>
                        </td>
                        <td style="padding: 10px;">
                            <div style="display: flex; gap: 5px;">
                                <a href="edit.php?id=<?php echo $medicine['id']; ?>" style="background: #3b82f6; color: white; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-size: 11px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="view.php?id=<?php echo $medicine['id']; ?>" style="background: #8b5cf6; color: white; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-size: 11px;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?delete=<?php echo $medicine['id']; ?>" onclick="return confirm('Delete this medicine?');" style="background: #ef4444; color: white; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-size: 11px;">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="padding: 40px; text-align: center;">
                            <i class="fas fa-pills" style="font-size: 40px; color: #cbd5e1;"></i>
                            <h3 style="margin-top: 10px;">No Medicines Found</h3>
                            <a href="add.php" class="btn btn-primary" style="display: inline-block; margin-top: 10px;">Add Medicine</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('applyFilters').addEventListener('click', function() {
    const search = document.getElementById('searchInput').value;
    const stockFilter = document.getElementById('stockFilter').value;
    const expiryFilter = document.getElementById('expiryFilter').value;
    let url = 'index.php?';
    if(search) url += 'search=' + encodeURIComponent(search) + '&';
    if(stockFilter) url += 'stock_filter=' + stockFilter + '&';
    if(expiryFilter) url += 'expiry_filter=' + expiryFilter;
    window.location.href = url;
});
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if(e.key === 'Enter') document.getElementById('applyFilters').click();
});
</script>

<?php require_once '../../includes/footer.php'; ?>