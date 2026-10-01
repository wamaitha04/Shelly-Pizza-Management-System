<?php

require_once __DIR__ . '/auth.php';

$activePage = $activePage ?? "";

function navClass($page, $activePage) {
    return $page === $activePage ? "sidebar-link active" : "sidebar-link";
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="/shellys_pizza/assets/img/logo.jpg" alt="Shelly's Pizza logo">
        <div>
            <div class="sidebar-brand-name">Shelly's Pizza</div>
            <div class="sidebar-brand-tagline">Deliciously Crafted</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="/shellys_pizza/dashboard.php" class="<?php echo navClass('dashboard', $activePage); ?>">🏠 Dashboard</a>
        <a href="/shellys_pizza/products/index.php" class="<?php echo navClass('products', $activePage); ?>">🍕 Products</a>

        <?php if (canSell()): ?>
            <a href="/shellys_pizza/sales/new_sale.php" class="<?php echo navClass('new_sale', $activePage); ?>">🧾 New Sale</a>
            <a href="/shellys_pizza/sales/index.php" class="<?php echo navClass('sales', $activePage); ?>">📜 Sales History</a>
        <?php endif; ?>

        <?php if (isKitchen()): ?>
            <a href="/shellys_pizza/inventory/index.php" class="<?php echo navClass('inventory', $activePage); ?>">📦 Inventory</a>
            <a href="/shellys_pizza/ingredients/index.php" class="<?php echo navClass('ingredients', $activePage); ?>">🧂 Ingredients</a>
        <?php endif; ?>

        <?php if (isManagement()): ?>
            <a href="/shellys_pizza/reports/index.php" class="<?php echo navClass('reports', $activePage); ?>">📊 Reports</a>
            <a href="/shellys_pizza/reports/staff.php" class="<?php echo navClass('staff', $activePage); ?>">👥 Staff Performance</a>
            <a href="/shellys_pizza/users/index.php" class="<?php echo navClass('users', $activePage); ?>">
                👤 Manage Users
                <?php
                    // $conn is set up by whichever page included this sidebar.
                    // If it's missing for some reason, just skip the badge
                    // rather than fail the whole page.
                    if (isset($conn) && $conn instanceof mysqli) {
                        $pendingCount = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE status = 'pending'")->fetch_assoc()["cnt"];
                        if ($pendingCount > 0) {
                            echo '<span style="display:inline-block; min-width:1.2em; margin-left:0.4em; padding:0.05em 0.4em; border-radius:999px; background:#A8352A; color:#fff; font-size:0.75em; text-align:center;">' . (int) $pendingCount . '</span>';
                        }
                    }
                ?>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <?php echo htmlspecialchars($_SESSION["username"] ?? ""); ?>
            <span class="sidebar-role"><?php echo htmlspecialchars($_SESSION["role"] ?? ""); ?></span>
        </div>
        <a href="/shellys_pizza/auth/logout.php" class="sidebar-logout">Log Out</a>
    </div>
</aside>
