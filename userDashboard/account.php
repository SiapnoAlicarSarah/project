<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Models\Subscription;

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

$db = (new Database())->connection();
$userId = (int) $_SESSION["user_id"];
$role = $_SESSION["role"];
$message = "";

if (isset($_POST["update_profile"])) {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    if ($name === "" || $email === "") {
        $message = "Name and email are required.";
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $userId]);
        $_SESSION["name"] = $name;
        $message = "Profile updated successfully.";
    }
}

if (isset($_POST["change_password"])) {
    $current = $_POST["current_password"] ?? "";
    $new = $_POST["new_password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $message = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $message = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $message = "New passwords do not match.";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$newHash, $userId]);
        $message = "Password updated successfully.";
    }
}

if (isset($_POST["delete_account"])) {
    $db->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userId]);
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$stmt = $db->prepare("SELECT name, email, role, created_at FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$sub = new Subscription();
$membership = null;
$counts = null;
if ($role === "client") {
    $clientId = $sub->getClientId($userId);
    $membership = $sub->latest($clientId);
} elseif (in_array($role, ["staff", "owner"], true)) {
    $counts = $sub->counts();
}
$initials = strtoupper(substr($user["name"], 0, 1) . substr(strstr($user["name"], " ") ?: "", 1, 1));

$dashboard = $role === "client" ? "userDashboard.php" : ($role === "staff" ? "staffDashboard.php" : "ownerDashboard.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FitSync</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <a href="../index.php" class="logo">Fit<span>Sync</span></a>
        <nav class="side-nav">
            <a href="<?= $dashboard ?>">Dashboard</a>
            <a href="#" class="active">Account</a>
        </nav>
        <div class="side-foot">
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div>
                <h1>My Profile</h1>
                <p class="muted">Manage your FitSync account details.</p>
            </div>
            <a href="<?= $dashboard ?>" class="nav-cta">Back to Dashboard</a>
        </header>

        <?php if ($message !== ""): ?>
            <div class="flash"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="card profile-hero">
            <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            <div>
                <h2 style="margin:0;"><?= htmlspecialchars($user["name"]) ?></h2>
                <p class="muted"><?= htmlspecialchars($user["email"]) ?></p>
                <span class="role-badge"><?= htmlspecialchars(ucfirst($role)) ?></span>
            </div>
        </section>

        <div class="account-grid">
            <section class="card">
                <div class="card-head"><div><h2>Profile Information</h2><p class="muted">Your account details.</p></div></div>
                <div class="info-row"><span>Name</span><span><?= htmlspecialchars($user["name"]) ?></span></div>
                <div class="info-row"><span>Email</span><span><?= htmlspecialchars($user["email"]) ?></span></div>
                <div class="info-row"><span>Role</span><span><?= htmlspecialchars(ucfirst($role)) ?></span></div>
                <?php if (!empty($user["created_at"])): ?>
                    <div class="info-row"><span>Member since</span><span><?= date("M d, Y", strtotime($user["created_at"])) ?></span></div>
                <?php endif; ?>
                <?php if ($role === "client" && $membership): ?>
                    <div class="info-row"><span>Membership</span><span><?= htmlspecialchars($membership["plan_name"]) ?></span></div>
                    <div class="info-row"><span>Expires</span><span><?= date("M d, Y", strtotime($membership["end_date"])) ?></span></div>
                <?php elseif ($counts): ?>
                    <div class="info-row"><span>Clients</span><span><?= $counts["clients"] ?></span></div>
                    <div class="info-row"><span>Memberships</span><span><?= $counts["memberships"] ?></span></div>
                    <div class="info-row"><span>Revenue</span><span>₱<?= number_format((float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status='paid'")->fetchColumn(), 2) ?></span></div>
                <?php endif; ?>
            </section>

            <section class="card">
                <div class="card-head"><div><h2>Edit Profile</h2><p class="muted">Update your name and email.</p></div></div>
                <form method="POST">
                    <label>Name:</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user["name"]) ?>" required>
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user["email"]) ?>" required>
                    <button type="submit" name="update_profile">Save Changes</button>
                </form>
            </section>

            <section class="card">
                <div class="card-head"><div><h2>Change Password</h2><p class="muted">Use at least 6 characters.</p></div></div>
                <form method="POST">
                    <label>Current Password:</label>
                    <input type="password" name="current_password" required>
                    <label>New Password:</label>
                    <input type="password" name="new_password" required>
                    <label>Confirm New Password:</label>
                    <input type="password" name="confirm_password" required>
                    <button type="submit" name="change_password">Update Password</button>
                </form>
            </section>

            <section class="card danger-zone">
                <div class="card-head"><div><h2>Danger Zone</h2><p class="muted">This cannot be undone.</p></div></div>
                <p style="font-size:14px; color:#7f1d1d;">Deleting your account is permanent.</p>
                <form method="POST" onsubmit="return confirm('Delete your account? This cannot be undone.');">
                    <button type="submit" name="delete_account" class="btn-danger">Delete Account</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
