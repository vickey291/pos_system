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

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $medicine_code = $_POST['medicine_code'];
    $name = $_POST['name'];
    $company = $_POST['company'];
    $batch_number = $_POST['batch_number'];
    $expiry_date = $_POST['expiry_date'];
    $purchase_price = $_POST['purchase_price'];
    $sale_price = $_POST['sale_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $min_stock_level = $_POST['min_stock_level'];
    
    $query = "UPDATE medicines SET 
              medicine_code = :code, name = :name, company = :company, batch_number = :batch,
              expiry_date = :expiry, purchase_price = :purchase, sale_price = :sale,
              stock_quantity = :stock, min_stock_level = :min_stock WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':code', $medicine_code);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':company', $company);
    $stmt->bindParam(':batch', $batch_number);
    $stmt->bindParam(':expiry', $expiry_date);
    $stmt->bindParam(':purchase', $purchase_price);
    $stmt->bindParam(':sale', $sale_price);
    $stmt->bindParam(':stock', $stock_quantity);
    $stmt->bindParam(':min_stock', $min_stock_level);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        $_SESSION['success'] = "Medicine updated successfully!";
        header("Location: index.php");
        exit();
    } else {
        $error = "Error updating medicine!";
    }
}
?>

<div style="max-width: 700px; margin: 0 auto; background: white; border-radius: 16px; padding: 25px;">
    <h2 style="margin-bottom: 20px; font-size: 22px;"><i class="fas fa-edit"></i> Edit Medicine</h2>
    
    <?php if($error): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 15px;"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Medicine Code *</label>
                <input type="text" name="medicine_code" value="<?php echo htmlspecialchars($medicine['medicine_code']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Medicine Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($medicine['name']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Company</label>
                <input type="text" name="company" value="<?php echo htmlspecialchars($medicine['company']); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Batch Number</label>
                <input type="text" name="batch_number" value="<?php echo htmlspecialchars($medicine['batch_number']); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Expiry Date *</label>
                <input type="date" name="expiry_date" value="<?php echo $medicine['expiry_date']; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Min Stock Level</label>
                <input type="number" name="min_stock_level" value="<?php echo $medicine['min_stock_level']; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Purchase Price (₨)</label>
                <input type="number" name="purchase_price" step="0.01" value="<?php echo $medicine['purchase_price']; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Sale Price (₨) *</label>
                <input type="number" name="sale_price" step="0.01" value="<?php echo $medicine['sale_price']; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Current Stock *</label>
                <input type="number" name="stock_quantity" value="<?php echo $medicine['stock_quantity']; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 25px;">
            <button type="submit" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 10px 25px; border-radius: 8px; cursor: pointer;">Update Medicine</button>
            <a href="index.php" style="background: #6c757d; color: white; padding: 10px 25px; border-radius: 8px; text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>