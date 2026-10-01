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
    $stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Only ever deletes an account that's still pending — this is not
    // a general-purpose delete, so it can't be pointed at an existing
    // approved account by tampering with the URL.
    if ($target && $target["status"] === "pending") {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php");
exit();
