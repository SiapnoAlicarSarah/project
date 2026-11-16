<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
use App\Models\Authentication;

$message = "";
if (isset($_POST["sub"])) {
    $name = $_POST["name"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "client";
    $staffCode = $_POST["staff_code"] ?? "";

    $auth = new Authentication();
    $message = $auth->register($name, $email, $password, $role, $staffCode);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FitSync</title>
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
        .auth-card input[type="password"],
        .auth-card select { width: 100%; }
        .auth-card .msg { font-weight: 600; color: #2563eb; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">Fit<span>Sync</span></a>
        <div class="nav-links">
            <a href="index.php" class="nav-cta">Home</a>
            <a href="login.php" class="nav-cta">Login</a>
        </div>
    </nav>

    <div class="auth-card">
        <h1>Register</h1>

        <form method="POST">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required> <br><br>

            <label for="email">Email:</label>
            <input type="text" name="email" id="email" required> <br><br>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required> <br><br>

            <label for="role">Register as:</label>
            <select name="role" id="role" onchange="toggleCode()">
                <option value="client">Client</option>
                <option value="staff">Staff</option>
            </select> <br><br>

            <div id="codeBox" style="display:none;">
                <label for="staff_code">Staff Access Code:</label>
                <input type="password" name="staff_code" id="staff_code"> <br><br>
            </div>

            <input type="submit" name="sub" value="Register">
        </form>

        <?php if ($message !== ""): ?>
            <p class="msg"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>

    <script>
        function toggleCode() {
            var role = document.getElementById("role").value;
            document.getElementById("codeBox").style.display = (role === "staff") ? "block" : "none";
        }
    </script>

</body>
</html>