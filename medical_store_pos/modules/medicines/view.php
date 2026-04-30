<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

$query = "SELECT * FROM medicines WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$medicine = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$medicine) {
    header("Location: index.php");
    exit();
}

// Get sales history for this medicine
$salesQuery = "SELECT SUM(si.quantity) as total_sold, SUM(si.total) as total_revenue 
               FROM sale_items si 
               WHERE si.medicine_id = :id";
$salesStmt = $db->prepare($salesQuery);
$salesStmt->bindParam(':id', $id);
$salesStmt->execute();
$salesStats = $salesStmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fas fa-eye"></i> Medicine Details</h1>
    <p>View complete information about <?php echo htmlspecialchars($medicine['name']); ?></p>
</div>

<div style="background: white; border-radius: 20px; padding: 30px; max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="color: #1e293b;"><?php echo htmlspecialchars($medicine['name']); ?></h2>
        <div>
            <a href="edit.php?id=<?php echo $medicine['id']; ?>" class="btn btn-primary">Edit</a>
            <a href="index.php" class="btn btn-info">Back</a>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
        <div><strong>Medicine Code:</strong><br><?php echo $medicine['medicine_code']; ?></div>
        <div><strong>Company:</strong><br><?php echo $medicine['company'] ?: 'N/A'; ?></div>
        <div><strong>Batch Number:</strong><br><?php echo $medicine['batch_number'] ?: 'N/A'; ?></div>
        <div><strong>Expiry Date:</strong><br><?php echo date('d-m-Y', strtotime($medicine['expiry_date'])); ?></div>
        <div><strong>Purchase Price:</strong><br>₨ <?php echo number_format($medicine['purchase_price'], 2); ?></div>
        <div><strong>Sale Price:</strong><br>₨ <?php echo number_format($medicine['sale_price'], 2); ?></div>
        <div><strong>Current Stock:</strong><br><?php echo $medicine['stock_quantity']; ?> units</div>
        <div><strong>Min Stock Level:</strong><br><?php echo $medicine['min_stock_level']; ?> units</div>
        <div><strong>Total Sold:</strong><br><?php echo $salesStats['total_sold'] ?? 0; ?> units</div>
        <div><strong>Total Revenue:</strong><br>₨ <?php echo number_format($salesStats['total_revenue'] ?? 0, 2); ?></div>
        <div><strong>Added On:</strong><br><?php echo date('d-m-Y H:i', strtotime($medicine['created_at'])); ?></div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>