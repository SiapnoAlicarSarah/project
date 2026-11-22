<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ["staff", "owner"], true)) {
    header("Location: ../index.php");
    exit;
}

if ($_SESSION["role"] === "staff") {
    $db = (new App\Database())->connection();
    $st = $db->prepare("SELECT status FROM staff WHERE user_id = ?");
    $st->execute([(int) $_SESSION["user_id"]]);
    if ($st->fetchColumn() !== "approved") {
        header("Location: ../index.php");
        exit;
    }
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
<body class="app-body">
    <aside class="sidebar">
        <a href="#" class="logo">Fit<span>Sync</span></a>
        <nav class="side-nav">
            <a href="#overview" class="active">Overview</a>
            <a href="#plans">Plans</a>
            <a href="#memberships">Memberships</a>
            <a href="#clients">Clients</a>
            <button type="button" id="openNotif" class="side-notif">Notifications<?php if (count($pendingRequests) > 0): ?><span class="badge"><?= count($pendingRequests) ?></span><?php endif; ?></button>
        </nav>
        <div class="side-foot">
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div>
                <h1>Staff Dashboard</h1>
                <p class="muted">Welcome back, <?= htmlspecialchars($_SESSION["name"]) ?></p>
            </div>
            <a href="account.php" class="nav-cta">Account</a>
        </header>

        <?php if ($flash !== ""): ?>
            <div class="flash"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <?php $sc = $sub->counts(); ?>
        <section class="card" id="overview">
            <div class="card-head"><div><h2>Overview</h2><p class="muted">Key numbers at a glance.</p></div></div>
            <div class="stat-grid">
                <div class="stat-card"><div class="label">Clients</div><div class="value"><?= $sc["clients"] ?></div></div>
                <div class="stat-card"><div class="label">Staff</div><div class="value"><?= $sc["staff"] ?></div></div>
                <div class="stat-card"><div class="label">Pending requests</div><div class="value"><?= count($pendingRequests) ?></div></div>
                <div class="stat-card"><div class="label">Memberships</div><div class="value"><?= $sc["memberships"] ?></div><div class="sub">active <?= $sc["active"] ?> · expired <?= $sc["expired"] ?></div></div>
            </div>
        </section>

        <?php require __DIR__ . '/plan_section.php'; ?>
        <?php require __DIR__ . '/manage_users.php'; ?>
        <?php require __DIR__ . '/manage_clients.php'; ?>
    </main>

    <?php require __DIR__ . '/staff_popup.php'; ?>
</body>
</html>
