<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/auth.php';
requireRole(MANAGEMENT_ROLES);

include '../config/db_connect.php';

$user_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT role, status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Self-registration only ever requests a sub-user role, so any
    // pending account here is safe for a manager to approve too —
    // there's nothing here a manager isn't already allowed to grant.
    if ($target && $target["status"] === "pending" && in_array($target["role"], SUBUSER_ROLES, true)) {
        $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php");
exit();
