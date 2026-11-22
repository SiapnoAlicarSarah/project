<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Membership_plan;
use App\Models\Subscription;

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "client") {
    header("Location: ../index.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$sub = new Subscription();
$clientId = $sub->getClientId($userId);

if (isset($_POST["pay_plan"])) {
    $_SESSION["flash"] = $sub->subscribe(
        $clientId,
        $userId,
        (int) ($_POST["plan_id"] ?? 0),
        $_POST["payment_method"] ?? ""
    );
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}
if (isset($_POST["mark_read"])) {
    $sub->markAllRead($userId);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}
if (isset($_POST["clear_notifications"])) {
    $sub->clearAll($userId);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

$flash = $_SESSION["flash"] ?? "";
unset($_SESSION["flash"]);

$planModel = new Membership_plan();
$plans = $planModel->getAll();
$notifications = $sub->notifications($userId);
$unread = $sub->unreadCount($userId);
$payments = $sub->clientPayments($clientId);
$latest = $sub->latest($clientId);
$pendingReq = $sub->pending($clientId);

$status = "none";
$daysLeft = 0;
if ($latest) {
    $daysLeft = (int) floor((strtotime($latest["end_date"]) - strtotime(date("Y-m-d"))) / 86400);
    if ($daysLeft < 0) { $status = "expired"; }
    elseif ($daysLeft <= 7) { $status = "expiring"; }
    else { $status = "active"; }
}
if (!$latest && $pendingReq) { $status = "pending"; }

$statusLabels = [
    "none" => "No membership", "pending" => "Waiting for approval",
    "active" => "Active", "expiring" => "Expiring soon", "expired" => "Expired",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - FitSync</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <a href="../index.php" class="logo">Fit<span>Sync</span></a>
        <nav class="side-nav">
            <a href="#" class="active">Dashboard</a>
            <a href="#plans">Membership Plans</a>
            <button type="button" id="openPayments" class="side-notif">Payments</button>
            <button type="button" id="openNotif" class="side-notif">Notifications<?php if ($unread > 0): ?><span class="badge"><?= $unread ?></span><?php endif; ?></button>
        </nav>
        <div class="side-foot">
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div>
                <h1>Welcome back, <span class="accent"><?= htmlspecialchars($_SESSION["name"]) ?></span></h1>
                <p class="muted">Check your membership and manage your plans.</p>
            </div>
            <a href="account.php" class="nav-cta">Account</a>
        </header>

        <?php if ($flash !== ""): ?>
            <div class="flash"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <section class="card">
            <div class="card-head"><div><h2>My Membership</h2><p class="muted">Your current membership status.</p></div></div>
            <div class="membership-status">
                <span class="chip chip-<?= $status ?>"><?= $statusLabels[$status] ?></span>
                <?php if ($pendingReq): ?>
                    <p><strong>Request:</strong> <?= htmlspecialchars($pendingReq["plan_name"]) ?> is waiting for staff approval.</p>
                <?php endif; ?>
                <?php if ($latest): ?>
                    <p><strong>Plan:</strong> <?= htmlspecialchars($latest["plan_name"]) ?></p>
                    <p><strong>Started:</strong> <?= date("M d, Y", strtotime($latest["start_date"])) ?></p>
                    <p><strong>Expires:</strong> <?= date("M d, Y", strtotime($latest["end_date"])) ?></p>
                    <p><?= $status === "expired" ? "Your membership ended " . abs($daysLeft) . " day(s) ago." : "<strong>Days left:</strong> " . $daysLeft ?></p>
                    <?php if ($status === "expiring"): ?><p class="muted">Renew below so you don't lose access.</p><?php endif; ?>
                <?php elseif (!$pendingReq): ?>
                    <p class="muted">You don't have a membership yet. Choose a plan below.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card" id="plans">
            <div class="card-head"><div><h2>Available Membership Plans</h2><p class="muted">Choose a plan and pay to subscribe.</p></div></div>
            <?php if (empty($plans)): ?>
                <p class="empty">No membership plans available yet.</p>
            <?php else: ?>
                <div class="plan-grid">
                    <?php foreach ($plans as $plan): ?>
                        <?php $img = basename(($plan["image"] ?? "") ?: "gym.png"); if (!is_file(__DIR__ . "/../images/" . $img)) { $img = "gym.png"; } ?>
                        <article class="plan-card">
                            <img src="../images/<?= htmlspecialchars($img) ?>" alt="" onerror="this.style.display='none'">
                            <h3><?= htmlspecialchars($plan["plan_name"]) ?></h3>
                            <p class="plan-price">₱<?= number_format($plan["plan_price"], 2) ?></p>
                            <p class="muted"><?= (int) $plan["duration_days"] ?> days</p>
                            <p class="muted"><?= htmlspecialchars($plan["description"] ?? "") ?></p>
                            <button type="button" class="pay-btn" <?= $pendingReq ? "disabled" : "" ?>
                                data-id="<?= (int) $plan["plan_id"] ?>"
                                data-name="<?= htmlspecialchars($plan["plan_name"]) ?>"
                                data-price="<?= number_format($plan["plan_price"], 2) ?>">Pay</button>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <dialog id="payDialog">
        <h2>Pay for <span id="payPlanName"></span></h2>
        <p>Amount: <strong>₱<span id="payPrice"></span></strong></p>
        <form method="POST">
            <input type="hidden" name="plan_id" id="payPlanId">
            <label for="payment_method">Payment method:</label>
            <select name="payment_method" id="payment_method" required>
                <option value="">Choose...</option>
                <option value="gcash">GCash</option>
                <option value="bank_transfer">Bank transfer</option>
                <option value="cash">Cash</option>
            </select><br><br>
            <input type="submit" name="pay_plan" value="Confirm Payment">
            <button type="button" id="closePay" class="btn-cancel">Cancel</button>
        </form>
    </dialog>

    <dialog id="notifDialog">
        <h2>Notifications</h2>
        <?php if ($status === "expiring"): ?><p class="notif-item unread">Your membership expires in <?= $daysLeft ?> day(s).</p><?php endif; ?>
        <?php if ($status === "expired"): ?><p class="notif-item unread">Your membership has expired. Pay to renew.</p><?php endif; ?>
        <?php if (empty($notifications) && $status !== "expiring" && $status !== "expired"): ?>
            <p class="empty">No notifications yet.</p>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notif-item <?= $n["is_read"] ? "" : "unread" ?>">
                    <?= htmlspecialchars($n["message"]) ?>
                    <small><?= date("M d, Y g:i A", strtotime($n["created_at"])) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <br>
        <form method="POST" style="display:inline;"><button type="submit" name="mark_read">Mark all as read</button></form>
        <form method="POST" style="display:inline;"><button type="submit" name="clear_notifications" class="btn-danger" onclick="return confirm('Clear all notifications?');">Clear all</button></form>
        <button type="button" id="closeNotif" class="btn-cancel">Close</button>
    </dialog>

    <dialog id="paymentsDialog">
        <h2>Payment History</h2>
        <?php if (empty($payments)): ?>
            <p class="empty">No payments yet.</p>
        <?php else: ?>
            <div class="table-wrap"><table>
                <tr><th>Reference</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th></tr>
                <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td><?= htmlspecialchars($pay["reference_no"] ?: "—") ?></td>
                        <td><?= htmlspecialchars($pay["plan_name"]) ?></td>
                        <td>₱<?= number_format($pay["amount"], 2) ?></td>
                        <td><?= htmlspecialchars($pay["payment_method"]) ?></td>
                        <td><?= htmlspecialchars($pay["payment_status"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table></div>
        <?php endif; ?>
        <br><button type="button" id="closePayments" class="btn-cancel">Close</button>
    </dialog>

    <script>
        var payDialog = document.getElementById("payDialog");
        document.querySelectorAll(".pay-btn").forEach(function (btn) {
            btn.onclick = function () {
                document.getElementById("payPlanId").value = btn.dataset.id;
                document.getElementById("payPlanName").textContent = btn.dataset.name;
                document.getElementById("payPrice").textContent = btn.dataset.price;
                payDialog.showModal();
            };
        });
        document.getElementById("closePay").onclick = function () { payDialog.close(); };
        var notifDialog = document.getElementById("notifDialog");
        document.getElementById("openNotif").onclick = function () { notifDialog.showModal(); };
        document.getElementById("closeNotif").onclick = function () { notifDialog.close(); };
        var paymentsDialog = document.getElementById("paymentsDialog");
        document.getElementById("openPayments").onclick = function () { paymentsDialog.showModal(); };
        document.getElementById("closePayments").onclick = function () { paymentsDialog.close(); };
    </script>
</body>
</html>
