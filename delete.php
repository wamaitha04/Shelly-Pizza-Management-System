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

// Safety check #1: never let someone delete their own account —
// that could lock the only logged-in user out mid-session.
if ($user_id === (int) $_SESSION["user_id"]) {
    header("Location: index.php");
    exit();
}

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Safety check #2: a manager cannot delete an owner or manager
    // account — only the Owner can.
    if ($target && !isOwner() && in_array($target["role"], MANAGEMENT_ROLES, true)) {
        header("Location: index.php");
        exit();
    }

    // Safety check #3: if this account is an owner, make sure it's
    // not the LAST owner — otherwise nobody would be left who can
    // manage staff accounts at all.
    if ($target && $target["role"] === "owner") {
        $ownerCount = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'owner'")->fetch_assoc();
        if ($ownerCount["cnt"] <= 1) {
            header("Location: index.php");
            exit();
        }
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: index.php");
exit();
