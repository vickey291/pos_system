<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get report data
if($report_type == 'daily') {
    $query = "SELECT s.*, COUNT(si.id) as item_count, u.full_name as cashier 
              FROM sales s 
              LEFT JOIN sale_items si ON s.id = si.sale_id 
              LEFT JOIN users u ON s.created_by = u.id 
              WHERE DATE(s.sale_date) = :date 
              GROUP BY s.id 
              ORDER BY s.sale_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':date', $start_date);
} else {
    $query = "SELECT s.*, COUNT(si.id) as item_count, u.full_name as cashier 
              FROM sales s 
              LEFT JOIN sale_items si ON s.id = si.sale_id 
              LEFT JOIN users u ON s.created_by = u.id 
              WHERE DATE(s.sale_date) BETWEEN :start AND :end 
              GROUP BY s.id 
              ORDER BY s.sale_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start', $start_date);
    $stmt->bindParam(':end', $end_date);
}

$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get totals
$total_sales = 0;
$total_items = 0;
$total_discount = 0;
foreach($sales as $sale) {
    $total_sales += $sale['total_amount'];
    $total_items += $sale['item_count'];
    $total_discount += $sale['discount'];
}

// Get top selling medicines
$query = "SELECT m.name, SUM(si.quantity) as total_sold, SUM(si.total) as total_revenue 
          FROM sale_items si 
          JOIN medicines m ON si.medicine_id = m.id 
          JOIN sales s ON si.sale_id = s.id 
          WHERE DATE(s.sale_date) BETWEEN :start AND :end 
          GROUP BY m.id 
          ORDER BY total_sold DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':start', $start_date);
$stmt->bindParam(':end', $end_date);
$stmt->execute();
$top_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily sales for chart
$chartQuery = "SELECT DATE(sale_date) as date, SUM(total_amount) as total 
               FROM sales 
               WHERE sale_date BETWEEN :start AND :end 
               GROUP BY DATE(sale_date) 
               ORDER BY date";
$chartStmt = $db->prepare($chartQuery);
$chartStmt->bindParam(':start', $start_date);
$chartStmt->bindParam(':end', $end_date);
$chartStmt->execute();
$chart_data = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<style>
    .reports-container {
        padding: 20px;
    }
    
    .page-header {
        margin-bottom: 25px;
    }
    
    .page-header h1 {
        font-size: 24px;
        color: #1e293b;
        margin-bottom: 5px;
    }
    
    .page-header p {
        color: #64748b;
        font-size: 14px;
    }
    
    /* Filter Card */
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .filter-group {
        flex: 1;
        min-width: 150px;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
    }
    
    .filter-group input, .filter-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .filter-group input:focus, .filter-group select:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .btn-success {
        background: #10b981;
        color: white;
    }
    
    .btn-info {
        background: #3b82f6;
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 55px;
        height: 55px;
        background: linear-gradient(135deg, #667eea15, #764ba215);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon i {
        font-size: 26px;
        color: #667eea;
    }
    
    .stat-info h3 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 5px;
    }
    
    .stat-info p {
        font-size: 13px;
        color: #64748b;
    }
    
    /* Chart Card */
    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .chart-card h3 {
        font-size: 18px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    canvas {
        max-height: 300px;
        width: 100%;
    }
    
    /* Top Medicines Card */
    .top-medicines-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .top-medicines-card h3 {
        font-size: 18px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .medicine-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .medicine-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: #f8fafc;
        border-radius: 10px;
    }
    
    .medicine-name {
        font-weight: 500;
        color: #1e293b;
    }
    
    .medicine-stats {
        display: flex;
        gap: 20px;
    }
    
    .medicine-stats span {
        font-size: 13px;
    }
    
    .medicine-stats .sold {
        color: #667eea;
        font-weight: 600;
    }
    
    .medicine-stats .revenue {
        color: #10b981;
        font-weight: 600;
    }
    
    /* Sales Table */
    .sales-table-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .sales-table-card h3 {
        font-size: 18px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }
    
    .data-table thead {
        background: #1e293b;
        color: white;
    }
    
    .data-table th {
        padding: 12px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
    }
    
    .data-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    
    .data-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-cash { background: #d1fae5; color: #065f46; }
    .badge-card { background: #dbeafe; color: #1e40af; }
    .badge-online { background: #fef3c7; color: #92400e; }
    
    .empty-state {
        text-align: center;
        padding: 50px;
        color: #94a3b8;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        justify-content: flex-end;
    }
    
    @media (max-width: 768px) {
        .reports-container {
            padding: 15px;
        }
        
        .filter-form {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .stat-card {
            padding: 15px;
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
        }
        
        .stat-icon i {
            font-size: 20px;
        }
        
        .stat-info h3 {
            font-size: 18px;
        }
        
        .medicine-item {
            flex-direction: column;
            text-align: center;
            gap: 8px;
        }
        
        .medicine-stats {
            justify-content: center;
        }
    }
</style>

<div class="reports-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
        <p>View sales reports, analytics, and export data</p>
    </div>
    
    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label>Report Type</label>
                <select name="type" onchange="this.form.submit()">
                    <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>📅 Daily Report</option>
                    <option value="range" <?php echo $report_type == 'range' ? 'selected' : ''; ?>>📆 Date Range Report</option>
                </select>
            </div>
            
            <?php if($report_type == 'daily'): ?>
            <div class="filter-group">
                <label>Select Date</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" onchange="this.form.submit()">
            </div>
            <?php else: ?>
            <div class="filter-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="filter-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-chart-line"></i> Generate Report
                </button>
            </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <div class="action-buttons">
                    <a href="export.php?type=<?php echo $report_type; ?>&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" 
                       class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="print.php?type=<?php echo $report_type; ?>&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" 
                       class="btn btn-info" target="_blank">
                        <i class="fas fa-print"></i> Print Report
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo number_format($total_sales, 2); ?></h3>
                <p>Total Sales</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div class="stat-info">
                <h3><?php echo count($sales); ?></h3>
                <p>Total Invoices</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_items; ?></h3>
                <p>Items Sold</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-percent"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo number_format($total_discount, 2); ?></h3>
                <p>Total Discount</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h3>₨ <?php echo count($sales) > 0 ? number_format($total_sales / count($sales), 2) : '0.00'; ?></h3>
                <p>Average Bill</p>
            </div>
        </div>
    </div>
    
    <!-- Chart Card -->
    <?php if(count($chart_data) > 0): ?>
    <div class="chart-card">
        <h3><i class="fas fa-chart-line"></i> Sales Trend</h3>
        <canvas id="salesChart"></canvas>
    </div>
    <?php endif; ?>
    
    <!-- Top Selling Medicines -->
    <div class="top-medicines-card">
        <h3><i class="fas fa-trophy"></i> Top Selling Medicines</h3>
        <?php if(count($top_medicines) > 0): ?>
        <div class="medicine-list">
            <?php foreach($top_medicines as $index => $medicine): ?>
            <div class="medicine-item">
                <div class="medicine-name">
                    <span style="font-weight: bold; color: #667eea;">#<?php echo $index + 1; ?></span> 
                    <?php echo htmlspecialchars($medicine['name']); ?>
                </div>
                <div class="medicine-stats">
                    <span class="sold"><i class="fas fa-box"></i> <?php echo $medicine['total_sold']; ?> sold</span>
                    <span class="revenue"><i class="fas fa-rupee-sign"></i> <?php echo number_format($medicine['total_revenue'], 2); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-line"></i>
            <p>No sales data available for this period</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sales Details Table -->
    <div class="sales-table-card">
        <h3><i class="fas fa-list"></i> Sales Details</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date & Time</th>
                        <th>Cashier</th>
                        <th>Items</th>
                        <th>Discount</th>
                        <th>Total</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($sales) > 0): ?>
                        <?php foreach($sales as $sale): ?>
                        <tr>
                            <td><strong><?php echo $sale['invoice_number']; ?></strong></td>
                            <td><?php echo date('d-m-Y h:i A', strtotime($sale['sale_date'])); ?></td>
                            <td><?php echo $sale['cashier']; ?></td>
                            <td><?php echo $sale['item_count']; ?> items</td>
                            <td>₨ <?php echo number_format($sale['discount'], 2); ?></td>
                            <td><strong>₨ <?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                            <td><span class="badge badge-<?php echo $sale['payment_method']; ?>"><?php echo ucfirst($sale['payment_method']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-chart-line"></i>
                                    <p>No sales data available for the selected period</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if(count($chart_data) > 0): ?>
const ctx = document.getElementById('salesChart').getContext('2d');
const chartData = <?php echo json_encode($chart_data); ?>;
new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.map(item => item.date),
        datasets: [{
            label: 'Sales (₨)',
            data: chartData.map(item => item.total),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₨ ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once '../../includes/footer.php'; ?>