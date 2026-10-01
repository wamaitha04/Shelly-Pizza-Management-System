<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/auth.php';
requireRole(MANAGEMENT_ROLES);

include '../config/db_connect.php';
include '../includes/validation.php';
$activePage = "users";

$user_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
if ($user_id === 0) {
    header("Location: index.php");
    exit();
}

$errors = [];
$success = "";

// Load the target account first so we know its CURRENT role before
// deciding whether this logged-in user is even allowed to touch it.
$stmt = $conn->prepare("SELECT username, email, phone, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: index.php");
    exit();
}

// A manager cannot edit an owner or manager account — only the Owner
// can. This stops a manager from, say, changing another manager's
// password or role.
if (!isOwner() && in_array($user["role"], MANAGEMENT_ROLES, true)) {
    header("Location: index.php");
    exit();
}

$allowedRoles = assignableRoles();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role        = $_POST["role"];
    $email       = trim($_POST["email"] ?? "");
    $phone       = normalizePhone($_POST["phone"] ?? "");
    $newPassword = $_POST["password"]; // optional — leave blank to keep current password

    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = "You're not allowed to assign that role.";
    }
    if ($email === "" || $phone === "") {
        $errors[] = "Email and phone are both required.";
    }
    if ($email !== "" && !isValidEmail($email)) {
        $errors[] = "That doesn't look like a valid email address.";
    }
    if ($phone !== "" && !isValidPhone($phone)) {
        $errors[] = "That doesn't look like a valid phone number (digits only, optionally starting with +).";
    }
    if ($newPassword !== "") {
        $strengthError = passwordStrengthError($newPassword);
        if ($strengthError) {
            $errors[] = $strengthError;
        }
    }

    if (empty($errors)) {
        // Uniqueness check EXCLUDING this user's own row — otherwise
        // saving the form without changing the email would always
        // "conflict" with itself.
        $stmt = $conn->prepare(
            "SELECT email, phone FROM users WHERE (email = ? OR phone = ?) AND user_id != ?"
        );
        $stmt->bind_param("ssi", $email, $phone, $user_id);
        $stmt->execute();
        $conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($conflicts as $row) {
            if ($row["email"] === $email) $errors[] = "That email is already registered to another account.";
            if ($row["phone"] === $phone) $errors[] = "That phone number is already registered to another account.";
        }
        $errors = array_unique($errors);
    }

    if (empty($errors)) {
        if ($newPassword !== "") {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "UPDATE users SET role = ?, email = ?, phone = ?, password_hash = ? WHERE user_id = ?"
            );
            $stmt->bind_param("ssssi", $role, $email, $phone, $passwordHash, $user_id);
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET role = ?, email = ?, phone = ? WHERE user_id = ?"
            );
            $stmt->bind_param("sssi", $role, $email, $phone, $user_id);
        }
        $stmt->execute();
        $stmt->close();
        $success = "User updated.";
        // Reflect the change immediately in the form below.
        $user["role"] = $role;
        $user["email"] = $email;
        $user["phone"] = $phone;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - Shelly's Pizza</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="card" style="max-width: 480px; margin: 0 auto;">
            <h2>Edit User: <?php echo htmlspecialchars($user["username"]); ?></h2>

            <?php foreach ($errors as $err): ?>
                <p class="error-message"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
            <?php if ($success): ?>
                <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>

            <form method="POST" action="edit.php?id=<?php echo $user_id; ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($user["email"] ?? ""); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($user["phone"] ?? ""); ?>" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <?php foreach ($allowedRoles as $r): ?>
                            <option value="<?php echo $r; ?>" <?php echo $user['role'] === $r ? 'selected' : ''; ?>>
                                <?php echo ucfirst($r); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password">
                    <p style="font-size:0.8rem; color: var(--color-caramel-dark); margin-top:0.2rem;">
                        If setting a new one: 8+ characters, upper &amp; lower case, a number, and a symbol.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" style="border:none; cursor:pointer;">Save Changes</button>
                    <a href="index.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>
