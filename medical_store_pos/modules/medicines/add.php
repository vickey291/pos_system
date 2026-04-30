<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $medicine_code = strtoupper($_POST['medicine_code']);
    $name = $_POST['name'];
    $company = $_POST['company'];
    $batch_number = strtoupper($_POST['batch_number']);
    $expiry_date = $_POST['expiry_date'];
    $purchase_price = $_POST['purchase_price'];
    $sale_price = $_POST['sale_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $min_stock_level = $_POST['min_stock_level'];
    
    // Check if medicine code already exists
    $checkQuery = "SELECT COUNT(*) as count FROM medicines WHERE medicine_code = :code";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':code', $medicine_code);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0) {
        $error = "Medicine code already exists! Please use a different code.";
    } else {
        $query = "INSERT INTO medicines (medicine_code, name, company, batch_number, expiry_date, purchase_price, sale_price, stock_quantity, min_stock_level) 
                  VALUES (:code, :name, :company, :batch, :expiry, :purchase, :sale, :stock, :min_stock)";
        
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
        
        if($stmt->execute()) {
            $_SESSION['success'] = "Medicine added successfully!";
            header("Location: index.php");
            exit();
        } else {
            $error = "Error adding medicine!";
        }
    }
}

require_once '../../includes/header.php';
?>

<style>
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .form-header {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
        text-align: center;
    }
    
    .form-header h2 {
        font-size: 24px;
        color: #1e293b;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    
    .form-header p {
        color: #64748b;
        font-size: 14px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 10px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 13px;
        color: #334155;
    }
    
    .form-group label .required {
        color: #ef4444;
        margin-left: 3px;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }
    
    .form-group input::placeholder {
        color: #94a3b8;
    }
    
    .info-text {
        font-size: 11px;
        color: #64748b;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .info-text i {
        font-size: 10px;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    
    .btn {
        padding: 12px 28px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        font-family: inherit;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        box-shadow: 0 2px 10px rgba(102,126,234,0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102,126,234,0.4);
    }
    
    .btn-outline {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    
    .btn-outline:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-left: 4px solid #ef4444;
    }
    
    /* Price Input Group */
    .price-group {
        position: relative;
    }
    
    .price-group input {
        padding-left: 35px;
    }
    
    .price-symbol {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
            margin: 0 10px;
        }
        
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            justify-content: center;
        }
        
        .form-header h2 {
            font-size: 20px;
        }
    }
</style>

<div class="form-container">
    <div class="form-header">
        <h2>
            <i class="fas fa-pills" style="color: #667eea;"></i>
            Add New Medicine
        </h2>
        <p>Fill in the medicine details below to add to inventory</p>
    </div>
    
    <?php if($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="medicineForm">
        <div class="form-row">
            <div class="form-group">
                <label>Medicine Code <span class="required">*</span></label>
                <input type="text" name="medicine_code" required placeholder="e.g., MED001" value="<?php echo isset($_POST['medicine_code']) ? htmlspecialchars($_POST['medicine_code']) : ''; ?>">
                <div class="info-text">
                    <i class="fas fa-info-circle"></i> Unique code for this medicine (auto converts to uppercase)
                </div>
            </div>
            
            <div class="form-group">
                <label>Medicine Name <span class="required">*</span></label>
                <input type="text" name="name" required placeholder="Enter medicine name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                <div class="info-text">
                    <i class="fas fa-info-circle"></i> Full generic/brand name of the medicine
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Company / Manufacturer</label>
                <input type="text" name="company" placeholder="e.g., GSK, Pfizer, Sanofi" value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>">
                <div class="info-text">
                    <i class="fas fa-building"></i> Pharmaceutical company name
                </div>
            </div>
            
            <div class="form-group">
                <label>Batch Number</label>
                <input type="text" name="batch_number" placeholder="e.g., BATCH001" value="<?php echo isset($_POST['batch_number']) ? htmlspecialchars($_POST['batch_number']) : ''; ?>">
                <div class="info-text">
                    <i class="fas fa-barcode"></i> Manufacturer batch/lot number
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Expiry Date <span class="required">*</span></label>
                <input type="date" name="expiry_date" required value="<?php echo isset($_POST['expiry_date']) ? $_POST['expiry_date'] : ''; ?>">
                <div class="info-text">
                    <i class="fas fa-calendar-alt"></i> Date when medicine expires
                </div>
            </div>
            
            <div class="form-group">
                <label>Min Stock Level <span class="required">*</span></label>
                <input type="number" name="min_stock_level" value="<?php echo isset($_POST['min_stock_level']) ? $_POST['min_stock_level'] : '10'; ?>" required>
                <div class="info-text">
                    <i class="fas fa-bell"></i> Alert when stock falls below this level
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Purchase Price (Rs.) <span class="required">*</span></label>
                <div class="price-group">
                    <span class="price-symbol">₨</span>
                    <input type="number" name="purchase_price" step="0.01" required placeholder="0.00" value="<?php echo isset($_POST['purchase_price']) ? $_POST['purchase_price'] : ''; ?>">
                </div>
                <div class="info-text">
                    <i class="fas fa-tags"></i> Cost price from supplier
                </div>
            </div>
            
            <div class="form-group">
                <label>Sale Price (Rs.) <span class="required">*</span></label>
                <div class="price-group">
                    <span class="price-symbol">₨</span>
                    <input type="number" name="sale_price" step="0.01" required placeholder="0.00" value="<?php echo isset($_POST['sale_price']) ? $_POST['sale_price'] : ''; ?>">
                </div>
                <div class="info-text">
                    <i class="fas fa-tags"></i> Selling price to customers
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Initial Stock Quantity <span class="required">*</span></label>
            <input type="number" name="stock_quantity" required placeholder="0" value="<?php echo isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : ''; ?>">
            <div class="info-text">
                <i class="fas fa-boxes"></i> Number of units to add to inventory
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Medicine
            </button>
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
    // Auto convert medicine code to uppercase
    const medicineCodeInput = document.querySelector('input[name="medicine_code"]');
    if(medicineCodeInput) {
        medicineCodeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
    
    // Auto convert batch number to uppercase
    const batchInput = document.querySelector('input[name="batch_number"]');
    if(batchInput) {
        batchInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
    
    // Calculate profit margin (optional)
    const purchasePrice = document.querySelector('input[name="purchase_price"]');
    const salePrice = document.querySelector('input[name="sale_price"]');
    
    function showProfitHint() {
        if(purchasePrice.value && salePrice.value) {
            const purchase = parseFloat(purchasePrice.value);
            const sale = parseFloat(salePrice.value);
            const profit = sale - purchase;
            const margin = (profit / purchase) * 100;
            
            if(profit > 0) {
                // Could add a small hint, but keeping it simple
            }
        }
    }
    
    if(purchasePrice && salePrice) {
        purchasePrice.addEventListener('input', showProfitHint);
        salePrice.addEventListener('input', showProfitHint);
    }
</script>

<?php require_once '../../includes/footer.php'; ?>