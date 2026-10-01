<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/auth.php';
requireRole(MANAGEMENT_ROLES);

include '../config/db_connect.php';
$activePage = "staff";

// Overall performance per staff member
$staffStats = $conn->query("
    SELECT 
        u.username,
        u.role,
        COUNT(s.sale_id) AS total_sales,
        IFNULL(SUM(s.total_amount), 0) AS total_revenue,
        IFNULL(AVG(s.total_amount), 0) AS avg_sale,
        MAX(s.sale_date) AS last_sale
    FROM users u
    LEFT JOIN sales s ON u.user_id = s.user_id
    GROUP BY u.user_id, u.username, u.role
    ORDER BY total_revenue DESC
")->fetch_all(MYSQLI_ASSOC);

// Today's performance per staff member
$todayStats = $conn->query("
    SELECT 
        u.username,
        COUNT(s.sale_id) AS sales_today,
        IFNULL(SUM(s.total_amount), 0) AS revenue_today
    FROM users u
    LEFT JOIN sales s ON u.user_id = s.user_id 
        AND DATE(s.sale_date) = CURDATE()
    GROUP BY u.user_id, u.username
    ORDER BY revenue_today DESC
")->fetch_all(MYSQLI_ASSOC);

// Top product per staff — get all product totals per user, then pick max in PHP
$productRows = $conn->query("
    SELECT 
        u.username,
        si.product_name,
        SUM(si.quantity) AS qty
    FROM users u
    JOIN sales s ON u.user_id = s.user_id
    JOIN sale_items si ON s.sale_id = si.sale_id
    GROUP BY u.username, si.product_name
    ORDER BY u.username, qty DESC
")->fetch_all(MYSQLI_ASSOC);

// Build top product map: keep only the first (highest qty) row per username
$topProductMap = [];
foreach ($productRows as $row) {
    if (!isset($topProductMap[$row["username"]])) {
        $topProductMap[$row["username"]] = $row["product_name"] . " (" . $row["qty"] . " sold)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Performance - Shelly's Pizza</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">

        <!-- Today's leaderboard -->
        <div class="card" style="margin-bottom:1.5rem;">
            <h2>📅 Today's Leaderboard</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Sales Today</th>
                        <th>Revenue Today (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todayStats as $i => $row): ?>
                        <tr>
                            <td>
                                <?php if ($i === 0 && $row["sales_today"] > 0): ?>
                                    🥇
                                <?php endif; ?>
                                <?php echo htmlspecialchars($row["username"]); ?>
                            </td>
                            <td><?php echo $row["sales_today"]; ?></td>
                            <td><?php echo number_format($row["revenue_today"], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- All-time performance -->
        <div class="card">
            <h2>🏅 All-Time Staff Performance</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Role</th>
                        <th>Total Sales</th>
                        <th>Total Revenue (KES)</th>
                        <th>Avg Sale (KES)</th>
                        <th>Top Product</th>
                        <th>Last Sale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffStats as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["username"]); ?></td>
                            <td>
                                <span class="pill pill-category">
                                    <?php echo htmlspecialchars($row["role"]); ?>
                                </span>
                            </td>
                            <td><?php echo $row["total_sales"]; ?></td>
                            <td><?php echo number_format($row["total_revenue"], 2); ?></td>
                            <td><?php echo number_format($row["avg_sale"], 2); ?></td>
                            <td>
                                <?php echo $topProductMap[$row["username"]] 
                                    ?? "<span style='color:#aaa;'>—</span>"; ?>
                            </td>
                            <td>
                                <?php echo $row["last_sale"] 
                                    ? date("d M Y, g:i A", strtotime($row["last_sale"])) 
                                    : "<span style='color:#aaa;'>Never</span>"; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>
</body>
</html>
