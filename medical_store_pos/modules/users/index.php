<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard/index.php?error=unauthorized");
    exit();
}

// Handle user deletion
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if($id != $_SESSION['user_id']) {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $success = "User deleted successfully!";
    } else {
        $error = "You cannot delete your own account!";
    }
}

// Handle user add/edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    if(isset($_POST['user_id']) && $_POST['user_id'] > 0) {
        // Update user
        $query = "UPDATE users SET username = :username, full_name = :full_name, email = :email, role = :role WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_POST['user_id']);
        $success = "User updated successfully!";
    } else {
        // Add new user
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, full_name, email, role, password) VALUES (:username, :full_name, :email, :role, :password)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $password);
        $success = "User added successfully!";
    }
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':role', $role);
    
    if($stmt->execute()) {
        header("Location: index.php?success=" . urlencode($success));
        exit();
    } else {
        $error = "Error saving user!";
    }
}

// Get all users
$query = "SELECT * FROM users ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<style>
    .users-container {
        padding: 20px;
    }
    
    .page-header {
        margin-bottom: 25px;
    }
    
    .page-header h1 {
        font-size: 24px;
        color: #1e293b;
    }
    
    .action-bar {
        background: white;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .btn {
        padding: 8px 18px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-warning {
        background: #f59e0b;
        color: white;
    }
    
    .table-responsive {
        background: white;
        border-radius: 12px;
        overflow-x: auto;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }
    
    .data-table th {
        background: #1e293b;
        color: white;
        padding: 12px;
        text-align: left;
        font-size: 12px;
    }
    
    .data-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    
    .role-badge {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .role-admin { background: #fee2e2; color: #991b1b; }
    .role-manager { background: #fef3c7; color: #92400e; }
    .role-cashier { background: #d1fae5; color: #065f46; }
    
    .alert {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border-left: 4px solid #10b981;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 16px;
        padding: 25px;
        max-width: 500px;
        width: 90%;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        font-size: 13px;
    }
    
    .form-group input, .form-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }
    
    @media (max-width: 768px) {
        .users-container { padding: 15px; }
    }
</style>

<div class="users-container">
    <div class="page-header">
        <h1><i class="fas fa-users-cog"></i> User Management</h1>
        <p>Manage system users and roles (Admin only)</p>
    </div>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="action-bar">
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email'] ?: 'N/A'); ?></td>
                    <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                    <td>
                        <button onclick="openEditModal(<?php echo $user['id']; ?>)" class="btn btn-warning" style="padding: 4px 10px; font-size: 11px;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                        <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger" style="padding: 4px 10px; font-size: 11px;" onclick="return confirm('Delete this user?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle">Add New User</h3>
        <form method="POST" action="">
            <input type="hidden" name="user_id" id="user_id">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" id="full_name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" id="role">
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group" id="password_group">
                <label>Password *</label>
                <input type="password" name="password" id="password" placeholder="Enter password">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-primary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Add New User';
        document.getElementById('user_id').value = '';
        document.getElementById('username').value = '';
        document.getElementById('full_name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('role').value = 'cashier';
        document.getElementById('password').value = '';
        document.getElementById('password_group').style.display = 'block';
        document.getElementById('userModal').style.display = 'flex';
    }
    
    function openEditModal(id) {
        fetch(`get_user.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modalTitle').innerText = 'Edit User';
                document.getElementById('user_id').value = data.id;
                document.getElementById('username').value = data.username;
                document.getElementById('full_name').value = data.full_name;
                document.getElementById('email').value = data.email;
                document.getElementById('role').value = data.role;
                document.getElementById('password').value = '';
                document.getElementById('password_group').style.display = 'none';
                document.getElementById('userModal').style.display = 'flex';
            });
    }
    
    function closeModal() {
        document.getElementById('userModal').style.display = 'none';
    }
    
    window.onclick = function(event) {
        if(event.target == document.getElementById('userModal')) {
            closeModal();
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>