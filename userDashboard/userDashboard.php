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

/* ---------- Pay for a plan (sends a request to staff) ---------- */
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

/* ---------- Mark notifications as read ---------- */
if (isset($_POST["mark_read"])) {
    $sub->markAllRead($userId);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

/* ---------- Clear all notifications ---------- */
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

/* ---------- Membership status ---------- */
$latest = $sub->latest($clientId);
$pendingReq = $sub->pending($clientId);

$status = "none";
$daysLeft = 0;
if ($latest) {
    $daysLeft = (int) floor((strtotime($latest["end_date"]) - strtotime(date("Y-m-d"))) / 86400);
    if ($daysLeft < 0) {
        $status = "expired";
    } elseif ($daysLeft <= 7) {
        $status = "expiring";
    } else {
        $status = "active";
    }
}
if (!$latest && $pendingReq) {
    $status = "pending";
}

$statusLabels = [
    "none"     => "No membership",
    "pending"  => "Waiting for approval",
    "active"   => "Active",
    "expiring" => "Expiring soon",
    "expired"  => "Expired",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - FitSync</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* ---------- Hero ---------- */
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
            padding: 40px 48px;
            border-radius: 20px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #60a5fa 100%);
            color: #fff;
            flex-wrap: wrap;
        }
        .hero-text { flex: 1; min-width: 260px; }
        .hero-text h1 { font-size: 44px; line-height: 1.1; margin: 0 0 12px; }
        .hero-text h1 span { color: #93c5fd; }
        .hero-text p { margin: 0; font-size: 16px; max-width: 420px; }

        .status-card {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 20px 24px;
            min-width: 280px;
        }
        .status-card h3 { margin: 0 0 10px; font-size: 16px; color: #bfdbfe; }
        .status-chip {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .chip-active   { background: #22c55e; color: #fff; }
        .chip-expiring { background: #f59e0b; color: #fff; }
        .chip-expired  { background: #ef4444; color: #fff; }
        .chip-none     { background: #e2e8f0; color: #334155; }
        .status-card p { margin: 4px 0; font-size: 14px; }

        /* ---------- Plans ---------- */
        .programs { margin-top: 32px; }
        .programs h2 { font-size: 24px; margin-bottom: 14px; }
        .programs h2 span { color: #2563eb; }

        .program-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .program-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            border: 2px solid transparent;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            transition: transform 0.15s, border-color 0.2s;
        }
        .program-card:hover { transform: translateY(-4px); border-color: #2563eb; }
        .program-img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 12px;
        }
        .program-card h3 { margin: 0 0 6px; color: #1d4ed8; }
        .program-price { font-size: 22px; font-weight: 700; color: #2563eb; margin: 0; }
        .program-days { margin: 2px 0 8px; color: #64748b; font-size: 13px; }
        .program-desc { font-size: 13px; color: #475569; margin: 0 0 12px; }
        button:disabled { background: #94a3b8; cursor: not-allowed; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">Fit<span>Sync</span></div>

        <div class="nav-right">
            <button type="button" class="nav-btn" id="openNotif">
                Notifications<?php if ($unread > 0): ?><span class="badge"><?= $unread ?></span><?php endif; ?>
            </button>
            <span class="nav-user">Hi, <span><?= htmlspecialchars($_SESSION["name"]) ?></span></span>
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </nav>

    <?php if ($flash !== ""): ?>
        <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <section class="hero">
        <div class="hero-text">
            <h1>Welcome back, <span><?= htmlspecialchars($_SESSION["name"]) ?></span></h1>
            <p>Check your membership, get notified before it expires, and renew with a few clicks.</p>
        </div>

        <div class="status-card">
            <h3>My Membership</h3>
            <span class="status-chip chip-<?= $status ?>"><?= $statusLabels[$status] ?></span>

            <?php if ($pendingReq): ?>
                <p><strong>Request:</strong> <?= htmlspecialchars($pendingReq["plan_name"]) ?> is waiting for staff approval.</p>
            <?php endif; ?>

            <?php if ($latest): ?>
                <p><strong>Plan:</strong> <?= htmlspecialchars($latest["plan_name"]) ?></p>
                <p><strong>Started:</strong> <?= date("M d, Y", strtotime($latest["start_date"])) ?></p>
                <p><strong>Expires:</strong> <?= date("M d, Y", strtotime($latest["end_date"])) ?></p>
                <?php if ($status === "expired"): ?>
                    <p>Your membership ended <?= abs($daysLeft) ?> day(s) ago. Pay below to renew.</p>
                <?php else: ?>
                    <p><strong>Days left:</strong> <?= $daysLeft ?></p>
                <?php endif; ?>
                <?php if ($status === "expiring"): ?>
                    <p>Your membership is ending soon. Renew below so you don't lose access.</p>
                <?php endif; ?>
            <?php elseif (!$pendingReq): ?>
                <p>You don't have a membership yet. Choose a plan below and pay to get started.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="programs">
        <h2>Available <span>Memberships</span></h2>

        <?php if (empty($plans)): ?>
            <p>No membership plans available yet.</p>
        <?php else: ?>
            <div class="program-grid">
                <?php foreach ($plans as $plan): ?>
                    <?php
                        $img = basename(($plan["image"] ?? "") ?: "plan.png");
                        if (!is_file(__DIR__ . "/../images/" . $img)) {
                            $img = "plan.png";
                        }
                    ?>
                    <div class="program-card">
                        <img src="../images/<?= htmlspecialchars($img) ?>" alt="" class="program-img"
                             onerror="this.style.display='none'">
                        <h3><?= htmlspecialchars($plan["plan_name"]) ?></h3>
                        <p class="program-price">₱<?= number_format($plan["plan_price"], 2) ?></p>
                        <p class="program-days"><?= (int) $plan["duration_days"] ?> days</p>
                        <p class="program-desc"><?= htmlspecialchars($plan["description"] ?? "") ?></p>
                        <button type="button" class="pay-btn" <?= $pendingReq ? "disabled" : "" ?>
                                data-id="<?= (int) $plan["plan_id"] ?>"
                                data-name="<?= htmlspecialchars($plan["plan_name"]) ?>"
                                data-price="<?= number_format($plan["plan_price"], 2) ?>">Pay</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Pay popup -->
    <dialog id="payDialog">
        <h2>Pay for <span id="payPlanName"></span></h2>
        <p>Amount: <strong>₱<span id="payPrice"></span></strong></p>
        <form method="POST">
            <input type="hidden" name="plan_id" id="payPlanId">

            <label for="payment_method">Payment method:</label>
            <select name="payment_method" id="payment_method" required>
                <option value="">Choose...</option>
                <option value="gcash">GCash</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank transfer</option>
                <option value="cash">Cash</option>
            </select> <br><br>

            <input type="submit" name="pay_plan" value="Confirm Payment">
            <button type="button" id="closePay" class="btn-cancel">Cancel</button>
        </form>
    </dialog>

    <!-- Notifications popup -->
    <dialog id="notifDialog">
        <h2>Notifications</h2>

        <?php if ($status === "expiring"): ?>
            <div class="notif-item unread">
                Your membership expires in <?= $daysLeft ?> day(s). Renew soon!
            </div>
        <?php elseif ($status === "expired"): ?>
            <div class="notif-item unread">Your membership has expired. Pay to renew.</div>
        <?php endif; ?>

        <?php if (empty($notifications) && $status !== "expiring" && $status !== "expired"): ?>
            <p>No notifications yet.</p>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notif-item <?= $n["is_read"] ? "" : "unread" ?>">
                    <?= htmlspecialchars($n["message"]) ?>
                    <small><?= date("M d, Y g:i A", strtotime($n["created_at"])) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <br>
        <form method="POST" style="display:inline;">
            <button type="submit" name="mark_read">Mark all as read</button>
        </form>
        <form method="POST" style="display:inline;">
            <button type="submit" name="clear_notifications" class="btn-danger"
                    onclick="return confirm('Clear all notifications?');">Clear all</button>
        </form>
        <button type="button" id="closeNotif" class="btn-cancel">Close</button>
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
    </script>

</body>
</html>