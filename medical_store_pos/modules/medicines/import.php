<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';
$imported = 0;
$skipped = 0;

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if($file['error'] == UPLOAD_ERR_OK && $file['size'] > 0) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if($file_ext != 'csv') {
            $error = "Please upload a valid CSV file!";
        } else {
            $handle = fopen($file['tmp_name'], 'r');
            if($handle !== false) {
                // Skip headers
                $headers = fgetcsv($handle);
                
                while(($data = fgetcsv($handle)) !== false) {
                    // Skip empty rows
                    if(empty(array_filter($data))) {
                        continue;
                    }
                    
                    // Get data from CSV
                    $medicine_code = isset($data[0]) ? strtoupper(trim($data[0])) : '';
                    $name = isset($data[1]) ? trim($data[1]) : '';
                    $company = isset($data[2]) ? trim($data[2]) : '';
                    $batch_number = isset($data[3]) ? strtoupper(trim($data[3])) : '';
                    $expiry_date = isset($data[4]) ? trim($data[4]) : '';
                    $purchase_price = isset($data[5]) ? floatval($data[5]) : 0;
                    $sale_price = isset($data[6]) ? floatval($data[6]) : 0;
                    $stock_quantity = isset($data[7]) ? intval($data[7]) : 0;
                    $min_stock_level = isset($data[8]) ? intval($data[8]) : 10;
                    
                    // Validate required fields
                    if(empty($medicine_code) || empty($name) || empty($expiry_date)) {
                        $skipped++;
                        continue;
                    }
                    
                    // Validate expiry date format
                    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date)) {
                        $skipped++;
                        continue;
                    }
                    
                    // Check if medicine already exists
                    $checkQuery = "SELECT id FROM medicines WHERE medicine_code = :code";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':code', $medicine_code);
                    $checkStmt->execute();
                    
                    if($checkStmt->rowCount() == 0) {
                        $query = "INSERT INTO medicines (medicine_code, name, company, batch_number, expiry_date, purchase_price, sale_price, stock_quantity, min_stock_level) 
                                  VALUES (:code, :name, :company, :batch, :expiry, :purchase, :sale, :stock, :min_stock)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':code', $medicine_code);
                        $stmt->bindParam(':name', $name);
                        $stmt->bindParam(':company', $company);
                        $stmt->bindParam(':batch', $batch_number);
                        $stmt->bindParam(':expiry', $expiry_date);
                        $stmt->bindParam(':purchase', $purchase_price);
                        $stmt->bindParam(':sale', $sale_price);
                        $stmt->bindParam(':stock', $stock_quantity);
                        $stmt->bindParam(':min_stock', $min_stock_level);
                        
                        if($stmt->execute()) {
                            $imported++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        $skipped++;
                    }
                }
                fclose($handle);
                
                if($imported > 0) {
                    $_SESSION['success'] = "✅ Successfully imported " . $imported . " medicines! ⚠️ Skipped " . $skipped . " records.";
                } elseif($skipped > 0) {
                    $error = "No new medicines imported. " . $skipped . " records were skipped (duplicate codes or invalid data).";
                } else {
                    $error = "No valid data found in the CSV file.";
                }
                
                if(empty($error) && $imported > 0) {
                    header("Location: index.php");
                    exit();
                }
            } else {
                $error = "Error reading the CSV file!";
            }
        }
    } else {
        $error = "Please select a valid CSV file to upload.";
    }
}

require_once '../../includes/header.php';
?>

<style>
    .import-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        margin-bottom: 30px;
        text-align: center;
    }
    
    .page-header h1 {
        font-size: 28px;
        color: #1e293b;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }
    
    .page-header h1 i {
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .page-header p {
        color: #64748b;
        font-size: 14px;
    }
    
    /* Alert Styles */
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-left: 4px solid #ef4444;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-left: 4px solid #10b981;
    }
    
    /* Info Card */
    .info-card {
        background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        border: 1px solid #bae6fd;
    }
    
    .info-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .info-header i {
        font-size: 28px;
        color: #0284c7;
        background: white;
        padding: 10px;
        border-radius: 14px;
    }
    
    .info-header h3 {
        font-size: 18px;
        color: #0369a1;
        margin: 0;
    }
    
    .info-text {
        color: #0c4a6e;
        font-size: 13px;
        margin-bottom: 15px;
        line-height: 1.6;
    }
    
    /* Format Table */
    .format-table-wrapper {
        background: white;
        border-radius: 16px;
        overflow: auto;
        margin-top: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .format-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 600px;
    }
    
    .format-table th {
        background: #1e293b;
        color: white;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
    }
    
    .format-table td {
        padding: 10px 15px;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
    }
    
    .format-table tr:last-child td {
        border-bottom: none;
    }
    
    .format-table tr:hover td {
        background: #f8fafc;
    }
    
    .required-badge {
        background: #ef4444;
        color: white;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    
    .optional-badge {
        background: #94a3b8;
        color: white;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    
    .example-code {
        background: #f1f5f9;
        padding: 4px 8px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 12px;
    }
    
    /* Upload Card */
    .upload-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        margin-bottom: 25px;
    }
    
    .upload-area {
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #fafcff;
    }
    
    .upload-area:hover {
        border-color: #667eea;
        background: #f5f3ff;
        transform: translateY(-2px);
    }
    
    .upload-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea15, #764ba215);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    .upload-icon i {
        font-size: 40px;
        color: #667eea;
    }
    
    .upload-area h4 {
        font-size: 18px;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .upload-area p {
        color: #64748b;
        font-size: 13px;
    }
    
    .file-hint {
        margin-top: 15px;
        font-size: 12px;
        color: #94a3b8;
    }
    
    /* Buttons */
    .btn-group {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 25px;
    }
    
    .btn {
        padding: 12px 28px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        box-shadow: 0 2px 10px rgba(102,126,234,0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102,126,234,0.4);
    }
    
    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }
    
    .btn-sample {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    
    .btn-sample:hover {
        background: #fde68a;
        transform: translateY(-2px);
    }
    
    /* Features Grid */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 20px;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: white;
        border-radius: 12px;
    }
    
    .feature-item i {
        width: 30px;
        height: 30px;
        background: #e0e7ff;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        font-size: 14px;
    }
    
    .feature-item span {
        font-size: 12px;
        color: #334155;
    }
    
    .sample-link {
        text-align: center;
        margin-top: 20px;
    }
    
    @media (max-width: 768px) {
        .import-container {
            padding: 15px;
        }
        
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .format-table {
            font-size: 11px;
        }
        
        .format-table th, .format-table td {
            padding: 8px 10px;
        }
        
        .btn-group {
            flex-direction: column;
        }
        
        .btn {
            justify-content: center;
        }
        
        .upload-area {
            padding: 25px;
        }
    }
</style>

<div class="import-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <i class="fas fa-file-import"></i>
            Import Medicines
        </h1>
        <p>Bulk import medicines from CSV file into your inventory</p>
    </div>
    
    <!-- Error Alert -->
    <?php if($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle" style="font-size: 20px;"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Info Card -->
    <div class="info-card">
        <div class="info-header">
            <i class="fas fa-info-circle"></i>
            <h3>CSV Format Guide</h3>
        </div>
        <div class="info-text">
            Your CSV file should follow the format below. The first row should contain column headers.
        </div>
        
        <div class="format-table-wrapper">
            <table class="format-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Column Name</th>
                        <th>Required</th>
                        <th>Description</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td><strong>Medicine Code</strong></td><td><span class="required-badge">Required</span></td><td>Unique identifier</td><td><code class="example-code">MED001</code></td></tr>
                    <tr><td>2</td><td><strong>Medicine Name</strong></td><td><span class="required-badge">Required</span></td><td>Full name</td><td><code class="example-code">Paracetamol 500mg</code></td></tr>
                    <tr><td>3</td><td>Company</td><td><span class="optional-badge">Optional</span></td><td>Manufacturer</td><td><code class="example-code">GSK</code></td></tr>
                    <tr><td>4</td><td>Batch Number</td><td><span class="optional-badge">Optional</span></td><td>Batch/Lot number</td><td><code class="example-code">BATCH001</code></td></tr>
                    <tr><td>5</td><td><strong>Expiry Date</strong></td><td><span class="required-badge">Required</span></td><td>YYYY-MM-DD format</td><td><code class="example-code">2025-12-31</code></td></tr>
                    <tr><td>6</td><td>Purchase Price</td><td><span class="optional-badge">Optional</span></td><td>Cost price</td><td><code class="example-code">5.00</code></td></tr>
                    <tr><td>7</td><td>Sale Price</td><td><span class="optional-badge">Optional</span></td><td>Selling price</td><td><code class="example-code">8.00</code></td></tr>
                    <tr><td>8</td><td>Stock Quantity</td><td><span class="optional-badge">Optional</span></td><td>Initial stock</td><td><code class="example-code">100</code></td></tr>
                    <tr><td>9</td><td>Min Stock Level</td><td><span class="optional-badge">Optional</span></td><td>Alert level</td><td><code class="example-code">10</code></td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="features-grid">
            <div class="feature-item"><i class="fas fa-check-circle"></i><span>Auto-skip duplicate codes</span></div>
            <div class="feature-item"><i class="fas fa-shield-alt"></i><span>Data validation included</span></div>
            <div class="feature-item"><i class="fas fa-chart-line"></i><span>Import summary report</span></div>
        </div>
        
        <div class="sample-link">
            <a href="sample_csv.php" class="btn btn-sample">
                <i class="fas fa-download"></i> Download Sample CSV Template
            </a>
        </div>
    </div>
    
    <!-- Upload Card -->
    <div class="upload-card">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="upload-area" onclick="document.getElementById('csv_file').click()">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h4>Choose CSV File</h4>
                <p>Click or drag and drop your CSV file here</p>
                <div class="file-hint">
                    <i class="fas fa-file-csv"></i> Supported format: .CSV
                </div>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" style="display: none;" required>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Import Medicines
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Medicines
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tips Card -->
    <div style="background: #f8fafc; border-radius: 16px; padding: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <i class="fas fa-lightbulb" style="color: #f59e0b; font-size: 20px;"></i>
            <h4 style="color: #1e293b; margin: 0;">Pro Tips</h4>
        </div>
        <ul style="color: #64748b; font-size: 13px; margin-left: 20px;">
            <li>Make sure your CSV file is UTF-8 encoded to avoid character issues</li>
            <li>Medicine Code must be unique - duplicate codes will be skipped automatically</li>
            <li>Expiry Date must be in YYYY-MM-DD format (e.g., 2025-12-31)</li>
            <li>All numeric values should not contain currency symbols or commas</li>
            <li>First row of your CSV should contain column headers</li>
        </ul>
    </div>
</div>

<script>
    // Show selected file name
    const fileInput = document.getElementById('csv_file');
    const uploadArea = document.querySelector('.upload-area');
    
    if(fileInput) {
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if(fileName) {
                uploadArea.innerHTML = `
                    <div class="upload-icon" style="background: #d1fae5;">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    </div>
                    <h4>File Selected</h4>
                    <p><strong>${fileName}</strong></p>
                    <div class="file-hint">
                        <i class="fas fa-file-csv"></i> Click to change file
                    </div>
                `;
                uploadArea.style.borderColor = '#10b981';
                uploadArea.style.background = '#f0fdf4';
            }
        });
        
        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#667eea';
            uploadArea.style.background = '#f5f3ff';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#cbd5e1';
            uploadArea.style.background = '#fafcff';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            const file = e.dataTransfer.files[0];
            if(file && file.name.endsWith('.csv')) {
                fileInput.files = e.dataTransfer.files;
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            } else {
                alert('Please drop a valid CSV file');
            }
            uploadArea.style.borderColor = '#cbd5e1';
            uploadArea.style.background = '#fafcff';
        });
    }
</script>

<?php require_once '../../includes/footer.php'; ?>