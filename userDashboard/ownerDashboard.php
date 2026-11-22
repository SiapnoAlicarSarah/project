<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Models\Subscription;

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "owner") {
    header("Location: ../index.php");
    exit;
}

require __DIR__ . '/staff_top.php';

if (isset($_POST["approve_staff"])) {
    $_SESSION["flash"] = $sub->approveStaff((int) ($_POST["user_id"] ?? 0));
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}
if (isset($_POST["reject_staff"])) {
    $_SESSION["flash"] = $sub->rejectStaff((int) ($_POST["user_id"] ?? 0));
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

$db = (new Database())->connection();
$clients = $db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$staff   = $db->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
$revenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status='paid'")->fetchColumn();
$pendingRevenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status='pending'")->fetchColumn();
$oc = (new Subscription())->counts();
$pendingStaff = $sub->pendingStaff();
$recentPayments = $db->query("SELECT pay.*, u.name AS client_name, p.plan_name FROM payment pay JOIN client c ON c.client_id=pay.client_id JOIN users u ON u.user_id=c.user_id JOIN membership m ON m.membership_id=pay.membership_id JOIN membership_plan p ON p.plan_id=m.plan_id ORDER BY pay.payment_id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
$revByDay = $db->query("SELECT DATE(created_at) d, COALESCE(SUM(amount),0) amt FROM payment WHERE payment_status='paid' GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - FitSync</title>
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
            <a href="#staff">Staff</a>
            <a href="#payments">Payments</a>
            <button type="button" id="openNotif" class="side-notif">Notifications</button>
        </nav>
        <div class="side-foot">
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div>
                <h1>Owner Dashboard</h1>
                <p class="muted">Welcome back, <?= htmlspecialchars($_SESSION["name"]) ?></p>
            </div>
            <a href="account.php" class="nav-cta">Account</a>
        </header>

        <?php if ($flash !== ""): ?>
            <div class="flash"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <section class="card" id="overview">
            <div class="card-head"><div><h2>Overview</h2><p class="muted">Key numbers at a glance.</p></div></div>
            <div class="stat-grid">
                <div class="stat-card"><div class="label">Clients</div><div class="value"><?= (int) $clients ?></div></div>
                <div class="stat-card"><div class="label">Staff</div><div class="value"><?= (int) $staff ?></div></div>
                <div class="stat-card"><div class="label">Pending requests</div><div class="value"><?= count($pendingRequests) ?></div></div>
                <div class="stat-card"><div class="label">Memberships</div><div class="value"><?= $oc["memberships"] ?></div><div class="sub">active <?= $oc["active"] ?> · expired <?= $oc["expired"] ?></div></div>
                <div class="stat-card"><div class="label">Revenue collected</div><div class="value">₱<?= number_format((float)$revenue, 2) ?></div></div>
                <div class="stat-card"><div class="label">Pending revenue</div><div class="value">₱<?= number_format((float)$pendingRevenue, 2) ?></div></div>
            </div>
            <h3>Revenue Trend (Last 7 Days)</h3>
            <?php if (empty($revByDay)): ?>
                <p class="empty">No revenue data yet.</p>
            <?php else: ?>
                <?php $maxR = max(array_column($revByDay, "amt")) ?: 1; ?>
                <div class="trend">
                    <?php foreach ($revByDay as $r): ?>
                        <div class="trend-row">
                            <span><?= htmlspecialchars($r["d"]) ?></span>
                            <div class="trend-track"><div class="trend-bar" style="width:<?= round(($r["amt"]/$maxR)*100) ?>%"></div></div>
                            <span>₱<?= number_format($r["amt"], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php require __DIR__ . '/plan_section.php'; ?>
        <?php require __DIR__ . '/manage_users.php'; ?>
        <?php require __DIR__ . '/manage_clients.php'; ?>

        <section class="card" id="staff">
            <div class="card-head"><div><h2>Staff Approvals</h2><p class="muted">Review pending staff registrations.</p></div></div>
            <?php if (empty($pendingStaff)): ?>
                <p class="empty">No pending staff registrations.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <tr><th>Name</th><th>Email</th><th>Action</th></tr>
                        <?php foreach ($pendingStaff as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s["name"]) ?></td>
                                <td><?= htmlspecialchars($s["email"]) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= (int) $s["user_id"] ?>">
                                        <button type="submit" name="approve_staff" class="btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this staff?');">
                                        <input type="hidden" name="user_id" value="<?= (int) $s["user_id"] ?>">
                                        <button type="submit" name="reject_staff" class="btn-danger btn-sm">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card" id="payments">
            <div class="card-head"><div><h2>Recent Payments</h2><p class="muted">Latest payment records.</p></div></div>
            <?php if (empty($recentPayments)): ?>
                <p class="empty">No payments yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <tr><th>Reference</th><th>Client</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th></tr>
                        <?php foreach ($recentPayments as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p["reference_no"] ?: "—") ?></td>
                                <td><?= htmlspecialchars($p["client_name"]) ?></td>
                                <td><?= htmlspecialchars($p["plan_name"]) ?></td>
                                <td>₱<?= number_format($p["amount"], 2) ?></td>
                                <td><?= htmlspecialchars($p["payment_method"]) ?></td>
                                <td><span class="chip chip-<?= $p["payment_status"] === "paid" ? "active" : "pending" ?>"><?= htmlspecialchars(ucfirst($p["payment_status"])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php require __DIR__ . '/staff_popup.php'; ?>
</body>
</html>
