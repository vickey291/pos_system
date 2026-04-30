<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private $conn;
    
    public function __construct($db = null) {
        $this->conn = $db;
    }
    
    public function login($username, $password) {
        if(!$this->conn) {
            return false;
        }
        
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                return true;
            } elseif($user['password'] == $password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET password = :password WHERE id = :id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(':password', $hashed_password);
                $update_stmt->bindParam(':id', $user['id']);
                $update_stmt->execute();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function getRole() {
        return $_SESSION['role'] ?? 'cashier';
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getUserName() {
        return $_SESSION['full_name'] ?? 'User';
    }
    
    // Role Check Methods
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function isManager() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'manager';
    }
    
    public function isCashier() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'cashier';
    }
    
    public function isAdminOrManager() {
        return $this->isAdmin() || $this->isManager();
    }
    
    // ============ PERMISSION METHODS ============
    
    public function canViewDashboard() {
        return true;
    }
    
    public function canAccessBilling() {
        return true;
    }
    
    public function canViewMedicines() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canAddMedicine() {
        return $this->isAdmin();
    }
    
    public function canEditMedicine() {
        return $this->isAdmin();
    }
    
    public function canDeleteMedicine() {
        return $this->isAdmin();
    }
    
    public function canImportMedicines() {
        return $this->isAdmin();
    }
    
    public function canExportMedicines() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canViewInventory() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canViewSuppliers() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canAddSupplier() {
        return $this->isAdmin();
    }
    
    public function canEditSupplier() {
        return $this->isAdmin();
    }
    
    public function canDeleteSupplier() {
        return $this->isAdmin();
    }
    
    public function canViewCustomers() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canAddCustomer() {
        return $this->isAdmin();
    }
    
    public function canEditCustomer() {
        return $this->isAdmin();
    }
    
    public function canDeleteCustomer() {
        return $this->isAdmin();
    }
    
    public function canViewLoyalty() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canViewPurchases() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canAddPurchase() {
        return $this->isAdmin();
    }
    
    public function canEditPurchase() {
        return $this->isAdmin();
    }
    
    public function canDeletePurchase() {
        return $this->isAdmin();
    }
    
    public function canViewReports() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canExportReports() {
        return $this->isAdmin() || $this->isManager();
    }
    
    public function canManageUsers() {
        return $this->isAdmin();
    }
    
    public function canDeleteAnyRecord() {
        return $this->isAdmin();
    }
    
    public function canEditAnyRecord() {
        return $this->isAdmin();
    }
    
    // ============ REQUIRE METHODS ============
    
    public function requireLogin() {
        if(!$this->isLoggedIn()) {
            $this->redirect('../login.php');
        }
    }
    
    public function requireAdmin() {
        if(!$this->isAdmin()) {
            $this->showAccessDenied("Only Administrator can access this page");
        }
    }
    
    public function requireManager() {
        if(!$this->isAdminOrManager()) {
            $this->showAccessDenied("Only Admin or Manager can access this page");
        }
    }
    
    public function requirePermission($permission) {
        if(!$this->$permission()) {
            $this->showAccessDenied("You don't have permission to perform this action");
        }
    }
    
    // ============ HELPER METHODS ============
    
    private function redirect($url) {
        header("Location: " . $url);
        exit();
    }
    
    private function showAccessDenied($message) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('HTTP/1.0 403 Forbidden');
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
                    margin-bottom: 25px;
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
                .denied-box .user-info {
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
                <p><?php echo htmlspecialchars($message); ?></p>
                <a href="../dashboard/index.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Go to Dashboard
                </a>
                <div class="user-info">
                    <i class="fas fa-user"></i> Logged in as: <?php echo htmlspecialchars($this->getUserName()); ?> 
                    (<?php echo ucfirst($this->getRole()); ?>)
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}
?>