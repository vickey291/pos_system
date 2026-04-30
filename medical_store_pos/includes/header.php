<?php
// Start output buffering
ob_start();

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permission_check.php';

// Check if user is logged in
$current_page = basename($_SERVER['PHP_SELF']);
if(!isset($_SESSION['user_id']) && $current_page != 'login.php') {
    header("Location: ../login.php");
    exit();
}

// Get user info
$auth = new Auth();
$user_name = $auth->getUserName();
$user_role = $auth->getRole();
$user_id = $auth->getUserId();

// Role definitions
$is_admin = $auth->isAdmin();
$is_manager = $auth->isAdminOrManager();
$is_cashier = $auth->isCashier();

// Get page title
$current_module = basename(dirname($_SERVER['PHP_SELF']));
$page_title = 'Dashboard';
if($current_module == 'billing') $page_title = 'Billing';
elseif($current_module == 'medicines') $page_title = 'Medicines';
elseif($current_module == 'inventory') $page_title = 'Inventory';
elseif($current_module == 'suppliers') $page_title = 'Suppliers';
elseif($current_module == 'customers') $page_title = 'Customers';
elseif($current_module == 'purchases') $page_title = 'Purchases';
elseif($current_module == 'reports') $page_title = 'Reports';
elseif($current_module == 'users') $page_title = 'User Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Medical Store POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: #0f0f1f; }
        .sidebar::-webkit-scrollbar-thumb { background: #667eea; border-radius: 5px; }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h3 {
            font-size: 1.3rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .sidebar-header p { font-size: 0.7rem; opacity: 0.7; margin-top: 0.3rem; }
        .role-badge {
            display: inline-block;
            background: rgba(102,126,234,0.3);
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.65rem;
            margin-top: 0.5rem;
        }
        
        .sidebar-menu { list-style: none; padding: 1rem 0; }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.7rem 1.2rem;
            color: #e0e0e0;
            text-decoration: none;
            transition: all 0.3s;
            gap: 0.75rem;
            font-size: 0.85rem;
        }
        .sidebar-menu li a:hover,
        .sidebar-menu li.active a {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: white;
        }
        .sidebar-menu li a i { width: 22px; }
        .menu-group-header {
            padding: 0.8rem 1.2rem 0.3rem;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            border-top: 1px solid rgba(255,255,255,0.05);
            margin-top: 0.5rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            transition: all 0.3s;
        }
        .top-header {
            background: white;
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #667eea; }
        .page-title h1 { font-size: 1.2rem; color: #333; }
        .user-profile { display: flex; align-items: center; gap: 1rem; }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .user-details { text-align: right; }
        .user-details h4 { font-size: 0.85rem; color: #333; }
        .user-details p { font-size: 0.7rem; color: #666; }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            transition: all 0.3s;
        }
        .logout-btn:hover { background: #c82333; }
        .content-wrapper { padding: 1.5rem; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; }
            .menu-toggle { display: block; }
            .user-details { display: none; }
            .content-wrapper { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-hospital-user"></i> MediCare POS</h3>
                <p><?php echo htmlspecialchars($user_name); ?></p>
                <span class="role-badge">
                    <i class="fas <?php echo $is_admin ? 'fa-crown' : ($is_manager ? 'fa-chart-line' : 'fa-user'); ?>"></i> 
                    <?php echo ucfirst($user_role); ?>
                </span>
            </div>
            <ul class="sidebar-menu">
                <li class="<?php echo ($current_module == 'dashboard') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/dashboard/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li class="<?php echo ($current_module == 'billing') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/billing/index.php"><i class="fas fa-cash-register"></i> Billing</a>
                </li>
                
                <?php if($is_admin || $is_manager): ?>
                <div class="menu-group-header"><i class="fas fa-chart-line"></i> MANAGEMENT</div>
                <li class="<?php echo ($current_module == 'medicines') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/medicines/index.php"><i class="fas fa-pills"></i> Medicines</a>
                </li>
                <li class="<?php echo ($current_module == 'inventory') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/inventory/index.php"><i class="fas fa-boxes"></i> Inventory</a>
                </li>
                <li class="<?php echo ($current_module == 'suppliers') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/suppliers/index.php"><i class="fas fa-truck"></i> Suppliers</a>
                </li>
                <li class="<?php echo ($current_module == 'customers') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/customers/index.php"><i class="fas fa-users"></i> Customers</a>
                </li>
                <li class="<?php echo ($current_module == 'purchases') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/purchases/index.php"><i class="fas fa-shopping-cart"></i> Purchases</a>
                </li>
                <li class="<?php echo ($current_module == 'reports') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <?php endif; ?>
                
                <?php if($is_admin): ?>
                <div class="menu-group-header"><i class="fas fa-cog"></i> ADMIN</div>
                <li class="<?php echo ($current_module == 'users') ? 'active' : ''; ?>">
                    <a href="/medical_store_pos/modules/users/index.php"><i class="fas fa-users-cog"></i> User Management</a>
                </li>
                <?php endif; ?>
                
                <div class="menu-group-header"><i class="fas fa-user"></i> ACCOUNT</div>
                <li><a href="/medical_store_pos/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="top-header">
                <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div class="page-title"><h1><?php echo $page_title; ?></h1></div>
                <div class="user-profile">
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($user_name); ?></h4>
                        <p><?php echo ucfirst($user_role); ?></p>
                    </div>
                    <div class="user-avatar"><i class="fas fa-user-md"></i></div>
                    <a href="/medical_store_pos/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            <div class="content-wrapper">