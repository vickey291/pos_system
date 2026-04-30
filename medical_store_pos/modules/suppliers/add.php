<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_code = $_POST['supplier_code'];
    $name = $_POST['name'];
    $contact_person = $_POST['contact_person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    
    // Check if supplier code already exists
    $checkQuery = "SELECT COUNT(*) as count FROM suppliers WHERE supplier_code = :code";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':code', $supplier_code);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0) {
        $error = "Supplier code already exists! Please use a different code.";
    } else {
        $query = "INSERT INTO suppliers (supplier_code, name, contact_person, phone, email, address) 
                  VALUES (:code, :name, :contact, :phone, :email, :address)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $supplier_code);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact', $contact_person);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);
        
        if($stmt->execute()) {
            $_SESSION['success'] = "Supplier added successfully!";
            header("Location: index.php");
            exit();
        } else {
            $error = "Error adding supplier!";
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
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .form-header {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .form-header h2 {
        font-size: 22px;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-header p {
        color: #64748b;
        font-size: 13px;
        margin-top: 5px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        font-size: 13px;
        color: #334155;
    }
    
    .form-group label .required {
        color: #ef4444;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }
    
    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #94a3b8;
    }
    
    textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    
    .btn {
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.3);
    }
    
    .btn-outline {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    
    .btn-outline:hover {
        background: #e2e8f0;
    }
    
    .alert {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }
    
    .info-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 5px;
    }
    
    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
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
    }
</style>

<div class="form-container">
    <div class="form-header">
        <h2><i class="fas fa-truck"></i> Add New Supplier</h2>
        <p>Fill in the supplier details below to add them to your system</p>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Supplier Code <span class="required">*</span></label>
                <input type="text" name="supplier_code" required placeholder="e.g., SUP001" value="<?php echo isset($_POST['supplier_code']) ? htmlspecialchars($_POST['supplier_code']) : ''; ?>">
                <div class="info-text">Unique code for this supplier</div>
            </div>
            
            <div class="form-group">
                <label>Supplier Name <span class="required">*</span></label>
                <input type="text" name="name" required placeholder="Enter supplier name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" name="contact_person" placeholder="Contact person name" value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Phone Number <span class="required">*</span></label>
                <input type="text" name="phone" required placeholder="Phone number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Email address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" placeholder="Full address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Supplier
            </button>
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>