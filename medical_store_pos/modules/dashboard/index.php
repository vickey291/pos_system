<?php
// Fix the paths - go up two levels to reach the root
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get dashboard statistics with error handling
$stats = [];
$recent_sales = [];
$low_stock_items = [];

try {
    // Total Sales Today
    $query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['today_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Sales This Month
    $query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['month_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Medicines
    $query = "SELECT COUNT(*) as total FROM medicines";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_medicines'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Low Stock Alerts
    $query = "SELECT COUNT(*) as total FROM medicines WHERE stock_quantity <= min_stock_level";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Expiring Medicines (within 30 days)
    $query = "SELECT COUNT(*) as total FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['expiring'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Customers
    $query = "SELECT COUNT(*) as total FROM customers";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Suppliers
    $query = "SELECT COUNT(*) as total FROM suppliers";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_suppliers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent Sales with more details
    $query = "SELECT s.*, c.name as customer_name, u.full_name as cashier 
              FROM sales s 
              LEFT JOIN customers c ON s.customer_id = c.id 
              LEFT JOIN users u ON s.created_by = u.id 
              ORDER BY s.sale_date DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Low Stock Items with details
    $query = "SELECT id, name, stock_quantity, min_stock_level FROM medicines WHERE stock_quantity <= min_stock_level LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $stats = [
        'today_sales' => 0,
        'month_sales' => 0,
        'total_medicines' => 0,
        'low_stock' => 0,
        'expiring' => 0,
        'total_customers' => 0,
        'total_suppliers' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Dashboard - Medical Store POS System</title>
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

        /* Main Layout */
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
            padding: 0.5rem;
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

        /* Container */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 10s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-section h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .date-time {
            position: absolute;
            top: 2rem;
            right: 2rem;
            text-align: right;
            font-size: 1rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .stat-card:hover::after {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .stat-info h3 {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-info p {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-info small {
            font-size: 0.7rem;
            color: #94a3b8;
        }

        /* Card Colors */
        .stat-card.primary .stat-icon { background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea; }
        .stat-card.success .stat-icon { background: linear-gradient(135deg, #10b98120, #05966920); color: #10b981; }
        .stat-card.warning .stat-icon { background: linear-gradient(135deg, #f59e0b20, #d9770620); color: #f59e0b; }
        .stat-card.danger .stat-icon { background: linear-gradient(135deg, #ef444420, #dc262620); color: #ef4444; }
        .stat-card.info .stat-icon { background: linear-gradient(135deg, #3b82f620, #2563eb20); color: #3b82f6; }

        /* Section Styles */
        .section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-header h2 {
            font-size: 1.2rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-header h2 i {
            color: #667eea;
        }

        .view-all-btn {
            background: #f1f5f9;
            color: #667eea;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-all-btn:hover {
            background: #667eea;
            color: white;
        }

        /* Quick Actions */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .btn {
            padding: 0.8rem;
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Recent Sales List */
        .sales-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .sale-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 14px;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }

        .sale-item:hover {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transform: translateX(5px);
        }

        .sale-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .sale-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea15, #764ba215);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sale-icon i {
            font-size: 1.2rem;
            color: #667eea;
        }

        .sale-details {
            flex: 1;
        }

        .sale-invoice {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .sale-invoice strong {
            font-size: 0.9rem;
            color: #1e293b;
        }

        .payment-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .payment-cash { background: #d1fae5; color: #065f46; }
        .payment-card { background: #dbeafe; color: #1e40af; }
        .payment-online { background: #fef3c7; color: #92400e; }

        .sale-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.7rem;
            color: #64748b;
        }

        .sale-meta i {
            margin-right: 0.2rem;
        }

        .sale-actions {
            text-align: right;
        }

        .sale-amount {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.3rem;
        }

        .receipt-btn {
            background: #3b82f6;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s;
        }

        .receipt-btn:hover {
            background: #2563eb;
        }

        /* Alert Items */
        .alert-items {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .alert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
        }

        .alert-item .stock-badge {
            background: #f59e0b;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
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

            .top-header {
                padding: 1rem;
            }

            .dashboard-container {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                text-align: center;
            }

            .date-time {
                position: relative;
                top: auto;
                right: auto;
                text-align: center;
                margin-top: 1rem;
            }

            .welcome-section h1 {
                font-size: 1.3rem;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }

            .sale-item {
                flex-direction: column;
                text-align: center;
                gap: 0.8rem;
            }

            .sale-info {
                flex-direction: column;
                text-align: center;
            }

            .sale-invoice {
                justify-content: center;
            }

            .sale-meta {
                justify-content: center;
                flex-wrap: wrap;
            }

            .sale-actions {
                text-align: center;
            }

            .user-details {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .stat-info p {
                font-size: 1.2rem;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card, .section {
            animation: fadeInUp 0.5s ease-out;
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
                <li class="active">
                    <a href="index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../billing/index.php">
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
                    <a href="../purchases/index.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Purchases</span>
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
                    <h1>Dashboard</h1>
                    <p>Welcome back to your medical store management system</p>
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

            <div class="dashboard-container">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>!</h1>
                        <p>Here's what's happening with your medical store today.</p>
                    </div>
                    <div class="date-time">
                        <i class="fas fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?><br>
                        <i class="fas fa-clock"></i> <?php echo date('h:i A'); ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                        <div class="stat-info">
                            <h3>Today's Sales</h3>
                            <p>₨ <?php echo number_format($stats['today_sales'] ?? 0, 2); ?></p>
                            <small><i class="fas fa-chart-line"></i> Today's revenue</small>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="stat-info">
                            <h3>Monthly Sales</h3>
                            <p>₨ <?php echo number_format($stats['month_sales'] ?? 0, 2); ?></p>
                            <small><i class="fas fa-calendar"></i> This month</small>
                        </div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fas fa-pills"></i></div>
                        <div class="stat-info">
                            <h3>Total Medicines</h3>
                            <p><?php echo $stats['total_medicines'] ?? 0; ?></p>
                            <small><i class="fas fa-boxes"></i> Active products</small>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <h3>Low Stock Alert</h3>
                            <p><?php echo $stats['low_stock'] ?? 0; ?> Items</p>
                            <small><i class="fas fa-truck"></i> Need reorder</small>
                        </div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon"><i class="fas fa-calendar-times"></i></div>
                        <div class="stat-info">
                            <h3>Expiring Soon</h3>
                            <p><?php echo $stats['expiring'] ?? 0; ?> Items</p>
                            <small><i class="fas fa-hourglass-half"></i> Within 30 days</small>
                        </div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3>Total Customers</h3>
                            <p><?php echo $stats['total_customers'] ?? 0; ?></p>
                            <small><i class="fas fa-user-plus"></i> Registered</small>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    </div>
                    <div class="action-buttons">
                        <a href="../billing/index.php" class="btn btn-primary">
                            <i class="fas fa-cash-register"></i> New Bill
                        </a>
                        <a href="../medicines/add.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Medicine
                        </a>
                        <a href="../suppliers/add.php" class="btn btn-info">
                            <i class="fas fa-truck"></i> Add Supplier
                        </a>
                    </div>
                </div>
                
                <!-- Recent Sales Section -->
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Recent Sales</h2>
                        <a href="../reports/sales.php" class="view-all-btn">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="sales-list">
                        <?php if(isset($recent_sales) && count($recent_sales) > 0): ?>
                            <?php foreach($recent_sales as $sale): ?>
                            <div class="sale-item">
                                <div class="sale-info">
                                    <div class="sale-icon">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <div class="sale-details">
                                        <div class="sale-invoice">
                                            <strong><?php echo htmlspecialchars($sale['invoice_number'] ?? 'N/A'); ?></strong>
                                            <span class="payment-badge payment-<?php echo $sale['payment_method'] ?? 'cash'; ?>">
                                                <?php echo ucfirst($sale['payment_method'] ?? 'Cash'); ?>
                                            </span>
                                        </div>
                                        <div class="sale-meta">
                                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></span>
                                            <span><i class="fas fa-calendar-alt"></i> <?php echo isset($sale['sale_date']) ? date('d-m-Y h:i A', strtotime($sale['sale_date'])) : 'N/A'; ?></span>
                                            <span><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($sale['cashier'] ?? 'Staff'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="sale-actions">
                                    <div class="sale-amount">
                                        ₨ <?php echo number_format($sale['total_amount'] ?? 0, 2); ?>
                                    </div>
                                    <a href="../billing/print_receipt.php?invoice=<?php echo urlencode($sale['invoice_number']); ?>" 
                                       class="receipt-btn" target="_blank">
                                        <i class="fas fa-print"></i> Receipt
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-cart"></i>
                                <p>No sales recorded yet</p>
                                <a href="../billing/index.php" class="btn btn-primary" style="margin-top: 0.5rem; display: inline-block;">
                                    <i class="fas fa-plus"></i> Create First Sale
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Low Stock Alerts Section -->
                <?php if(isset($low_stock_items) && count($low_stock_items) > 0): ?>
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</h2>
                        <a href="../inventory/index.php" class="view-all-btn">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="alert-items">
                        <?php foreach($low_stock_items as $item): ?>
                        <div class="alert-item">
                            <div>
                                <strong><i class="fas fa-capsules"></i> <?php echo htmlspecialchars($item['name']); ?></strong>
                                <br>
                                <small>Min Level: <?php echo $item['min_stock_level']; ?> units</small>
                            </div>
                            <div class="stock-badge">
                                Stock: <?php echo $item['stock_quantity']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        if(menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if(window.innerWidth <= 768) {
                if(sidebar && menuToggle && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // Update date/time every second
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateString = now.toLocaleDateString('en-PK', options);
            const timeString = now.toLocaleTimeString('en-PK', { hour: '2-digit', minute: '2-digit' });
            const dateTimeElement = document.querySelector('.date-time');
            if(dateTimeElement) {
                dateTimeElement.innerHTML = `
                    <i class="fas fa-calendar-alt"></i> ${dateString}<br>
                    <i class="fas fa-clock"></i> ${timeString}
                `;
            }
        }
        
        setInterval(updateDateTime, 1000);
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if(window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>