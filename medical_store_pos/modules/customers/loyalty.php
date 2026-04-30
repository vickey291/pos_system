<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM customers ORDER BY total_purchases DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<style>
    .loyalty-container {
        padding: 20px;
    }
    
    .page-header {
        margin-bottom: 25px;
    }
    
    .tier-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .tier-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .tier-card i {
        font-size: 40px;
        margin-bottom: 10px;
    }
    
    .tier-card h3 {
        font-size: 18px;
        margin-bottom: 10px;
    }
    
    .tier-card .discount {
        font-size: 28px;
        font-weight: 700;
    }
    
    .tier-gold i { color: #f59e0b; }
    .tier-silver i { color: #94a3b8; }
    .tier-bronze i { color: #cd7f32; }
    .tier-platinum i { color: #e5e4e2; }
    
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
    
    .data-table thead {
        background: #1e293b;
        color: white;
    }
    
    .data-table th, .data-table td {
        padding: 12px;
        text-align: left;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .tier-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .tier-gold-badge { background: #fef3c7; color: #92400e; }
    .tier-silver-badge { background: #f1f5f9; color: #475569; }
    .tier-bronze-badge { background: #fed7aa; color: #92400e; }
    
    @media (max-width: 768px) {
        .tier-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="loyalty-container">
    <div class="page-header">
        <h1><i class="fas fa-gift"></i> Loyalty Program</h1>
        <p>Reward your loyal customers with exclusive discounts</p>
    </div>
    
    <div class="tier-grid">
        <div class="tier-card tier-bronze">
            <i class="fas fa-medal"></i>
            <h3>Bronze</h3>
            <div class="discount">0% OFF</div>
            <p>Min Purchase: ₨ 0</p>
        </div>
        <div class="tier-card tier-silver">
            <i class="fas fa-star"></i>
            <h3>Silver</h3>
            <div class="discount">5% OFF</div>
            <p>Min Purchase: ₨ 5,000</p>
        </div>
        <div class="tier-card tier-gold">
            <i class="fas fa-crown"></i>
            <h3>Gold</h3>
            <div class="discount">10% OFF</div>
            <p>Min Purchase: ₨ 15,000</p>
        </div>
        <div class="tier-card tier-platinum">
            <i class="fas fa-gem"></i>
            <h3>Platinum</h3>
            <div class="discount">15% OFF</div>
            <p>Min Purchase: ₨ 50,000</p>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Customer</th><th>Phone</th><th>Total Purchases</th><th>Tier</th><th>Discount</th></tr></thead>
            <tbody>
                <?php foreach($customers as $customer):
                    $total = $customer['total_purchases'];
                    if($total >= 50000) { $tier = 'Platinum'; $discount = '15%'; $class = 'tier-platinum-badge'; }
                    elseif($total >= 15000) { $tier = 'Gold'; $discount = '10%'; $class = 'tier-gold-badge'; }
                    elseif($total >= 5000) { $tier = 'Silver'; $discount = '5%'; $class = 'tier-silver-badge'; }
                    else { $tier = 'Bronze'; $discount = '0%'; $class = 'tier-bronze-badge'; }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                    <td><?php echo $customer['phone']; ?></td>
                    <td>₨ <?php echo number_format($total, 2); ?></td>
                    <td><span class="tier-badge <?php echo $class; ?>"><?php echo $tier; ?></span></td>
                    <td><?php echo $discount; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>