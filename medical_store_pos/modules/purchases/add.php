<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Get all suppliers
$query = "SELECT * FROM suppliers ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all medicines
$query = "SELECT * FROM medicines ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $purchase_date = $_POST['purchase_date'];
    $items = json_decode($_POST['items'], true);
    $total_amount = $_POST['total_amount'];
    $paid_amount = $_POST['paid_amount'];
    $due_amount = $total_amount - $paid_amount;
    $status = $due_amount == 0 ? 'completed' : 'pending';
    
    $invoice_number = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
    
    try {
        $db->beginTransaction();
        
        $query = "INSERT INTO purchases (invoice_number, supplier_id, purchase_date, total_amount, paid_amount, due_amount, status) 
                  VALUES (:invoice, :supplier, :date, :total, :paid, :due, :status)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':invoice', $invoice_number);
        $stmt->bindParam(':supplier', $supplier_id);
        $stmt->bindParam(':date', $purchase_date);
        $stmt->bindParam(':total', $total_amount);
        $stmt->bindParam(':paid', $paid_amount);
        $stmt->bindParam(':due', $due_amount);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        $purchase_id = $db->lastInsertId();
        
        foreach($items as $item) {
            $query = "INSERT INTO purchase_items (purchase_id, medicine_id, quantity, purchase_price, total) 
                      VALUES (:purchase_id, :medicine_id, :quantity, :price, :total)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':purchase_id', $purchase_id);
            $stmt->bindParam(':medicine_id', $item['medicine_id']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':price', $item['price']);
            $stmt->bindParam(':total', $item['total']);
            $stmt->execute();
            
            $query = "UPDATE medicines SET stock_quantity = stock_quantity + :qty WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':qty', $item['quantity']);
            $stmt->bindParam(':id', $item['medicine_id']);
            $stmt->execute();
        }
        
        $db->commit();
        $_SESSION['success'] = "Purchase order created successfully! Invoice: $invoice_number";
        header("Location: index.php");
        exit();
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = "Error creating purchase order: " . $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<style>
    .form-container {
        max-width: 900px;
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
    }
    
    .form-group input, .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .item-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }
    
    .item-row select { flex: 2; }
    .item-row input { flex: 1; }
    
    .btn-add-item {
        background: #10b981;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
    }
    
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    
    .items-table th, .items-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .btn-remove {
        background: #ef4444;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .total-display {
        text-align: right;
        font-size: 18px;
        font-weight: bold;
        margin: 15px 0;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .item-row {
            flex-direction: column;
        }
        .item-row select, .item-row input {
            width: 100%;
        }
    }
</style>

<div class="form-container">
    <div class="form-header">
        <h2><i class="fas fa-plus"></i> New Purchase Order</h2>
        <p>Create a new purchase order from supplier</p>
    </div>
    
    <?php if($error): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" id="purchaseForm">
        <div class="form-row">
            <div class="form-group">
                <label>Supplier *</label>
                <select name="supplier_id" required>
                    <option value="">Select Supplier</option>
                    <?php foreach($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Purchase Date *</label>
                <input type="date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>
        
        <h4 style="margin: 20px 0 10px;">Add Medicines</h4>
        <div id="items-container">
            <div class="item-row">
                <select class="medicine-select">
                    <option value="">Select Medicine</option>
                    <?php foreach($medicines as $medicine): ?>
                    <option value="<?php echo $medicine['id']; ?>" data-price="<?php echo $medicine['purchase_price']; ?>" data-name="<?php echo htmlspecialchars($medicine['name']); ?>">
                        <?php echo htmlspecialchars($medicine['name']); ?> - ₨ <?php echo $medicine['purchase_price']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" class="quantity" placeholder="Quantity" min="1">
                <button type="button" class="btn-add-item" onclick="addItem(this)">Add</button>
            </div>
        </div>
        
        <table class="items-table" id="items-table">
            <thead>
                <tr><th>Medicine</th><th>Quantity</th><th>Price</th><th>Total</th><th>Action</th></tr>
            </thead>
            <tbody id="items-body"></tbody>
        </table>
        <div class="total-display">
            Total Amount: ₨ <span id="total-amount">0.00</span>
        </div>
        <input type="hidden" name="items" id="items-json">
        <input type="hidden" name="total_amount" id="total-amount-input">
        
        <div class="form-row">
            <div class="form-group">
                <label>Paid Amount</label>
                <input type="number" id="paid-amount" name="paid_amount" step="0.01" value="0">
            </div>
            <div class="form-group">
                <label>Due Amount</label>
                <input type="text" id="due-amount" readonly style="background: #f5f5f5;">
            </div>
        </div>
        
        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Create Purchase Order</button>
        <a href="index.php" class="btn" style="background: #64748b; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-left: 10px;">Cancel</a>
    </form>
</div>

<script>
let items = [];
let total = 0;

function addItem(btn) {
    let row = btn.parentElement;
    let select = row.querySelector('.medicine-select');
    let quantity = row.querySelector('.quantity').value;
    let medicineId = select.value;
    let medicineName = select.options[select.selectedIndex].getAttribute('data-name');
    let price = select.options[select.selectedIndex].getAttribute('data-price');
    
    if(!medicineId || !quantity || quantity <= 0) {
        alert('Please select medicine and enter valid quantity');
        return;
    }
    
    let existing = items.find(item => item.medicine_id == medicineId);
    if(existing) {
        existing.quantity = parseInt(existing.quantity) + parseInt(quantity);
        existing.total = existing.quantity * existing.price;
    } else {
        items.push({
            medicine_id: medicineId,
            name: medicineName,
            quantity: parseInt(quantity),
            price: parseFloat(price),
            total: parseInt(quantity) * parseFloat(price)
        });
    }
    
    updateTable();
    row.querySelector('.quantity').value = '';
    select.value = '';
}

function updateTable() {
    let tbody = document.getElementById('items-body');
    tbody.innerHTML = '';
    total = 0;
    
    items.forEach((item, index) => {
        total += item.total;
        tbody.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td><input type="number" value="${item.quantity}" onchange="updateQty(${index}, this.value)" style="width: 70px;"></td>
                <td>₨ ${item.price.toFixed(2)}</td>
                <td>₨ ${item.total.toFixed(2)}</td>
                <td><button type="button" class="btn-remove" onclick="removeItem(${index})">Remove</button></td>
            </tr>
        `;
    });
    
    document.getElementById('total-amount').innerText = total.toFixed(2);
    document.getElementById('total-amount-input').value = total;
    document.getElementById('items-json').value = JSON.stringify(items);
    updateDue();
}

function updateQty(index, qty) {
    qty = parseInt(qty);
    if(qty > 0) {
        items[index].quantity = qty;
        items[index].total = qty * items[index].price;
        updateTable();
    }
}

function removeItem(index) {
    items.splice(index, 1);
    updateTable();
}

function updateDue() {
    let paid = parseFloat(document.getElementById('paid-amount').value) || 0;
    let due = total - paid;
    document.getElementById('due-amount').value = due.toFixed(2);
}

document.getElementById('paid-amount').addEventListener('input', updateDue);
</script>

<?php require_once '../../includes/footer.php'; ?>