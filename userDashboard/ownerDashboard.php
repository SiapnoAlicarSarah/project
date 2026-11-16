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

$db = (new Database())->connection();
$clients = $db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$staff   = $db->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
$revenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status='paid'")->fetchColumn();
$pendingRevenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status='pending'")->fetchColumn();
$oc = (new Subscription())->counts();

$recentMemberships = array_slice($allMemberships, 0, 8);
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

    <h1>Owner Dashboard</h1>
    <div class="stat-grid">
        <div class="stat-card">
            <div class="label">Registered Clients</div>
            <div class="value"><?= (int) $clients ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Staff Accounts</div>
            <div class="value"><?= (int) $staff ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Pending Requests</div>
            <div class="value"><?= count($pendingRequests) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Memberships</div>
            <div class="value"><?= $oc["memberships"] ?></div>
            <div class="sub">active <?= $oc["active"] ?> · expired <?= $oc["expired"] ?> · pending <?= $oc["pending"] ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Revenue Collected</div>
            <div class="value">₱<?= number_format((float)$revenue, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Pending Revenue</div>
            <div class="value">₱<?= number_format((float)$pendingRevenue, 2) ?></div>
        </div>
    </div>

    <!-- Quick create plan form -->
    <div class="card">
        <h2>Quick Create Membership Plan</h2>
        <form method="POST" class="form-row" action="ownerDashboard.php">
            <input type="text" name="plan_name" placeholder="Plan name" required>
            <input type="number" name="price" step="0.01" placeholder="Price" required>
            <input type="number" name="duration_days" placeholder="Days" min="1" required>
            <input type="text" name="description" placeholder="Description">
            <button type="submit" name="add_plan">Create Plan</button>
        </form>
        <small style="color:var(--muted);">Submits to the plan manager (use Manage Plans for full controls).</small>
    </div>

    <!-- Revenue chart -->
    <div class="card">
        <h2>Revenue Trend (Last 7 Days)</h2>
        <?php if (empty($revByDay)): ?>
            <p class="empty">No revenue data yet.</p>
        <?php else: ?>
            <?php $maxR = max(array_column($revByDay, "amt")) ?: 1; ?>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($revByDay as $r): ?>
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:3px;">
                            <span><?= htmlspecialchars($r["d"]) ?></span>
                            <span>₱<?= number_format($r["amt"], 2) ?></span>
                        </div>
                        <div style="background:#e2e8f0; border-radius:6px; overflow:hidden; height:10px;">
                            <div style="width:<?= round(($r["amt"]/$maxR)*100) ?>%; background:linear-gradient(90deg,#2563eb,#60a5fa); height:100%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent memberships -->
    <div class="card">
        <h2>Recent Memberships</h2>
        <?php if (empty($recentMemberships)): ?>
            <p class="empty">No memberships yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr><th>Client</th><th>Plan</th><th>Price</th><th>Period</th><th>Status</th></tr>
                    <?php foreach ($recentMemberships as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m["client_name"]) ?></td>
                            <td><?= htmlspecialchars($m["plan_name"]) ?></td>
                            <td>₱<?= number_format($m["plan_price"], 2) ?></td>
                            <td><?= date("M d", strtotime($m["start_date"])) ?> - <?= date("M d", strtotime($m["end_date"])) ?></td>
                            <td><span class="chip chip-<?= htmlspecialchars($m["status"]) ?>"><?= htmlspecialchars(ucfirst($m["status"])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent payments -->
    <div class="card">
        <h2>Recent Payments</h2>
        <?php if (empty($recentPayments)): ?>
            <p class="empty">No payments yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr><th>Client</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th></tr>
                    <?php foreach ($recentPayments as $p): ?>
                        <tr>
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
    </div>

    <?php require __DIR__ . '/plan_section.php'; ?>
    <?php require __DIR__ . '/staff_popup.php'; ?>
    <?php require __DIR__ . '/manage_users.php'; ?>
    <?php require __DIR__ . '/manage_clients.php'; ?>

</body>
</html>