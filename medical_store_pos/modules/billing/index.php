<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Search medicines
$search = isset($_GET['search']) ? $_GET['search'] : '';
$medicines = [];

if(!empty($search)) {
    $query = "SELECT * FROM medicines WHERE (name LIKE :search OR medicine_code LIKE :search) AND stock_quantity > 0 ORDER BY name LIMIT 10";
    $stmt = $db->prepare($query);
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get customers for dropdown
$query = "SELECT * FROM customers ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Billing - Medical Store POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Poppins', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Dashboard Layout */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #0f0f1f;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 5px;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.8;
            color: #a0a0a0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
        }

        .sidebar-menu li {
            margin: 0.5rem 1rem;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.85rem 1.2rem;
            color: #e0e0e0;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .sidebar-menu li a:hover {
            background: rgba(102,126,234,0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu li a i {
            width: 30px;
            font-size: 1.2rem;
            margin-right: 10px;
        }

        .sidebar-menu li.active a {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            width: calc(100% - 280px);
            transition: all 0.3s ease;
        }

        /* Top Header */
        .top-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 999;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #667eea;
        }

        .page-title h1 {
            font-size: 1.5rem;
            color: #333;
        }

        .page-title p {
            color: #666;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .user-details h4 {
            font-size: 0.95rem;
            color: #333;
        }

        .user-details p {
            font-size: 0.75rem;
            color: #666;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Billing Container */
        .billing-container {
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Billing Layout */
        .billing-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        /* Left Section */
        .billing-left {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        /* Search Section */
        .search-section {
            margin-bottom: 2rem;
        }

        .search-section h3 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-result-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-result-item:hover {
            background: #f8f9fa;
            padding-left: 1.5rem;
        }

        /* Cart Section */
        .cart-section h3 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-table-container {
            overflow-x: auto;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .cart-table th,
        .cart-table td {
            padding: 1rem;
            text-align: left;
        }

        .cart-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
        }

        .cart-table tbody tr:hover {
            background: #f8f9fa;
        }

        .cart-table input[type="number"] {
            width: 70px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-align: center;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        .cart-total {
            background: #f8f9fa;
            font-weight: bold;
        }

        .cart-total td {
            padding: 1rem;
        }

        /* Right Section */
        .billing-right {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .customer-section,
        .payment-section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .customer-section h3,
        .payment-section h3 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .customer-section select,
        .payment-section select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            background: white;
            cursor: pointer;
        }

        .payment-amount {
            margin-top: 1rem;
        }

        .payment-amount label {
            display: block;
            margin: 0.5rem 0 0.25rem;
            color: #666;
            font-size: 0.85rem;
        }

        .payment-amount input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
        }

        .change-amount {
            display: inline-block;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2ecc71;
            margin-top: 0.5rem;
        }

        .btn-complete,
        .btn-clear {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }

        .btn-complete {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,204,113,0.3);
        }

        .btn-clear {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-clear:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231,76,60,0.3);
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Print Options Modal */
        .print-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .print-modal-content {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.3s ease;
        }

        .print-modal-content h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .print-modal-content p {
            margin-bottom: 1.5rem;
            color: #666;
        }

        .print-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .print-buttons button {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-print {
            background: #3498db;
            color: white;
        }

        .btn-later {
            background: #95a5a6;
            color: white;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .billing-layout {
                grid-template-columns: 1fr;
            }
            
            .billing-right {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .customer-section,
            .payment-section {
                flex: 1;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .menu-toggle {
                display: block;
            }

            .billing-container {
                padding: 1rem;
            }
            
            .billing-right {
                flex-direction: column;
            }
            
            .cart-table {
                font-size: 0.85rem;
            }
            
            .cart-table th,
            .cart-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-hospital-user"></i> MediCare POS</h3>
                <p>Medical Store Management</p>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="../../index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="active">
                    <a href="index.php">
                        <i class="fas fa-cash-register"></i>
                        <span>Billing</span>
                    </a>
                </li>
                <li>
                    <a href="../medicines/index.php">
                        <i class="fas fa-pills"></i>
                        <span>Medicines</span>
                    </a>
                </li>
                <li>
                    <a href="../inventory/index.php">
                        <i class="fas fa-boxes"></i>
                        <span>Inventory</span>
                    </a>
                </li>
                <li>
                    <a href="../suppliers/index.php">
                        <i class="fas fa-truck"></i>
                        <span>Suppliers</span>
                    </a>
                </li>
                <li>
                    <a href="../customers/index.php">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li>
                    <a href="../reports/index.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li>
                    <a href="../../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-header">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-title">
                    <h1>Point of Sale (POS)</h1>
                    <p>Create new sales invoice</p>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['role'] ?? 'Staff'); ?></p>
                    </div>
                    <a href="../../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <div class="billing-container">
                <div class="billing-layout">
                    <!-- Left Section -->
                    <div class="billing-left">
                        <div class="search-section">
                            <h3><i class="fas fa-search"></i> Search Medicine</h3>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="medicine-search" placeholder="Search by name or code..." autocomplete="off">
                                <div id="search-results" class="search-results"></div>
                            </div>
                        </div>
                        
                        <div class="cart-section">
                            <h3><i class="fas fa-shopping-cart"></i> Current Bill</h3>
                            <div class="cart-table-container">
                                <table class="cart-table">
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Price (₨)</th>
                                            <th>Qty</th>
                                            <th>Total (₨)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-body">
                                        <tr>
                                            <td colspan="5" class="empty-cart">
                                                <i class="fas fa-shopping-basket"></i>
                                                <p>Cart is empty</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="cart-total">
                                            <td colspan="3"><strong>Subtotal:</strong></td>
                                            <td id="subtotal">₨ 0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr class="cart-total">
                                            <td colspan="3"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" id="discount" value="0" step="0.01" style="width: 100px;">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr class="cart-total">
                                            <td colspan="3"><strong>Total:</strong></td>
                                            <td id="total">₨ 0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Section -->
                    <div class="billing-right">
                        <div class="customer-section">
                            <h3><i class="fas fa-user"></i> Customer Details</h3>
                            <select id="customer-id">
                                <option value="">Walk-in Customer</option>
                                <?php foreach($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="payment-section">
                            <h3><i class="fas fa-credit-card"></i> Payment</h3>
                            <select id="payment-method">
                                <option value="cash">💵 Cash</option>
                                <option value="card">💳 Card</option>
                                <option value="online">📱 Online Transfer</option>
                            </select>
                            
                            <div class="payment-amount">
                                <label>💰 Paid Amount:</label>
                                <input type="number" id="paid-amount" value="0" step="0.01">
                                <label>🔄 Change:</label>
                                <div class="change-amount" id="change-amount">₨ 0.00</div>
                            </div>
                            
                            <button class="btn-complete" id="complete-sale">
                                <i class="fas fa-check-circle"></i> Complete Sale
                            </button>
                            <button class="btn-clear" id="clear-cart">
                                <i class="fas fa-trash-alt"></i> Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Print Modal -->
    <div id="printModal" class="print-modal">
        <div class="print-modal-content">
            <h3><i class="fas fa-print"></i> Print Receipt</h3>
            <p>Would you like to print the receipt?</p>
            <div class="print-buttons">
                <button class="btn-print" id="confirmPrint">
                    <i class="fas fa-print"></i> Yes, Print
                </button>
                <button class="btn-later" id="laterPrint">
                    <i class="fas fa-clock"></i> Later
                </button>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let subtotal = 0;
        let lastInvoiceNumber = null;

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        if(menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }
        
        // Close sidebar on outside click (mobile)
        document.addEventListener('click', (e) => {
            if(window.innerWidth <= 768) {
                if(sidebar && menuToggle && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Search functionality
        let searchTimeout;
        document.getElementById('medicine-search').addEventListener('input', function() {
            let search = this.value;
            clearTimeout(searchTimeout);
            
            if(search.length > 1) {
                searchTimeout = setTimeout(() => {
                    fetch(`search_medicine.php?search=${encodeURIComponent(search)}`)
                        .then(response => response.json())
                        .then(data => {
                            let resultsDiv = document.getElementById('search-results');
                            resultsDiv.innerHTML = '';
                            if(data.length > 0) {
                                data.forEach(medicine => {
                                    resultsDiv.innerHTML += `
                                        <div class="search-result-item" onclick="addToCart(${medicine.id}, '${medicine.name.replace(/'/g, "\\'")}', ${medicine.sale_price}, ${medicine.stock_quantity})">
                                            <strong>${medicine.name}</strong><br>
                                            <small>Code: ${medicine.medicine_code} | Price: ₨ ${medicine.sale_price} | Stock: ${medicine.stock_quantity}</small>
                                        </div>
                                    `;
                                });
                                resultsDiv.style.display = 'block';
                            } else {
                                resultsDiv.innerHTML = '<div class="search-result-item">No medicines found</div>';
                                resultsDiv.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }, 300);
            } else {
                document.getElementById('search-results').style.display = 'none';
            }
        });

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if(!e.target.closest('.search-box')) {
                document.getElementById('search-results').style.display = 'none';
            }
        });

        function addToCart(id, name, price, stock) {
            let existing = cart.find(item => item.id === id);
            if(existing) {
                if(existing.quantity < stock) {
                    existing.quantity++;
                    existing.total = existing.quantity * existing.price;
                    showNotification(`Added another ${name} to cart`, 'success');
                } else {
                    showNotification('Not enough stock available!', 'error');
                    return;
                }
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1,
                    total: price,
                    stock: stock
                });
                showNotification(`${name} added to cart`, 'success');
            }
            updateCartDisplay();
            document.getElementById('search-results').style.display = 'none';
            document.getElementById('medicine-search').value = '';
        }

        function updateCartDisplay() {
            let cartBody = document.getElementById('cart-body');
            cartBody.innerHTML = '';
            subtotal = 0;
            
            if(cart.length === 0) {
                cartBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="empty-cart">
                            <i class="fas fa-shopping-basket"></i>
                            <p>Cart is empty</p>
                        </td>
                    </tr>
                `;
            } else {
                cart.forEach((item, index) => {
                    subtotal += item.total;
                    cartBody.innerHTML += `
                        <tr>
                            <td><strong>${item.name}</strong><br><small>Stock: ${item.stock}</small></td>
                            <td>₨ ${item.price.toFixed(2)}</td>
                            <td>
                                <input type="number" value="${item.quantity}" min="1" max="${item.stock}" 
                                       onchange="updateQuantity(${index}, this.value)" style="width: 70px;">
                            </td>
                            <td>₨ ${item.total.toFixed(2)}</td>
                            <td><button onclick="removeFromCart(${index})" class="btn-remove"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    `;
                });
            }
            
            document.getElementById('subtotal').innerHTML = `₨ ${subtotal.toFixed(2)}`;
            updateTotal();
        }

        function updateTotal() {
            let discount = parseFloat(document.getElementById('discount').value) || 0;
            let total = subtotal - discount;
            if(total < 0) total = 0;
            document.getElementById('total').innerHTML = `₨ ${total.toFixed(2)}`;
            
            let paid = parseFloat(document.getElementById('paid-amount').value) || 0;
            let change = paid - total;
            let changeElement = document.getElementById('change-amount');
            if(change >= 0) {
                changeElement.innerHTML = `₨ ${change.toFixed(2)}`;
                changeElement.style.color = '#2ecc71';
            } else {
                changeElement.innerHTML = `₨ ${Math.abs(change).toFixed(2)} (Short)`;
                changeElement.style.color = '#e74c3c';
            }
        }

        function updateQuantity(index, quantity) {
            quantity = parseInt(quantity);
            if(quantity > 0 && quantity <= cart[index].stock) {
                cart[index].quantity = quantity;
                cart[index].total = quantity * cart[index].price;
                updateCartDisplay();
            } else {
                showNotification('Invalid quantity!', 'error');
            }
        }

        function removeFromCart(index) {
            let itemName = cart[index].name;
            cart.splice(index, 1);
            updateCartDisplay();
            showNotification(`${itemName} removed from cart`, 'info');
        }

        document.getElementById('discount').addEventListener('input', updateTotal);
        document.getElementById('paid-amount').addEventListener('input', updateTotal);
        
        document.getElementById('clear-cart').addEventListener('click', function() {
            if(cart.length > 0 && confirm('Are you sure you want to clear the entire cart?')) {
                cart = [];
                updateCartDisplay();
                document.getElementById('discount').value = 0;
                document.getElementById('paid-amount').value = 0;
                showNotification('Cart cleared', 'info');
            }
        });

        // Print Modal Functions
        function showPrintModal(invoiceNumber) {
            lastInvoiceNumber = invoiceNumber;
            const modal = document.getElementById('printModal');
            modal.style.display = 'flex';
        }

        document.getElementById('confirmPrint').onclick = function() {
            if(lastInvoiceNumber) {
                window.open(`print_receipt.php?invoice=${lastInvoiceNumber}`, '_blank');
            }
            document.getElementById('printModal').style.display = 'none';
        };

        document.getElementById('laterPrint').onclick = function() {
            document.getElementById('printModal').style.display = 'none';
            showNotification('You can print receipt later from reports section', 'info');
        };

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('printModal');
            if(event.target === modal) {
                modal.style.display = 'none';
            }
        };

        document.getElementById('complete-sale').addEventListener('click', function() {
            if(cart.length === 0) {
                showNotification('Cart is empty!', 'error');
                return;
            }
            
            let total = subtotal - (parseFloat(document.getElementById('discount').value) || 0);
            let paid = parseFloat(document.getElementById('paid-amount').value) || 0;
            
            if(paid < total) {
                showNotification('Paid amount is less than total!', 'error');
                return;
            }
            
            let saleData = {
                customer_id: document.getElementById('customer-id').value,
                cart: cart,
                subtotal: subtotal,
                discount: parseFloat(document.getElementById('discount').value) || 0,
                total: total,
                paid: paid,
                payment_method: document.getElementById('payment-method').value
            };
            
            // Disable button to prevent double submission
            let completeBtn = document.getElementById('complete-sale');
            completeBtn.disabled = true;
            completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('process_sale.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(saleData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showNotification(`Sale completed successfully!\nInvoice #: ${data.invoice_number}`, 'success');
                    
                    // Clear cart and reset form
                    cart = [];
                    updateCartDisplay();
                    document.getElementById('paid-amount').value = 0;
                    document.getElementById('discount').value = 0;
                    document.getElementById('customer-id').value = '';
                    
                    // Show print modal
                    showPrintModal(data.invoice_number);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error processing sale!', 'error');
            })
            .finally(() => {
                completeBtn.disabled = false;
                completeBtn.innerHTML = '<i class="fas fa-check-circle"></i> Complete Sale';
            });
        });

        // Notification function
        function showNotification(message, type) {
            let notification = document.createElement('div');
            let bgColor = type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#3498db';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${bgColor};
                color: white;
                border-radius: 10px;
                z-index: 10001;
                animation: slideIn 0.3s ease-out;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                max-width: 350px;
                white-space: pre-line;
            `;
            notification.innerHTML = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>