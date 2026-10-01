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

// A manager can only create cashier/waiter/cook accounts — the owner can
// create any role, including other managers.
$allowedRoles = assignableRoles();

$errors = [];
$old = ["username" => "", "email" => "", "phone" => "", "role" => $allowedRoles[0] ?? ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"] ?? "");
    $phone    = normalizePhone($_POST["phone"] ?? "");
    $password = $_POST["password"];
    $role     = $_POST["role"];

    $old = ["username" => $username, "email" => $email, "phone" => $phone, "role" => $role];

    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = "You're not allowed to assign that role.";
    }
    if ($username === "" || $email === "" || $phone === "") {
        $errors[] = "Username, email, and phone are all required.";
    }
    if ($email !== "" && !isValidEmail($email)) {
        $errors[] = "That doesn't look like a valid email address.";
    }
    if ($phone !== "" && !isValidPhone($phone)) {
        $errors[] = "That doesn't look like a valid phone number (digits only, optionally starting with +).";
    }
    $strengthError = $password !== "" ? passwordStrengthError($password) : "Password is required.";
    if ($strengthError) {
        $errors[] = $strengthError;
    }

    if (empty($errors)) {
        // Check username/email/phone aren't already taken — the
        // database's UNIQUE constraints would also catch this, but
        // checking here first lets us show a friendlier, specific
        // message instead of a raw database error.
        $stmt = $conn->prepare(
            "SELECT username, email, phone FROM users WHERE username = ? OR email = ? OR phone = ?"
        );
        $stmt->bind_param("sss", $username, $email, $phone);
        $stmt->execute();
        $conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($conflicts as $row) {
            if ($row["username"] === $username) $errors[] = "That username is already taken.";
            if ($row["email"] === $email)       $errors[] = "That email is already registered.";
            if ($row["phone"] === $phone)       $errors[] = "That phone number is already registered.";
        }
        $errors = array_unique($errors);
    }

    if (empty($errors)) {
        // password_hash() turns the plain-text password into a secure,
        // one-way hash — exactly the same approach used for the
        // original seed admin account in schema.sql.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $username, $email, $phone, $passwordHash, $role);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User - Shelly's Pizza</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="card" style="max-width: 480px; margin: 0 auto;">
            <h2>Add Staff Account</h2>

            <?php foreach ($errors as $err): ?>
                <p class="error-message"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>

            <?php if (!isOwner()): ?>
                <p style="color: var(--color-caramel-dark); font-size: 0.85rem; margin-bottom: 1rem;">
                    As a manager, you can create Cashier, Waiter, and Cook accounts. Only the Owner can create Manager or Owner accounts.
                </p>
            <?php endif; ?>

            <form method="POST" action="add.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($old["username"]); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($old["email"]); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="e.g. +254712345678"
                           value="<?php echo htmlspecialchars($old["phone"]); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <p style="font-size:0.8rem; color: var(--color-caramel-dark); margin-top:0.2rem;">
                        8+ characters, with upper &amp; lower case, a number, and a symbol.
                    </p>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <?php foreach ($allowedRoles as $r): ?>
                            <option value="<?php echo $r; ?>" <?php echo $old['role'] === $r ? 'selected' : ''; ?>>
                                <?php echo ucfirst($r); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" style="border:none; cursor:pointer;">Create Account</button>
                    <a href="index.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>
