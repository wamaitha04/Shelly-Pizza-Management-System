<?php
session_start();

include '../config/db_connect.php';
$error = "";
$notice = "";

// Flash-style messages passed via redirect, not stored anywhere —
// they only show up once, right after registering or logging out.
if (isset($_GET["registered"])) {
    $notice = "Account created! You can now log in.";
} elseif (isset($_GET["loggedout"])) {
    $notice = "You've been logged out.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, role, status FROM users WHERE username = ?");

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
    
        $user = $result->fetch_assoc();

        
        if (password_verify($password, $user["password_hash"])) {

            if ($user["status"] !== "approved") {
                $error = "Your account is still awaiting approval from an Owner or Manager. Please check back soon.";
            } else {
                $_SESSION["user_id"]  = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"]     = $user["role"];

                header("Location: ../dashboard.php");
                exit();
            }
        } else {
            $error = "Incorrect username or password.";
        }
    } else {
        
        $error = "Incorrect username or password.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Shelly's Pizza</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">

    <div class="login-container">
        <img src="../assets/img/logo.jpg" alt="Shelly's Pizza logo" class="login-logo">

        <h1>Shelly's Pizza</h1>
        <h2>Inventory &amp; Sales Management</h2>

        <?php if ($notice): ?>
            <p class="success-message"><?php echo htmlspecialchars($notice); ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Log In</button>
        </form>

        <p style="text-align:center; margin-top:1rem; font-size:0.9rem;">
            New here? <a href="register.php">Create an account</a>
        </p>
    </div>

</body>
</html>
