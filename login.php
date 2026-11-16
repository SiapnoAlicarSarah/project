<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
use App\Models\Authentication;

$message = "";
if (isset($_POST["sub"])) {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $auth = new Authentication();
    $message = $auth->login($email, $password);

    if ($message == "Login Success") {
        $_SESSION["email"] = $email;
        if ($_SESSION["role"] === "owner") {
            header("Location: ./userDashboard/ownerDashboard.php");
        } elseif ($_SESSION["role"] === "staff") {
            header("Location: ./userDashboard/staffDashboard.php");
        } else {
            header("Location: ./userDashboard/userDashboard.php");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FitSync</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .auth-card {
            max-width: 380px;
            margin: 40px auto;
            background: #fff;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.2);
        }
        .auth-card input[type="text"],
        .auth-card input[type="password"] { width: 100%; }
        .auth-card .msg { color: #dc2626; font-weight: 600; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">Fit<span>Sync</span></div>
        <div class="nav-links">
            <a href="index.php" class="nav-cta">Back to Home</a>
        </div>
    </nav>

    <div class="auth-card">
        <h1>Login</h1>

        <form method="POST">
            <label for="email">Email:</label>
            <input type="text" name="email" id="email" required> <br><br>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required> <br><br>

            <input type="submit" name="sub" value="Login">
        </form>

        <?php if ($message !== ""): ?>
            <p class="msg"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <p>No account yet? <a href="register.php">Register</a></p>
    </div>

</body>
</html>