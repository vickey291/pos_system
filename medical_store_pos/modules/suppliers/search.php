<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Search suppliers
$query = "SELECT * FROM suppliers WHERE 
          name LIKE :search OR 
          supplier_code LIKE :search OR 
          phone LIKE :search OR 
          contact_person LIKE :search 
          ORDER BY name";
$stmt = $db->prepare($query);
$searchTerm = "%$search%";
$stmt->bindParam(':search', $searchTerm);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_results = count($suppliers);

require_once '../../includes/header.php';
?>

<style>
    .search-container {
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
    
    .search-summary {
        background: white;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .search-term {
        background: #667eea;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 13px;
    }
    
    .btn {
        padding: 8px 18px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
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
        padding: 14px 12px;
        text-align: left;
        font-size: 12px;
    }
    
    .data-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    
    .data-table tbody tr:hover {
        background: #f8fafc;
    }
    
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
    }
    
    .btn-sm-primary {
        background: #3b82f6;
        color: white;
    }
    
    .btn-sm-info {
        background: #8b5cf6;
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
        .search-container {
            padding: 15px;
        }
        
        .search-summary {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="search-container">
    <div class="page-header">
        <h1><i class="fas fa-search"></i> Search Results</h1>
        <p>Find suppliers by name, code, phone or contact person</p>
    </div>
    
    <div class="search-summary">
        <div>
            <strong>Search Results for:</strong>
            <span class="search-term">"<?php echo htmlspecialchars($search); ?>"</span>
        </div>
        <div>
            Found <strong><?php echo $total_results; ?></strong> supplier(s)
        </div>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to All Suppliers
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Supplier Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($total_results > 0): ?>
                    <?php foreach($suppliers as $supplier): 
                        $hasPurchases = false;
                        $checkQuery = "SELECT COUNT(*) as count FROM purchases WHERE supplier_id = :id";
                        $checkStmt = $db->prepare($checkQuery);
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
                                <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($supplier['contact_person'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['email'] ?: 'N/A'); ?></td>
                        <td>
                            <span class="badge <?php echo $hasPurchases ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $hasPurchases ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="btn-sm btn-sm-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="purchases.php?id=<?php echo $supplier['id']; ?>" class="btn-sm btn-sm-info">
                                    <i class="fas fa-history"></i> History
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h3>No Suppliers Found</h3>
                                <p>No suppliers match your search term "<?php echo htmlspecialchars($search); ?>"</p>
                                <a href="add.php" class="btn btn-primary" style="margin-top: 15px;">
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

<?php require_once '../../includes/footer.php'; ?>