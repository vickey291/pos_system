<?php include "db.php"; ?>
<!DOCTYPE html>
<html>
<head>
<title>Create Invoice</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
    body { background:#f1f4f9; }
    .container { margin-top:50px; max-width:900px; }
    .card { border-radius:15px; box-shadow:0 0 12px rgba(0,0,0,0.2); }
    .btn-save { background:#28a745;color:white; }
    .btn-save:hover{background:#218838;}
    .btn-remove{background:#dc3545;color:white;}
    .btn-remove:hover{background:#c82333;}
</style>
<script>
function addRow(){
    let table=document.getElementById("invoice_table");
    let rowCount=table.rows.length;
    let row=table.insertRow(rowCount);
    row.innerHTML=`<td>
        <select name="product_id[]" class="form-control product_select" required onchange="updatePrice(this)">
            <option value="">-- Select Product --</option>
            <?php $res=mysqli_query($conn,"SELECT * FROM products"); while($r=mysqli_fetch_assoc($res)){ echo "<option value='{$r['id']}' data-price='{$r['price']}'>{$r['name']} (Stock: {$r['quantity']})</option>"; } ?>
        </select></td>
        <td><input type="number" name="price[]" class="form-control price" readonly></td>
        <td><input type="number" name="quantity[]" class="form-control qty" value="1" min="1" onchange="calculateTotal()"></td>
        <td><input type="number" name="total[]" class="form-control total" readonly></td>
        <td><button type="button" class="btn btn-sm btn-remove" onclick="removeRow(this)">Remove</button></td>`;
}
function removeRow(btn){ let row=btn.parentNode.parentNode; row.parentNode.removeChild(row); calculateTotal();}
function updatePrice(sel){ let price=sel.selectedOptions[0].getAttribute("data-price"); let row=sel.parentNode.parentNode; row.querySelector(".price").value=price; calculateTotal();}
function calculateTotal(){
    let totals=document.querySelectorAll(".total"); let qtys=document.querySelectorAll(".qty"); let prices=document.querySelectorAll(".price"); let grandTotal=0;
    for(let i=0;i<totals.length;i++){ totals[i].value=prices[i].value*qtys[i].value; grandTotal+=totals[i].value;}
    document.getElementById("grand_total").value=grandTotal;
}
</script>
</head>
<body>
<div class="container">
    <div class="card p-4">
        <h2 class="text-center mb-4">Create Invoice</h2>
        <form method="POST" action="save_invoice.php">

        <!-- Customer selection -->
        <div class="mb-3">
            <label>Customer:</label>
            <select name="customer_id" class="form-control" required>
                <option value="">-- Select Customer --</option>
                <?php
                $cust=mysqli_query($conn,"SELECT * FROM customers");
                while($c=mysqli_fetch_assoc($cust)){
                    echo "<option value='{$c['id']}'>{$c['name']} ({$c['phone']})</option>";
                }
                ?>
            </select>
        </div>

        <!-- Products Table -->
        <table class="table table-bordered" id="invoice_table">
            <thead class="table-dark">
                <tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th>Action</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="product_id[]" class="form-control product_select" required onchange="updatePrice(this)">
                            <option value="">-- Select Product --</option>
                            <?php
                            $res=mysqli_query($conn,"SELECT * FROM products");
                            while($r=mysqli_fetch_assoc($res)){
                                echo "<option value='{$r['id']}' data-price='{$r['price']}'>{$r['name']} (Stock: {$r['quantity']})</option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td><input type="number" name="price[]" class="form-control price" readonly></td>
                    <td><input type="number" name="quantity[]" class="form-control qty" value="1" min="1" onchange="calculateTotal()"></td>
                    <td><input type="number" name="total[]" class="form-control total" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-remove" onclick="removeRow(this)">Remove</button></td>
                </tr>
            </tbody>
        </table>

        <button type="button" class="btn btn-info mb-3" onclick="addRow()">+ Add Product</button>

        <div class="mb-3">
            <label>Grand Total:</label>
            <input type="number" id="grand_total" name="grand_total" class="form-control" readonly>
        </div>

        <button class="btn btn-save w-100" type="submit">Save Invoice</button>

        </form>
    </div>
</div>
</body>
</html>
