<?php
/**
 * Central Permission Checker
 * Include this file at the top of every page
 * It will automatically check permissions based on page type
 */

require_once __DIR__ . '/auth.php';

// Create auth instance
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Get current page info
$current_file = basename($_SERVER['PHP_SELF']);
$current_module = basename(dirname($_SERVER['PHP_SELF']));

// Define page permissions
$page_permissions = [
    // Module => [page => required_permission, redirect_on_fail => redirect_page]
    'dashboard' => [
        'required' => 'canViewDashboard',
        'redirect' => '../login.php'
    ],
    'billing' => [
        'required' => 'canAccessBilling',
        'redirect' => '../dashboard/index.php'
    ],
    'medicines' => [
        'required' => 'canViewMedicines',
        'redirect' => '../dashboard/index.php'
    ],
    'inventory' => [
        'required' => 'canViewInventory',
        'redirect' => '../dashboard/index.php'
    ],
    'suppliers' => [
        'required' => 'canViewSuppliers',
        'redirect' => '../dashboard/index.php'
    ],
    'customers' => [
        'required' => 'canViewCustomers',
        'redirect' => '../dashboard/index.php'
    ],
    'purchases' => [
        'required' => 'canViewPurchases',
        'redirect' => '../dashboard/index.php'
    ],
    'reports' => [
        'required' => 'canViewReports',
        'redirect' => '../dashboard/index.php'
    ],
    'users' => [
        'required' => 'canManageUsers',
        'redirect' => '../dashboard/index.php'
    ]
];

// Define action permissions (for add, edit, delete)
$action_permissions = [
    'add' => [
        'medicines' => 'canAddMedicine',
        'suppliers' => 'canAddSupplier',
        'customers' => 'canAddCustomer',
        'purchases' => 'canAddPurchase'
    ],
    'edit' => [
        'medicines' => 'canEditMedicine',
        'suppliers' => 'canEditSupplier',
        'customers' => 'canEditCustomer',
        'purchases' => 'canEditPurchase'
    ],
    'delete' => [
        'medicines' => 'canDeleteMedicine',
        'suppliers' => 'canDeleteSupplier',
        'customers' => 'canDeleteCustomer',
        'purchases' => 'canDeletePurchase'
    ],
    'import' => [
        'medicines' => 'canImportMedicines'
    ],
    'export' => [
        'medicines' => 'canExportMedicines',
        'reports' => 'canExportReports'
    ]
];

// Check if current file is an action file (add, edit, delete, etc.)
$is_action_file = false;
$action_type = '';
$action_module = '';

foreach ($action_permissions as $action => $modules) {
    if (strpos($current_file, $action . '.php') !== false) {
        $is_action_file = true;
        $action_type = $action;
        // Find which module this action belongs to
        foreach ($modules as $module => $permission) {
            if ($current_module == $module) {
                $action_module = $module;
                break;
            }
        }
        break;
    }
}

// Check permission
$has_permission = false;
$permission_name = '';
$redirect_page = '../dashboard/index.php';

if ($is_action_file && isset($action_permissions[$action_type][$current_module])) {
    // Check action permission (add, edit, delete)
    $permission_name = $action_permissions[$action_type][$current_module];
    $has_permission = $auth->$permission_name();
} elseif (isset($page_permissions[$current_module])) {
    // Check page permission
    $permission_name = $page_permissions[$current_module]['required'];
    $redirect_page = $page_permissions[$current_module]['redirect'];
    $has_permission = $auth->$permission_name();
} else {
    // Default: allow access
    $has_permission = true;
}

// If no permission, show access denied page
if (!$has_permission) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Show access denied page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .denied-box {
                background: white;
                border-radius: 20px;
                padding: 40px;
                text-align: center;
                max-width: 450px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                animation: fadeIn 0.5s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .denied-box i {
                font-size: 70px;
                color: #ef4444;
                margin-bottom: 20px;
            }
            .denied-box h2 {
                color: #991b1b;
                margin-bottom: 10px;
                font-size: 28px;
            }
            .denied-box p {
                color: #64748b;
                margin-bottom: 20px;
                line-height: 1.6;
            }
            .denied-box .btn {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 12px 25px;
                border-radius: 10px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                font-weight: 600;
                transition: all 0.3s;
            }
            .denied-box .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            }
            .user-info {
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid #e2e8f0;
                font-size: 13px;
                color: #94a3b8;
            }
        </style>
    </head>
    <body>
        <div class="denied-box">
            <i class="fas fa-lock"></i>
            <h2>Access Denied</h2>
            <p>You don't have permission to access this page.<br>
            This action is restricted to authorized users only.</p>
            <a href="<?php echo $redirect_page; ?>" class="btn">
                <i class="fas fa-arrow-left"></i> Go to Dashboard
            </a>
            <div class="user-info">
                <i class="fas fa-user"></i> Logged in as: <strong><?php echo htmlspecialchars($auth->getUserName()); ?></strong><br>
                Role: <strong><?php echo ucfirst($auth->getRole()); ?></strong>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If we reach here, user has permission
// No need to do anything - just continue to the page
?>