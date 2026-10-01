<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

include 'config/db_connect.php';
include 'includes/stock.php';
$activePage = "dashboard";

// ---- Today vs Yesterday comparison ----
$today = $conn->query("
    SELECT IFNULL(SUM(total_amount),0) AS revenue, COUNT(*) AS cnt 
    FROM sales WHERE DATE(sale_date) = CURDATE()
")->fetch_assoc();

$yesterday = $conn->query("
    SELECT IFNULL(SUM(total_amount),0) AS revenue 
    FROM sales WHERE DATE(sale_date) = CURDATE() - INTERVAL 1 DAY
")->fetch_assoc();

$pctChange = 0;
if ($yesterday["revenue"] > 0) {
    $pctChange = (($today["revenue"] - $yesterday["revenue"]) / $yesterday["revenue"]) * 100;
}

// ---- Pizzas sold today (from sale_items) ----
$pizzasToday = $conn->query("
    SELECT IFNULL(SUM(si.quantity), 0) AS qty
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.sale_id
    WHERE DATE(s.sale_date) = CURDATE()
")->fetch_assoc();

// ---- Low stock count (products + ingredients) ----
// A product's stock is never a stored number — it's calculated here
// from its recipe against the Ingredients store, so this can't drift
// out of sync with what's physically available.
$makeableQuantities = computeMakeableQuantities($conn);
$productReorderLevels = $conn->query("
    SELECT p.product_id, p.product_name, i.reorder_level
    FROM products p
    LEFT JOIN inventory i ON p.product_id = i.product_id
")->fetch_all(MYSQLI_ASSOC);

$lowStockItems = [];
$lowPizzaCount = 0;
foreach ($productReorderLevels as $p) {
    $qty = $makeableQuantities[$p["product_id"]] ?? 0;
    $reorder = $p["reorder_level"] ?? 5;
    if ($qty <= $reorder) {
        $lowPizzaCount++;
        $lowStockItems[] = [
            "product_name" => $p["product_name"],
            "quantity" => $qty,
            "reorder_level" => $reorder,
            "type" => "Product",
        ];
    }
}

$lowIngredient = $conn->query("
    SELECT COUNT(*) AS cnt FROM ingredients WHERE quantity_in_stock <= reorder_level
")->fetch_assoc();

$lowIngredientItems = $conn->query("
    SELECT ingredient_name AS product_name, quantity_in_stock AS quantity, reorder_level, 'Ingredient' AS type
    FROM ingredients
    WHERE quantity_in_stock <= reorder_level
")->fetch_all(MYSQLI_ASSOC);

$lowStockItems = array_merge($lowStockItems, $lowIngredientItems);
usort($lowStockItems, fn($a, $b) => $a["quantity"] <=> $b["quantity"]);

$lowStockTotal = $lowPizzaCount + $lowIngredient["cnt"];

// ---- 7-day revenue trend ----
$trendResult = $conn->query("
    SELECT DATE(sale_date) AS d, SUM(total_amount) AS revenue
    FROM sales WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(sale_date)
")->fetch_all(MYSQLI_ASSOC);

$trendByDate = [];
foreach ($trendResult as $row) {
    $trendByDate[$row["d"]] = (float) $row["revenue"];
}

$last7Days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i days"));
    $last7Days[] = ["label" => date("D", strtotime($date)), "revenue" => $trendByDate[$date] ?? 0];
}

$maxRevenue = max(array_column($last7Days, "revenue"));
if ($maxRevenue == 0) $maxRevenue = 1;

$chartWidth  = 700;
$chartHeight = 140;
$stepX = $chartWidth / (count($last7Days) - 1);
$points = [];
foreach ($last7Days as $i => $day) {
    $x = $i * $stepX;
    $y = $chartHeight - (($day["revenue"] / $maxRevenue) * ($chartHeight - 10));
    $points[] = round($x, 1) . "," . round($y, 1);
}
$polylinePoints = implode(" ", $points);
$areaPoints = "0,{$chartHeight} " . $polylinePoints . " {$chartWidth},{$chartHeight}";

// ---- Top 3 sellers this week (from sale_items) ----
$topSellers = $conn->query("
    SELECT si.product_name, SUM(si.quantity) AS qty_sold
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.sale_id
    WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY si.product_name
    ORDER BY qty_sold DESC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Shelly's Pizza</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-layout">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content dashboard-dark">

        <div class="dashboard-greeting">
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?> 🍕</h2>
            <p>Here's how Shelly's Pizza is doing right now.</p>
        </div>

        <!-- Stat tiles -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-value">KES <?php echo number_format($today["revenue"], 0); ?></div>
                <div class="stat-change <?php echo $pctChange >= 0 ? 'up' : 'down'; ?>">
                    <?php echo $pctChange >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($pctChange), 1); ?>% vs yesterday
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Sales Today</div>
                <div class="stat-value"><?php echo $today["cnt"]; ?></div>
                <div class="stat-change neutral">transactions recorded</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pizzas Sold Today</div>
                <div class="stat-value"><?php echo $pizzasToday["qty"]; ?></div>
                <div class="stat-change neutral">units</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Low Stock Alerts</div>
                <div class="stat-value"><?php echo $lowStockTotal; ?></div>
                <div class="stat-change <?php echo $lowStockTotal > 0 ? 'down' : 'up'; ?>">
                    <?php echo $lowStockTotal > 0 ? 'needs attention' : 'all good'; ?>
                </div>
            </div>
        </div>

        <!-- Low stock alert panel (only shown when there are alerts) -->
        <?php if (!empty($lowStockItems)): ?>
        <div class="dark-panel" style="margin-bottom:1.5rem; border-left: 4px solid #A8352A;">
            <h3>⚠️ Low Stock Alerts</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item["product_name"]); ?></td>
                            <td>
                                <span class="pill pill-category">
                                    <?php echo $item["type"]; ?>
                                </span>
                            </td>
                            <td><?php echo $item["quantity"]; ?></td>
                            <td><?php echo $item["reorder_level"]; ?></td>
                            <td>
                                <?php if ($item["quantity"] == 0): ?>
                                    <span style="color:#A8352A; font-weight:700;">Out of stock</span>
                                <?php else: ?>
                                    <span style="color:#B8834D; font-weight:600;">Running low</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:0.8rem;">
                <a href="inventory/index.php" class="btn-secondary" style="margin-right:.5rem;">📦 Update Inventory</a>
                <a href="ingredients/index.php" class="btn-secondary">🧂 Update Ingredients</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Trend chart + top sellers -->
        <div class="dark-panel-grid">
            <div class="dark-panel">
                <h3>Revenue — Last 7 Days</h3>
                <svg viewBox="0 0 700 160" style="width: 100%; height: 160px;">
                    <defs>
                        <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#A8352A" stop-opacity="0.5"/>
                            <stop offset="100%" stop-color="#A8352A" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <polygon points="<?php echo $areaPoints; ?>" fill="url(#areaFill)"/>
                    <polyline points="<?php echo $polylinePoints; ?>" fill="none" stroke="#B8834D" stroke-width="2.5"/>
                    <?php foreach ($points as $p): ?>
                        <?php [$px, $py] = explode(",", $p); ?>
                        <circle cx="<?php echo $px; ?>" cy="<?php echo $py; ?>" r="3.5" fill="#F3E9D2"/>
                    <?php endforeach; ?>
                </svg>
                <div style="display:flex; justify-content:space-between; margin-top:0.5rem;">
                    <?php foreach ($last7Days as $day): ?>
                        <span style="font-size:0.72rem; color:var(--color-caramel-light);"><?php echo $day["label"]; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dark-panel">
                <h3>Top Sellers This Week</h3>
                <?php if (empty($topSellers)): ?>
                    <p style="color:var(--color-caramel-light); font-size:0.85rem;">No sales yet this week.</p>
                <?php else: ?>
                    <?php foreach ($topSellers as $i => $seller): ?>
                        <div class="top-seller-row">
                            <div>
                                <div class="top-seller-name"><?php echo htmlspecialchars($seller["product_name"]); ?></div>
                                <div class="top-seller-qty"><?php echo $seller["qty_sold"]; ?> sold</div>
                            </div>
                            <?php if ($i === 0): ?>
                                <span class="top-seller-badge">#1</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick links -->
        <div class="dark-panel">
            <h3>Quick Actions</h3>
            <div class="quick-links-row">
                <a href="sales/new_sale.php" class="quick-link-btn">🧾 Record a Sale</a>
                <a href="products/index.php" class="quick-link-btn">🍕 Manage Products</a>
                <a href="inventory/index.php" class="quick-link-btn">📦 View Inventory</a>
                <a href="ingredients/index.php" class="quick-link-btn">🧂 Manage Ingredients</a>
                <a href="reports/index.php" class="quick-link-btn">📊 Full Reports</a>
            </div>
        </div>

    </main>
</div>

</body>
</html>