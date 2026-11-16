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

/* ---------- Update profile ---------- */
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

/* ---------- Change password ---------- */
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

/* ---------- Delete account ---------- */
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FitSync</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Oswald', sans-serif;
            font-size: 28px;
            font-weight: 700;
            box-shadow: 0 6px 16px rgba(37,99,235,0.3);
        }
        .profile-meta h1 { margin-bottom: 4px; }
        .role-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            background: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }
        .profile-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 6px 18px rgba(37,99,235,0.12);
            border: 1px solid #cbd5e1;
        }
        .profile-card h2 { margin-top: 0; font-size: 18px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-row span:first-child { color: #475569; font-weight: 600; }
        .danger-zone { border: 1px solid #fecaca; background: #fef2f2; }
        .danger-zone h2 { color: #dc2626; }
        .btn-danger { background: #dc2626; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-danger:hover { background: #b91c1c; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../index.php" class="logo">Fit<span>Sync</span></a>
        <div class="nav-links">
            <a href="../index.php" class="nav-cta">Home</a>
            <?php if ($role === "client"): ?>
                <a href="userDashboard.php" class="nav-cta">Dashboard</a>
            <?php elseif ($role === "staff"): ?>
                <a href="staffDashboard.php" class="nav-cta">Dashboard</a>
            <?php else: ?>
                <a href="ownerDashboard.php" class="nav-cta">Dashboard</a>
            <?php endif; ?>
        </div>
        <div class="nav-right">
            <span class="nav-user">Hi, <span><?= htmlspecialchars($_SESSION["name"]) ?></span></span>
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </nav>

    <?php if ($message !== ""): ?>
        <div class="flash"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="profile-header">
        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="profile-meta">
            <h1><?= htmlspecialchars($user["name"]) ?></h1>
            <p style="margin:0; color:#475569;"><?= htmlspecialchars($user["email"]) ?></p>
            <span class="role-badge"><?= htmlspecialchars($role) ?></span>
        </div>
    </div>

    <div class="profile-grid">
        <!-- Profile info -->
        <div class="profile-card">
            <h2>Profile Information</h2>
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
        </div>

        <!-- Edit profile -->
        <div class="profile-card">
            <h2>Edit Profile</h2>
            <form method="POST">
                <label>Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user["name"]) ?>" required>
                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user["email"]) ?>" required>
                <button type="submit" name="update_profile">Save Changes</button>
            </form>
        </div>

        <!-- Change password -->
        <div class="profile-card">
            <h2>Change Password</h2>
            <form method="POST">
                <label>Current Password:</label>
                <input type="password" name="current_password" required>
                <label>New Password:</label>
                <input type="password" name="new_password" required>
                <label>Confirm New Password:</label>
                <input type="password" name="confirm_password" required>
                <button type="submit" name="change_password">Update Password</button>
            </form>
        </div>

        <!-- Danger zone -->
        <div class="profile-card danger-zone">
            <h2>Danger Zone</h2>
            <p style="font-size:14px; color:#7f1d1d; margin-bottom:12px;">Deleting your account is permanent and cannot be undone.</p>
            <form method="POST" onsubmit="return confirm('Delete your account? This cannot be undone.');">
                <button type="submit" name="delete_account" class="btn-danger">Delete Account</button>
            </form>
        </div>
    </div>
</body>
</html>