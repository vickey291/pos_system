# pos_system
📌 POS System (PHP + MySQL)  A simple and modern Point of Sale (POS) System built using PHP, MySQL and Bootstrap. This system includes products management, customers management, invoice creation, stock control, dashboard analytics and a secure login system.
## How to Install (Localhost)

1. Download or clone this repository.
2. Copy the folder into XAMPP → htdocs.
3. Open phpMyAdmin and create a database: pos_db
4. Import the file: pos_db.sql
5. Open the file db.php and set database credentials:
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "pos_db";
6. Run in browser:
   http://localhost/pos-system/
