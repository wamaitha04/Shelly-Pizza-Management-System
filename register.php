<?php
session_start();

// Already logged in? No reason to see the registration form.
if (isset($_SESSION["user_id"])) {
    header("Location: ../dashboard.php");
    exit();
}

include '../config/db_connect.php';
include '../includes/validation.php';
include '../includes/auth.php';

$errors = [];
// Default to the first self-registerable role so the dropdown always
// starts on something valid.
$old = ["username" => "", "email" => "", "phone" => "", "role" => SUBUSER_ROLES[0]];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $phone    = normalizePhone($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";
    $role     = $_POST["role"] ?? "";

    $old = ["username" => $username, "email" => $email, "phone" => $phone, "role" => $role];

    // ---- Required fields ----
    if ($username === "" || $email === "" || $phone === "" || $password === "") {
        $errors[] = "Please fill in every field.";
    }

    // ---- Role check ----
    // Self-registration can only ever request a sub-user role. Nobody
    // grants themselves manager/owner access through this form — that
    // still requires an Owner to create the account directly, or to
    // promote an existing account from Manage Users.
    if (!in_array($role, SUBUSER_ROLES, true)) {
        $errors[] = "Please choose a valid role.";
    }

    // ---- Format checks ("keyed in wrongly") ----
    if ($email !== "" && !isValidEmail($email)) {
        $errors[] = "That doesn't look like a valid email address.";
    }
    if ($phone !== "" && !isValidPhone($phone)) {
        $errors[] = "That doesn't look like a valid phone number (use digits only, optionally starting with +).";
    }

    // ---- Password checks ----
    if ($password !== "" && $confirm !== "" && $password !== $confirm) {
        $errors[] = "Password and confirmation password don't match.";
    }
    $strengthError = $password !== "" ? passwordStrengthError($password) : null;
    if ($strengthError) {
        $errors[] = $strengthError;
    }

    // ---- Uniqueness checks ("if any have been used before") ----
    // Only bother hitting the database if the basic checks above
    // already passed — no point checking uniqueness of a malformed
    // email.
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "SELECT username, email, phone FROM users WHERE username = ? OR email = ? OR phone = ?"
        );
        $stmt->bind_param("sss", $username, $email, $phone);
        $stmt->execute();
        $conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($conflicts as $row) {
            if ($row["username"] === $username) {
                $errors[] = "That username is already taken.";
            }
            if ($row["email"] === $email) {
                $errors[] = "That email is already registered.";
            }
            if ($row["phone"] === $phone) {
                $errors[] = "That phone number is already registered.";
            }
        }
        $errors = array_unique($errors);
    }

    if (empty($errors)) {
        // The role is whatever they asked for (already confirmed to be
        // a sub-user role above) — but the account sits as 'pending'
        // until an Owner or Manager approves it. They can also change
        // the assigned role at that point via Manage Users > Edit.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $status = "pending";

        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssss", $username, $email, $phone, $passwordHash, $role, $status);
        $stmt->execute();
        $stmt->close();

        header("Location: login.php?registered=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Shelly's Pizza</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">

    <div class="login-container">
        <img src="../assets/img/logo.jpg" alt="Shelly's Pizza logo" class="login-logo">

        <h1>Shelly's Pizza</h1>
        <h2>Create an Account</h2>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $err): ?>
                <p class="error-message"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST" action="register.php" id="register-form">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?php echo htmlspecialchars($old["username"]); ?>" required autofocus>

            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?php echo htmlspecialchars($old["email"]); ?>" required>

            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="e.g. +254712345678"
                   value="<?php echo htmlspecialchars($old["phone"]); ?>" required>

            <label for="role">I'll be working as</label>
            <select id="role" name="role" required>
                <?php foreach (SUBUSER_ROLES as $r): ?>
                    <option value="<?php echo $r; ?>" <?php echo $old['role'] === $r ? 'selected' : ''; ?>>
                        <?php echo ucfirst($r); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p style="font-size:0.8rem; margin-top:0.2rem; color: var(--color-caramel-dark);">
                An Owner or Manager will need to approve your account before you can log in —
                they can also correct your role at that point if needed.
            </p>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <div id="strength-meter" class="strength-meter"><div id="strength-bar"></div></div>
            <p id="strength-text" style="font-size:0.8rem; margin-top:0.2rem; color: var(--color-caramel-dark);">
                Use 8+ characters with upper &amp; lower case, a number, and a symbol.
            </p>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Create Account</button>
        </form>

        <p style="text-align:center; margin-top:1rem; font-size:0.9rem;">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>

    <style>
        .strength-meter { height: 6px; background: #eee; border-radius: 4px; margin-top: 0.4rem; overflow: hidden; }
        #strength-bar { height: 100%; width: 0%; background: #d9534f; transition: width 0.2s, background 0.2s; }
    </style>

    <script>
        // Lightweight live feedback only — the form still relies on the
        // server-side checks in register.php as the real source of truth.
        const pwInput = document.getElementById('password');
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');

        pwInput.addEventListener('input', () => {
            const val = pwInput.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const pct = (score / 5) * 100;
            bar.style.width = pct + '%';

            const levels = ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['#d9534f', '#d9534f', '#e0a92e', '#5bb45b', '#3c9a3c'];
            const idx = Math.max(score - 1, 0);
            bar.style.background = colors[idx];
            text.textContent = val.length === 0
                ? 'Use 8+ characters with upper & lower case, a number, and a symbol.'
                : 'Strength: ' + levels[idx];
        });

        document.getElementById('register-form').addEventListener('submit', (e) => {
            if (pwInput.value !== document.getElementById('confirm_password').value) {
                e.preventDefault();
                alert("Password and confirmation password don't match.");
            }
        });
    </script>

</body>
</html>
