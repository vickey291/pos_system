<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get medicine details
$query = "SELECT * FROM medicines WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$medicine = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$medicine) {
    $_SESSION['error'] = "Medicine not found!";
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $quantity = $_POST['quantity'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    
    if($action == 'dispose_all') {
        $quantity = $medicine['stock_quantity'];
    }
    
    if($quantity > 0 && $quantity <= $medicine['stock_quantity']) {
        $new_quantity = $medicine['stock_quantity'] - $quantity;
        
        $updateQuery = "UPDATE medicines SET stock_quantity = :new_qty WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':new_qty', $new_quantity);
        $updateStmt->bindParam(':id', $id);
        
        if($updateStmt->execute()) {
            // Log disposal (you can create a disposal_logs table later)
            $_SESSION['success'] = "Successfully disposed $quantity units of " . $medicine['name'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Error disposing medicine!";
        }
    } else {
        $error = "Invalid quantity!";
    }
}

require_once '../../includes/header.php';
?>

<style>
    .dispose-container {
        max-width: 500px;
        margin: 0 auto;
    }
    
    .dispose-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .dispose-header {
        text-align: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .dispose-header i {
        font-size: 50px;
        color: #ef4444;
        margin-bottom: 10px;
    }
    
    .dispose-header h2 {
        font-size: 22px;
        color: #1e293b;
    }
    
    .medicine-info {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .medicine-info h4 {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 10px;
    }
    
    .medicine-info p {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin: 5px 0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        font-size: 13px;
        color: #334155;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }
    
    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-dispose {
        background: #ef4444;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        flex: 1;
    }
    
    .btn-dispose:hover {
        background: #dc2626;
    }
    
    .btn-cancel {
        background: #64748b;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        text-decoration: none;
        text-align: center;
        flex: 1;
    }
    
    .btn-cancel:hover {
        background: #475569;
    }
    
    .warning-text {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px;
        border-radius: 8px;
        font-size: 12px;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .dispose-container {
            margin: 0;
        }
        
        .btn-group {
            flex-direction: column;
        }
    }
</style>

<div class="dispose-container">
    <div class="dispose-card">
        <div class="dispose-header">
            <i class="fas fa-trash-alt"></i>
            <h2>Dispose Medicine</h2>
            <p style="color: #64748b; font-size: 13px; margin-top: 5px;">Record expired or damaged medicine disposal</p>
        </div>
        
        <?php if($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="medicine-info">
            <h4>Medicine Information</h4>
            <p><strong><?php echo htmlspecialchars($medicine['name']); ?></strong></p>
            <p style="font-size: 13px; color: #64748b;">Code: <?php echo $medicine['medicine_code']; ?> | Batch: <?php echo $medicine['batch_number']; ?></p>
            <p style="font-size: 13px; color: #64748b;">Expiry Date: <?php echo date('d-m-Y', strtotime($medicine['expiry_date'])); ?></p>
            <p style="font-size: 13px; color: #64748b;">Current Stock: <strong style="color: #ef4444;"><?php echo $medicine['stock_quantity']; ?> units</strong></p>
        </div>
        
        <div class="warning-text">
            <i class="fas fa-exclamation-triangle"></i> 
            Warning: This action cannot be undone. Dispose only expired or damaged medicines.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Select Disposal Type</label>
                <select name="action" id="actionSelect" required>
                    <option value="dispose_all">Dispose All Stock (<?php echo $medicine['stock_quantity']; ?> units)</option>
                    <option value="dispose_partial">Dispose Partial Quantity</option>
                </select>
            </div>
            
            <div class="form-group" id="quantityGroup" style="display: none;">
                <label>Quantity to Dispose</label>
                <input type="number" name="quantity" min="1" max="<?php echo $medicine['stock_quantity']; ?>" placeholder="Enter quantity">
                <small style="color: #64748b;">Max: <?php echo $medicine['stock_quantity']; ?> units</small>
            </div>
            
            <div class="form-group">
                <label>Reason for Disposal</label>
                <textarea name="reason" rows="3" placeholder="e.g., Expired, Damaged, Near Expiry..."></textarea>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-dispose" onclick="return confirm('Are you sure you want to dispose this medicine?');">
                    <i class="fas fa-trash-alt"></i> Confirm Disposal
                </button>
                <a href="index.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    const actionSelect = document.getElementById('actionSelect');
    const quantityGroup = document.getElementById('quantityGroup');
    
    actionSelect.addEventListener('change', function() {
        if(this.value === 'dispose_partial') {
            quantityGroup.style.display = 'block';
        } else {
            quantityGroup.style.display = 'none';
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>