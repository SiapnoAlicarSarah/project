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
        .login-wrap {
            display: flex;
            gap: 28px;
            max-width: 960px;
            margin: 30px auto;
            align-items: stretch;
        }
        .login-brand {
            flex: 1;
            min-width: 260px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #60a5fa 100%);
            color: #fff;
            border-radius: 18px;
            padding: 36px 32px;
            box-shadow: 0 16px 40px rgba(37,99,235,0.3);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-brand h2 { color: #fff; font-size: 34px; margin-bottom: 12px; }
        .login-brand p { color: #dbeafe; font-size: 15px; margin-bottom: 18px; }
        .login-brand img { width: 100%; height: 180px; object-fit: cover; border-radius: 12px; margin-top: 14px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
        .login-brand ul { margin: 0; padding-left: 18px; color: #dbeafe; font-size: 14px; }
        .login-brand li { margin-bottom: 6px; }
        .auth-card {
            flex: 1;
            min-width: 300px;
            max-width: 420px;
            margin: 0;
            background: #fff;
            padding: 36px 34px;
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(37,99,235,0.18);
            border: 1px solid #cbd5e1;
        }
        .auth-card h1 { text-align: center; color: #1d4ed8; margin-bottom: 22px; }
        .auth-card input[type="text"],
        .auth-card input[type="password"] { width: 100%; padding: 11px 13px; }
        .auth-card .msg { color: #dc2626; font-weight: 600; text-align: center; }
        @media (max-width: 768px) {
            .login-wrap { flex-direction: column; }
            .login-brand img { height: 140px; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">Fit<span>Sync</span></a>
        <div class="nav-links">
            <a href="index.php" class="nav-cta">Home</a>
            <a href="register.php" class="nav-cta">Register</a>
        </div>
    </nav>

    <div class="login-wrap">
        <div class="login-brand">
            <h2>Welcome Back</h2>
            <p>FitSync keeps your gym membership organized: browse plans, track your expiry date, and view your payments in one place.</p>
            <ul>
                <li>Smart expiration tracking</li>
                <li>Secure payment records</li>
                <li>Role-based dashboards</li>
            </ul>
            <img src="images/gym.png" alt="Gym">
        </div>
        <div class="auth-card">
            <h1>Login</h1>

            <form method="POST">
                <label for="email">Email:</label>
                <input type="text" name="email" id="email" required>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>

                <input type="submit" name="sub" value="Login" style="width:100%;">
            </form>

            <?php if ($message !== ""): ?>
                <p class="msg"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <p style="text-align:center; margin-top:12px;">No account yet? <a href="register.php">Register</a></p>
        </div>
    </div>

</body>
</html>