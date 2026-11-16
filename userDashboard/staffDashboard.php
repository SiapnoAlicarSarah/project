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
        <a href="../index.php" class="logo">Fit<span>Sync</span></a>

        <div class="nav-links">
            <a href="../index.php" class="nav-cta">Home</a>
            <a href="operations.php" class="nav-cta">Operations</a>
        </div>

        <div class="nav-right">
            <a href="account.php" class="nav-cta">Account</a>
            <button type="button" class="nav-btn" id="openNotif">
                Notifications<?php if (count($pendingRequests) > 0): ?><span class="badge"><?= count($pendingRequests) ?></span><?php endif; ?>
            </button>
            <span class="nav-user">Hi, <span><?= htmlspecialchars($_SESSION["name"]) ?></span></span>
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </nav>

    <div class="action-bar">
        <span class="label">Manage:</span>
        <button type="button" id="openPlan">Create Plan</button>
        <button type="button" id="openView">View Plans</button>
        <button type="button" id="openManage">Manage Plans</button>
        <button type="button" id="openEdit">Quick Edit</button>
        <a href="edit_plan.php" class="nav-cta">Edit Plan</a>
        <button type="button" id="openUserPlans">User Plans</button>
        <button type="button" id="openClients">Clients</button>
    </div>

    <?php if ($flash !== ""): ?>
        <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <h1>Staff Dashboard</h1>
    <?php $sc = $sub->counts(); ?>
    <div class="stat-grid">
        <div class="stat-card">
            <div class="label">Pending Requests</div>
            <div class="value"><?= count($pendingRequests) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Registered Clients</div>
            <div class="value"><?= $sc["clients"] ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Staff Accounts</div>
            <div class="value"><?= $sc["staff"] ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Memberships</div>
            <div class="value"><?= $sc["memberships"] ?></div>
            <div class="sub">active <?= $sc["active"] ?> · expired <?= $sc["expired"] ?> · pending <?= $sc["pending"] ?></div>
        </div>
    </div>

    <?php require __DIR__ . '/plan_section.php'; ?>
    <?php require __DIR__ . '/staff_popup.php'; ?>
    <?php require __DIR__ . '/manage_users.php'; ?>
    <?php require __DIR__ . '/manage_clients.php'; ?>

</body>
</html>