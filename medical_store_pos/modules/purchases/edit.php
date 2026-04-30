<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get purchase details
$query = "SELECT p.*, s.name as supplier_name 
          FROM purchases p 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$purchase) {
    header("Location: index.php");
    exit();
}

// Get purchase items
$query = "SELECT pi.*, m.name, m.medicine_code 
          FROM purchase_items pi 
          JOIN medicines m ON pi.medicine_id = m.id 
          WHERE pi.purchase_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $purchase_date = $_POST['purchase_date'];
    $paid_amount = $_POST['paid_amount'];
    $status = $_POST['status'];
    $items_data = json_decode($_POST['items'], true);
    
    // Calculate total amount from items
    $total_amount = 0;
    foreach($items_data as $item) {
        $total_amount += $item['total'];
    }
    
    $due_amount = $total_amount - $paid_amount;
    
    try {
        $db->beginTransaction();
        
        // First, revert stock quantities for old items
        foreach($items as $old_item) {
            $revertQuery = "UPDATE medicines SET stock_quantity = stock_quantity - :qty WHERE id = :id";
            $revertStmt = $db->prepare($revertQuery);
            $revertStmt->bindParam(':qty', $old_item['quantity']);
            $revertStmt->bindParam(':id', $old_item['medicine_id']);
            $revertStmt->execute();
        }
        
        // Delete old purchase items
        $delQuery = "DELETE FROM purchase_items WHERE purchase_id = :id";
        $delStmt = $db->prepare($delQuery);
        $delStmt->bindParam(':id', $id);
        $delStmt->execute();
        
        // Update purchase
        $updateQuery = "UPDATE purchases SET 
                        supplier_id = :supplier,
                        purchase_date = :date,
                        total_amount = :total,
                        paid_amount = :paid,
                        due_amount = :due,
                        status = :status
                        WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':supplier', $supplier_id);
        $updateStmt->bindParam(':date', $purchase_date);
        $updateStmt->bindParam(':total', $total_amount);
        $updateStmt->bindParam(':paid', $paid_amount);
        $updateStmt->bindParam(':due', $due_amount);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':id', $id);
        $updateStmt->execute();
        
        // Insert new purchase items and update stock
        foreach($items_data as $item) {
            $itemQuery = "INSERT INTO purchase_items (purchase_id, medicine_id, quantity, purchase_price, total) 
                          VALUES (:purchase_id, :medicine_id, :quantity, :price, :total)";
            $itemStmt = $db->prepare($itemQuery);
            $itemStmt->bindParam(':purchase_id', $id);
            $itemStmt->bindParam(':medicine_id', $item['medicine_id']);
            $itemStmt->bindParam(':quantity', $item['quantity']);
            $itemStmt->bindParam(':price', $item['price']);
            $itemStmt->bindParam(':total', $item['total']);
            $itemStmt->execute();
            
            // Update stock
            $stockQuery = "UPDATE medicines SET stock_quantity = stock_quantity + :qty WHERE id = :id";
            $stockStmt = $db->prepare($stockQuery);
            $stockStmt->bindParam(':qty', $item['quantity']);
            $stockStmt->bindParam(':id', $item['medicine_id']);
            $stockStmt->execute();
        }
        
        $db->commit();
        $_SESSION['success'] = "Purchase order updated successfully!";
        header("Location: index.php");
        exit();
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = "Error updating purchase order: " . $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<style>
    .edit-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .form-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .form-header {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .form-header h2 {
        font-size: 22px;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .invoice-badge {
        background: #667eea;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
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
    
    .form-group input, .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }
    
    /* Items Table */
    .items-section {
        margin-top: 20px;
    }
    
    .items-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .items-header h3 {
        font-size: 18px;
        color: #1e293b;
    }
    
    .add-item-row {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        align-items: flex-end;
    }
    
    .add-item-row select {
        flex: 2;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }
    
    .add-item-row input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }
    
    .btn-add {
        background: #10b981;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
    }
    
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .items-table th, .items-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .items-table th {
        background: #f8fafc;
        font-weight: 600;
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
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
        font-size: 18px;
        font-weight: bold;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        margin-top: 20px;
    }
    
    .btn-cancel {
        background: #64748b;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #ef4444;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .add-item-row {
            flex-direction: column;
        }
        
        .add-item-row select, .add-item-row input {
            width: 100%;
        }
        
        .items-table {
            font-size: 12px;
        }
    }
</style>

<div class="edit-container">
    <div class="form-card">
        <div class="form-header">
            <h2>
                <i class="fas fa-edit"></i> Edit Purchase Order
            </h2>
            <div class="invoice-badge">
                <?php echo htmlspecialchars($purchase['invoice_number']); ?>
            </div>
        </div>
        
        <?php if($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="purchaseForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Supplier *</label>
                    <select name="supplier_id" required>
                        <option value="">Select Supplier</option>
                        <?php foreach($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>" <?php echo $supplier['id'] == $purchase['supplier_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Purchase Date *</label>
                    <input type="date" name="purchase_date" value="<?php echo $purchase['purchase_date']; ?>" required>
                </div>
            </div>
            
            <!-- Items Section -->
            <div class="items-section">
                <div class="items-header">
                    <h3><i class="fas fa-boxes"></i> Purchase Items</h3>
                </div>
                
                <div class="add-item-row">
                    <select id="medicine_select">
                        <option value="">Select Medicine</option>
                        <?php foreach($medicines as $medicine): ?>
                        <option value="<?php echo $medicine['id']; ?>" 
                                data-price="<?php echo $medicine['purchase_price']; ?>"
                                data-name="<?php echo htmlspecialchars($medicine['name']); ?>">
                            <?php echo htmlspecialchars($medicine['name']); ?> - ₨ <?php echo $medicine['purchase_price']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" id="quantity" placeholder="Quantity" min="1">
                    <button type="button" class="btn-add" onclick="addItem()">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Quantity</th>
                            <th>Price (₨)</th>
                            <th>Total (₨)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="items_body">
                        <?php foreach($items as $item): ?>
                        <tr data-id="<?php echo $item['medicine_id']; ?>" data-price="<?php echo $item['purchase_price']; ?>">
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><input type="number" class="item-qty" value="<?php echo $item['quantity']; ?>" min="1" style="width: 70px;" onchange="updateRowTotal(this)"></td>
                            <td class="item-price"><?php echo number_format($item['purchase_price'], 2); ?></td>
                            <td class="item-total"><?php echo number_format($item['total'], 2); ?></td>
                            <td><button type="button" class="btn-remove" onclick="removeRow(this)">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="total-display">
                    Total Amount: ₨ <span id="total_amount"><?php echo number_format($purchase['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Paid Amount (₨)</label>
                    <input type="number" name="paid_amount" id="paid_amount" step="0.01" value="<?php echo $purchase['paid_amount']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Due Amount (₨)</label>
                    <input type="text" id="due_amount" readonly style="background: #f5f5f5;">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="pending" <?php echo $purchase['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $purchase['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $purchase['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            
            <input type="hidden" name="items" id="items_json">
            
            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Update Purchase Order
                </button>
                <a href="index.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    let items = [];
    
    // Initialize items from existing rows
    function initItems() {
        const rows = document.querySelectorAll('#items_body tr');
        items = [];
        rows.forEach(row => {
            const medicineId = row.getAttribute('data-id');
            const name = row.cells[0].innerText;
            const quantity = parseInt(row.querySelector('.item-qty').value);
            const price = parseFloat(row.getAttribute('data-price'));
            const total = quantity * price;
            
            items.push({
                medicine_id: medicineId,
                name: name,
                quantity: quantity,
                price: price,
                total: total
            });
        });
        updateTotal();
    }
    
    function addItem() {
        const select = document.getElementById('medicine_select');
        const quantity = document.getElementById('quantity').value;
        const medicineId = select.value;
        const medicineName = select.options[select.selectedIndex].getAttribute('data-name');
        const price = parseFloat(select.options[select.selectedIndex].getAttribute('data-price'));
        
        if(!medicineId || !quantity || quantity <= 0) {
            alert('Please select medicine and enter valid quantity');
            return;
        }
        
        // Check if item already exists
        const existingIndex = items.findIndex(item => item.medicine_id == medicineId);
        if(existingIndex >= 0) {
            items[existingIndex].quantity += parseInt(quantity);
            items[existingIndex].total = items[existingIndex].quantity * items[existingIndex].price;
        } else {
            items.push({
                medicine_id: medicineId,
                name: medicineName,
                quantity: parseInt(quantity),
                price: price,
                total: parseInt(quantity) * price
            });
        }
        
        updateTable();
        document.getElementById('quantity').value = '';
        select.value = '';
    }
    
    function updateTable() {
        const tbody = document.getElementById('items_body');
        tbody.innerHTML = '';
        
        items.forEach((item, index) => {
            tbody.innerHTML += `
                <tr data-id="${item.medicine_id}" data-price="${item.price}">
                    <td>${item.name}</td>
                    <td><input type="number" class="item-qty" value="${item.quantity}" min="1" style="width: 70px;" onchange="updateItemQty(${index}, this.value)"></td>
                    <td class="item-price">${item.price.toFixed(2)}</td>
                    <td class="item-total">${item.total.toFixed(2)}</td>
                    <td><button type="button" class="btn-remove" onclick="removeItem(${index})">Remove</button></td>
                </tr>
            `;
        });
        
        updateTotal();
    }
    
    function updateItemQty(index, qty) {
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
    
    function updateRowTotal(input) {
        const row = input.closest('tr');
        const qty = parseInt(input.value);
        const price = parseFloat(row.querySelector('.item-price').innerText);
        const total = qty * price;
        row.querySelector('.item-total').innerText = total.toFixed(2);
        
        // Update items array
        const medicineId = row.getAttribute('data-id');
        const itemIndex = items.findIndex(i => i.medicine_id == medicineId);
        if(itemIndex >= 0) {
            items[itemIndex].quantity = qty;
            items[itemIndex].total = total;
        }
        updateTotal();
    }
    
    function removeRow(btn) {
        const row = btn.closest('tr');
        const medicineId = row.getAttribute('data-id');
        const itemIndex = items.findIndex(i => i.medicine_id == medicineId);
        if(itemIndex >= 0) {
            items.splice(itemIndex, 1);
        }
        row.remove();
        updateTotal();
    }
    
    function updateTotal() {
        let total = 0;
        items.forEach(item => {
            total += item.total;
        });
        document.getElementById('total_amount').innerText = total.toFixed(2);
        document.getElementById('items_json').value = JSON.stringify(items);
        updateDue();
    }
    
    function updateDue() {
        const total = parseFloat(document.getElementById('total_amount').innerText) || 0;
        const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
        const due = total - paid;
        document.getElementById('due_amount').value = due.toFixed(2);
    }
    
    document.getElementById('paid_amount').addEventListener('input', updateDue);
    
    // Initialize on load
    initItems();
</script>

<?php require_once '../../includes/footer.php'; ?>