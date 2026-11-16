<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "owner") {
    header("Location: ../index.php");
    exit;
}

require __DIR__ . '/staff_top.php';

$db = (new Database())->connection();
$clients = $db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$staff   = $db->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - FitSync</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo">Fit<span>Sync</span></div>

        <div class="nav-links">
            <button type="button" id="openPlan">Create Membership Plan</button>
            <button type="button" id="openView">View Membership Plans</button>
            <button type="button" id="openManage">Manage Plans</button>
            <button type="button" id="openUserPlans">Manage User Plans</button>
        </div>

        <div class="nav-right">
            <button type="button" class="nav-btn" id="openNotif">
                Notifications<?php if (count($pendingRequests) > 0): ?><span class="badge"><?= count($pendingRequests) ?></span><?php endif; ?>
            </button>
            <span class="nav-user">Hi, <span><?= htmlspecialchars($_SESSION["name"]) ?></span></span>
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </nav>

    <?php if ($flash !== ""): ?>
        <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <h1>Owner Dashboard</h1>
    <p>Registered clients: <strong><?= (int) $clients ?></strong></p>
    <p>Staff accounts: <strong><?= (int) $staff ?></strong></p>
    <p>Pending membership requests: <strong><?= count($pendingRequests) ?></strong></p>

    <?php require __DIR__ . '/plan_section.php'; ?>
    <?php require __DIR__ . '/staff_popup.php'; ?>
    <?php require __DIR__ . '/manage_users.php'; ?>

</body>
</html>