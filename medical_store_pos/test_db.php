<?php
echo "<h2>Database Connection Test</h2>";

// Test 1: Check if MySQL is running
echo "<h3>1. Testing MySQL Connection:</h3>";
try {
    $test_conn = new PDO("mysql:host=localhost;port=3306", "root", "");
    $test_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ MySQL is running and accessible<br>";
    
    // Test 2: Check if database exists
    echo "<h3>2. Checking Database:</h3>";
    $stmt = $test_conn->query("SHOW DATABASES LIKE 'medical_store_pos'");
    if($stmt->rowCount() > 0) {
        echo "✓ Database 'medical_store_pos' exists<br>";
        
        // Test 3: Check users table
        echo "<h3>3. Checking Users Table:</h3>";
        $test_conn->exec("USE medical_store_pos");
        $stmt = $test_conn->query("SHOW TABLES LIKE 'users'");
        if($stmt->rowCount() > 0) {
            echo "✓ Table 'users' exists<br>";
            
            // Test 4: Show users
            echo "<h3>4. Users in database:</h3>";
            $stmt = $test_conn->query("SELECT id, username, password, full_name, role FROM users");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Password</th><th>Full Name</th><th>Role</th></tr>";
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['password'] . "</td>";
                echo "<td>" . $row['full_name'] . "</td>";
                echo "<td>" . $row['role'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "✗ Table 'users' does not exist<br>";
            echo "Please create the users table using phpMyAdmin";
        }
    } else {
        echo "✗ Database 'medical_store_pos' does not exist<br>";
        echo "Please create the database in phpMyAdmin";
    }
    
} catch(PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure MySQL is started in XAMPP Control Panel</li>";
    echo "<li>Check if port 3306 is not blocked</li>";
    echo "<li>Try changing password in database.php if MySQL has a password</li>";
    echo "<li>Check if MySQL is running on a different port (like 3307)</li>";
    echo "</ul>";
}
?>