<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/auth.php';
requireRole(MANAGEMENT_ROLES);

include '../config/db_connect.php';
$activePage = "users";

$result = $conn->query("SELECT user_id, username, email, phone, role, status, created_at FROM users ORDER BY created_at DESC");
$rows = $result->fetch_all(MYSQLI_ASSOC);

$pendingRows  = array_values(array_filter($rows, fn($r) => $r["status"] === "pending"));
$approvedRows = array_values(array_filter($rows, fn($r) => $r["status"] !== "pending"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Shelly's Pizza</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php if (!empty($pendingRows)): ?>
        <div class="card" style="margin-bottom:1.5rem; border-left: 4px solid #B8834D;">
            <h2>⏳ Pending Approval (<?php echo count($pendingRows); ?>)</h2>
            <p style="color: var(--color-caramel-dark); font-size: 0.85rem; margin-bottom: 1rem;">
                These accounts registered themselves and can't log in yet. Check the role they
                requested — you can change it on Edit before or after approving.
            </p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Requested Role</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["username"]); ?></td>
                            <td><?php echo htmlspecialchars($row["email"] ?? "—"); ?></td>
                            <td><?php echo htmlspecialchars($row["phone"] ?? "—"); ?></td>
                            <td><span class="pill pill-category"><?php echo htmlspecialchars($row["role"]); ?></span></td>
                            <td><?php echo date("d M Y", strtotime($row["created_at"])); ?></td>
                            <td>
                                <a href="approve.php?id=<?php echo $row['user_id']; ?>"
                                   onclick="return confirm('Approve this account? They will be able to log in as ' + '<?php echo htmlspecialchars($row['role'], ENT_QUOTES); ?>' + ' right away.');">Approve</a>
                                &nbsp;|&nbsp;
                                <a href="edit.php?id=<?php echo $row['user_id']; ?>">Edit Role</a>
                                &nbsp;|&nbsp;
                                <a href="reject.php?id=<?php echo $row['user_id']; ?>"
                                   onclick="return confirm('Reject and delete this registration request? This cannot be undone.');"
                                   class="link-danger">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header-row">
                <h2>Staff Accounts</h2>
                <a href="add.php" class="btn-primary">+ Add User</a>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvedRows as $row): ?>
                        <?php
                            // Managers can only manage cashier/waiter/cook accounts —
                            // owner/manager accounts are locked to them.
                            $isLockedForManager = !isOwner() && in_array($row["role"], MANAGEMENT_ROLES, true);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["username"]); ?></td>
                            <td><?php echo htmlspecialchars($row["email"] ?? "—"); ?></td>
                            <td><?php echo htmlspecialchars($row["phone"] ?? "—"); ?></td>
                            <td><span class="pill pill-category"><?php echo htmlspecialchars($row["role"]); ?></span></td>
                            <td><?php echo date("d M Y", strtotime($row["created_at"])); ?></td>
                            <td>
                                <?php if ($isLockedForManager): ?>
                                    <span style="color:#aaa;">No access</span>
                                <?php else: ?>
                                    <a href="edit.php?id=<?php echo $row['user_id']; ?>">Edit</a>
                                    <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                        &nbsp;|&nbsp;
                                        <a href="delete.php?id=<?php echo $row['user_id']; ?>"
                                           onclick="return confirm('Delete this user account? This cannot be undone.');"
                                           class="link-danger">Delete</a>
                                    <?php endif; ?>
                                <?php endif; ?>
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
