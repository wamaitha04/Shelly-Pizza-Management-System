<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/auth.php';
requireRole(SALES_ROLES);

include '../config/db_connect.php';

$sale_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

$stmt = $conn->prepare(
    "SELECT s.sale_id, s.sale_date, s.total_amount, u.username
     FROM sales s
     JOIN users u ON s.user_id = u.user_id
     WHERE s.sale_id = ?"
);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sale) {
    header("Location: new_sale.php");
    exit();
}

$stmt = $conn->prepare(
    "SELECT product_name, quantity, unit_price,
            (quantity * unit_price) AS subtotal
     FROM sale_items
     WHERE sale_id = ?"
);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $sale_id; ?> - Shelly's Pizza</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            background: #fff;
            color: #000;
            padding: 20px;
        }

        .receipt-wrap {
            max-width: 320px;
            margin: 0 auto;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 12px;
        }

        .receipt-header img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 6px;
        }

        .receipt-header h1 {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .receipt-header p {
            font-size: 11px;
            color: #555;
        }

        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .receipt-meta {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .receipt-meta span {
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        thead th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 4px 2px;
            font-size: 11px;
            text-transform: uppercase;
        }

        tbody td {
            padding: 4px 2px;
            vertical-align: top;
        }

        .text-right { text-align: right; }

        .total-row {
            margin-top: 10px;
            text-align: right;
            font-size: 15px;
            font-weight: bold;
            border-top: 1px dashed #000;
            padding-top: 8px;
        }

        .receipt-footer {
            text-align: center;
            font-size: 11px;
            margin-top: 16px;
            color: #555;
        }

        .print-btn {
            display: block;
            margin: 20px auto 0;
            padding: 8px 24px;
            background: #A8352A;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 13px;
            border-radius: 4px;
        }

        /* Hide button when printing */
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="receipt-wrap">

    <div class="receipt-header">
        <img src="../assets/img/logo.jpg" alt="Shelly's Pizza">
        <h1>SHELLY'S PIZZA</h1>
        <p>Fresh from the oven to your table</p>
    </div>

    <hr class="divider">

    <div class="receipt-meta">
        <span><strong>Receipt #:</strong> <?php echo $sale["sale_id"]; ?></span>
        <span><strong>Date:</strong> <?php echo date("d M Y, g:i A", strtotime($sale["sale_date"])); ?></span>
        <span><strong>Served by:</strong> <?php echo htmlspecialchars($sale["username"]); ?></span>
    </div>

    <hr class="divider">

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item["product_name"]); ?></td>
                    <td class="text-right"><?php echo $item["quantity"]; ?></td>
                    <td class="text-right">KES <?php echo number_format($item["subtotal"], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-row">
        TOTAL: KES <?php echo number_format($sale["total_amount"], 2); ?>
    </div>

    <hr class="divider">

    <div class="receipt-footer">
        <p>Thank you for dining with us!</p>
        <p>Visit us again soon 🍕</p>
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ Print</button>

</div>

</body>
</html>
