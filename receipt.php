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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="app-header">
        <div class="app-header-brand">
            <img src="../assets/img/logo.jpg" alt="Shelly's Pizza logo">
            Shelly's Pizza — Receipt
        </div>
        <div>
            <a href="../dashboard.php">Dashboard</a>
            &nbsp;|&nbsp;
            <a href="../auth/logout.php">Log Out</a>
        </div>
    </header>

    <main class="app-main">
        <div class="card" style="max-width: 480px; margin: 0 auto;">
            <p class="success-message">✅ Sale completed successfully.</p>

            <h2 style="margin-bottom: 0.2rem;">Receipt #<?php echo $sale["sale_id"]; ?></h2>
            <p style="color: var(--color-caramel-dark); margin-bottom: 1.2rem; font-size: 0.9rem;">
                <?php echo date("d M Y, g:i A", strtotime($sale["sale_date"])); ?>
                &nbsp;•&nbsp; Served by <?php echo htmlspecialchars($sale["username"]); ?>
            </p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item["product_name"]); ?></td>
                            <td><?php echo $item["quantity"]; ?></td>
                            <td class="price-cell">KES <?php echo number_format($item["subtotal"], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="text-align: right; margin-top: 1rem; font-family: var(--font-heading); font-size: 1.4rem; color: var(--color-red); font-weight: 700;">
                Total: KES <?php echo number_format($sale["total_amount"], 2); ?>
            </div>

            <div class="form-actions">
                <a href="new_sale.php" class="btn-primary">+ New Sale</a>
                <a href="../dashboard.php" class="btn-secondary">Dashboard</a>
                <a href="print_receipt.php?id=<?php echo $sale_id; ?>" 
                   class="btn-secondary" target="_blank">🖨️ Print Receipt</a>
            </div>
        </div>
    </main>

</body>
</html>
