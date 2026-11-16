<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ["staff", "owner"], true)) {
    header("Location: ../index.php");
    exit;
}

require __DIR__ . '/staff_top.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - FitSync</title>
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

    <h1>Staff Dashboard</h1>
    <p>Pending membership requests: <strong><?= count($pendingRequests) ?></strong></p>

    <?php require __DIR__ . '/plan_section.php'; ?>
    <?php require __DIR__ . '/staff_popup.php'; ?>
    <?php require __DIR__ . '/manage_users.php'; ?>

</body>
</html>