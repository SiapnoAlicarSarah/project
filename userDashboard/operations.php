<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Models\Subscription;

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ["staff", "owner"], true)) {
    header("Location: ../index.php");
    exit;
}

$db = (new Database())->connection();
$sub = new Subscription();
$staffUserId = (int) $_SESSION["user_id"];
$message = "";

/* ---------- Attendance check-in ---------- */
if (isset($_POST["checkin"])) {
    $clientId = (int) ($_POST["client_id"] ?? 0);
    if ($clientId > 0) {
        $db->prepare("INSERT INTO attendance (client_id) VALUES (?)")->execute([$clientId]);
        $message = "Client checked in.";
    }
}
if (isset($_POST["checkout"])) {
    $aid = (int) ($_POST["attendance_id"] ?? 0);
    $db->prepare("UPDATE attendance SET check_out = NOW() WHERE attendance_id = ?")->execute([$aid]);
    $message = "Checked out.";
}

/* ---------- Class create ---------- */
if (isset($_POST["add_class"])) {
    $name = trim($_POST["class_name"] ?? "");
    $sched = trim($_POST["schedule"] ?? "");
    $cap = (int) ($_POST["capacity"] ?? 20);
    $inst = trim($_POST["instructor"] ?? "");
    if ($name !== "") {
        $db->prepare("INSERT INTO gym_class (class_name, schedule, capacity, instructor) VALUES (?,?,?,?)")
            ->execute([$name, $sched, $cap, $inst]);
        $message = "Class created.";
    }
}
/* ---------- Enroll client ---------- */
if (isset($_POST["enroll"])) {
    $cid = (int) ($_POST["class_id"] ?? 0);
    $clid = (int) ($_POST["client_id"] ?? 0);
    if ($cid > 0 && $clid > 0) {
        $db->prepare("INSERT INTO class_enrollment (class_id, client_id) VALUES (?,?)")->execute([$cid, $clid]);
        $message = "Client enrolled.";
    }
}

/* ---------- Product add ---------- */
if (isset($_POST["add_product"])) {
    $name = trim($_POST["product_name"] ?? "");
    $price = (float) ($_POST["product_price"] ?? 0);
    $stock = (int) ($_POST["product_stock"] ?? 0);
    if ($name !== "" && $price > 0) {
        $db->prepare("INSERT INTO product (name, price, stock) VALUES (?,?,?)")->execute([$name, $price, $stock]);
        $message = "Product added.";
    }
}
/* ---------- Record sale ---------- */
if (isset($_POST["record_sale"])) {
    $pid = (int) ($_POST["product_id"] ?? 0);
    $qty = (int) ($_POST["quantity"] ?? 0);
    $stmt = $db->prepare("SELECT price, stock FROM product WHERE product_id = ?");
    $stmt->execute([$pid]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($p && $qty > 0 && $qty <= $p["stock"]) {
        $total = $p["price"] * $qty;
        $db->prepare("INSERT INTO sale (product_id, quantity, total, sold_by) VALUES (?,?,?,?)")
            ->execute([$pid, $qty, $total, $staffUserId]);
        $db->prepare("UPDATE product SET stock = stock - ? WHERE product_id = ?")->execute([$qty, $pid]);
        $message = "Sale recorded: ₱" . number_format($total, 2);
    } else {
        $message = "Invalid sale (check stock).";
    }
}

/* ---------- Maintenance add ---------- */
if (isset($_POST["add_maintenance"])) {
    $equip = trim($_POST["equipment"] ?? "");
    $date = $_POST["service_date"] ?? date("Y-m-d");
    $notes = trim($_POST["notes"] ?? "");
    $status = $_POST["mstatus"] ?? "scheduled";
    if ($equip !== "") {
        $db->prepare("INSERT INTO maintenance (equipment, service_date, notes, status) VALUES (?,?,?,?)")
            ->execute([$equip, $date, $notes, $status]);
        $message = "Maintenance logged.";
    }
}

/* ---------- Reminders ---------- */
if (isset($_POST["send_reminders"])) {
    $rows = $db->query("SELECT m.membership_id, c.user_id, m.end_date FROM membership m JOIN client c ON c.client_id=m.client_id WHERE m.status='active' AND m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    foreach ($rows as $r) {
        $msg = "Reminder: your membership expires on " . date("M d, Y", strtotime($r["end_date"])) . ". Renew soon!";
        $db->prepare("INSERT INTO notification (user_id, membership_id, message) VALUES (?,?,?)")
            ->execute([$r["user_id"], $r["membership_id"], $msg]);
        $count++;
    }
    $message = "Sent $count reminder(s).";
}

$clients = $sub->allClients();
$attendanceToday = $db->query("SELECT a.*, c.client_id, u.name FROM attendance a JOIN client c ON c.client_id=a.client_id JOIN users u ON u.user_id=c.user_id WHERE DATE(a.check_in)=CURDATE() ORDER BY a.check_in DESC")->fetchAll(PDO::FETCH_ASSOC);
$classes = $db->query("SELECT * FROM gym_class ORDER BY class_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$products = $db->query("SELECT * FROM product ORDER BY product_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$maint = $db->query("SELECT * FROM maintenance ORDER BY service_date DESC")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Reports ---------- */
$daily = $db->query("SELECT DATE(created_at) d, COALESCE(SUM(total),0) amt FROM sale GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$byMethod = $db->query("SELECT payment_method, COALESCE(SUM(amount),0) amt, COUNT(*) cnt FROM payment GROUP BY payment_method")->fetchAll(PDO::FETCH_ASSOC);
$revByDay = $db->query("SELECT DATE(created_at) d, COALESCE(SUM(amount),0) amt FROM payment WHERE payment_status='paid' GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operations - FitSync</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .op-section { background: #fff; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(37,99,235,0.12); }
        .op-section h2 { margin-top: 0; }
        .op-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; }
        .op-section form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .op-section input, .op-section select { padding: 6px 8px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../index.php" class="logo">Fit<span>Sync</span></a>
        <div class="nav-links">
            <a href="../index.php" class="nav-cta">Home</a>
            <a href="<?= $_SESSION["role"] === "owner" ? "ownerDashboard.php" : "staffDashboard.php" ?>" class="nav-cta">Dashboard</a>
        </div>
        <div class="nav-right">
            <span class="nav-user">Hi, <span><?= htmlspecialchars($_SESSION["name"]) ?></span></span>
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </nav>

    <?php if ($message !== ""): ?>
        <div class="flash"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h1>Operations</h1>

    <!-- Attendance -->
    <div class="op-section">
        <h2>Attendance (Today)</h2>
        <form method="POST">
            <select name="client_id" required>
                <option value="">Select client...</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= (int) $c["client_id"] ?>"><?= htmlspecialchars($c["name"]) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="checkin">Check In</button>
        </form>
        <?php if (empty($attendanceToday)): ?>
            <p>No check-ins today.</p>
        <?php else: ?>
            <table>
                <tr><th>Client</th><th>Check In</th><th>Check Out</th><th>Action</th></tr>
                <?php foreach ($attendanceToday as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a["name"]) ?></td>
                        <td><?= date("g:i A", strtotime($a["check_in"])) ?></td>
                        <td><?= $a["check_out"] ? date("g:i A", strtotime($a["check_out"])) : "-" ?></td>
                        <td>
                            <?php if (!$a["check_out"]): ?>
                                <form method="POST">
                                    <input type="hidden" name="attendance_id" value="<?= (int) $a["attendance_id"] ?>">
                                    <button type="submit" name="checkout">Check Out</button>
                                </form>
                            <?php else: ?>Done<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <!-- Classes -->
    <div class="op-section">
        <h2>Class Scheduling</h2>
        <form method="POST">
            <input type="text" name="class_name" placeholder="Class name" required>
            <input type="text" name="schedule" placeholder="Schedule (e.g. Mon 6pm)">
            <input type="number" name="capacity" placeholder="Capacity" min="1" value="20">
            <input type="text" name="instructor" placeholder="Instructor">
            <button type="submit" name="add_class">Add Class</button>
        </form>
        <?php if (empty($classes)): ?>
            <p>No classes yet.</p>
        <?php else: ?>
            <table>
                <tr><th>Class</th><th>Schedule</th><th>Capacity</th><th>Instructor</th><th>Enroll</th></tr>
                <?php foreach ($classes as $cl): ?>
                    <tr>
                        <td><?= htmlspecialchars($cl["class_name"]) ?></td>
                        <td><?= htmlspecialchars($cl["schedule"]) ?></td>
                        <td><?= (int) $cl["capacity"] ?></td>
                        <td><?= htmlspecialchars($cl["instructor"]) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="class_id" value="<?= (int) $cl["class_id"] ?>">
                                <select name="client_id" required>
                                    <option value="">Client...</option>
                                    <?php foreach ($clients as $c): ?>
                                        <option value="<?= (int) $c["client_id"] ?>"><?= htmlspecialchars($c["name"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="enroll">Enroll</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <!-- Inventory / POS -->
    <div class="op-section">
        <h2>Inventory & Retail (POS)</h2>
        <form method="POST">
            <input type="text" name="product_name" placeholder="Product name" required>
            <input type="number" name="product_price" step="0.01" placeholder="Price" required>
            <input type="number" name="product_stock" placeholder="Stock" min="0" value="0">
            <button type="submit" name="add_product">Add Product</button>
        </form>
        <?php if (!empty($products)): ?>
            <table>
                <tr><th>Product</th><th>Price</th><th>Stock</th><th>Sell</th></tr>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p["name"]) ?></td>
                        <td>₱<?= number_format($p["price"], 2) ?></td>
                        <td><?= (int) $p["stock"] ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?= (int) $p["product_id"] ?>">
                                <input type="number" name="quantity" min="1" value="1" style="width:60px;">
                                <button type="submit" name="record_sale">Sell</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No products yet.</p>
        <?php endif; ?>
    </div>

    <!-- Maintenance -->
    <div class="op-section">
        <h2>Facility Maintenance</h2>
        <form method="POST">
            <input type="text" name="equipment" placeholder="Equipment" required>
            <input type="date" name="service_date" value="<?= date("Y-m-d") ?>">
            <input type="text" name="notes" placeholder="Notes">
            <select name="mstatus">
                <option value="scheduled">Scheduled</option>
                <option value="done">Done</option>
            </select>
            <button type="submit" name="add_maintenance">Log</button>
        </form>
        <?php if (empty($maint)): ?>
            <p>No maintenance records.</p>
        <?php else: ?>
            <table>
                <tr><th>Equipment</th><th>Date</th><th>Status</th><th>Notes</th></tr>
                <?php foreach ($maint as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m["equipment"]) ?></td>
                        <td><?= htmlspecialchars($m["service_date"]) ?></td>
                        <td><?= htmlspecialchars($m["status"]) ?></td>
                        <td><?= htmlspecialchars($m["notes"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <!-- Reminders -->
    <div class="op-section">
        <h2>Automated Reminders</h2>
        <p>Send in-app notifications to clients whose membership expires within 7 days.</p>
        <form method="POST">
            <button type="submit" name="send_reminders">Send Reminders</button>
        </form>
    </div>

    <!-- Reports -->
    <div class="op-section">
        <h2>Reports</h2>
        <h3>Daily Revenue (Payments)</h3>
        <?php if (empty($revByDay)): ?>
            <p>No payment data.</p>
        <?php else: ?>
            <?php $maxRev = max(array_column($revByDay, "amt")) ?: 1; ?>
            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
                <?php foreach ($revByDay as $r): ?>
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:3px;">
                            <span><?= htmlspecialchars($r["d"]) ?></span>
                            <span>₱<?= number_format($r["amt"], 2) ?></span>
                        </div>
                        <div style="background:#e2e8f0; border-radius:6px; overflow:hidden; height:10px;">
                            <div style="width:<?= round(($r["amt"]/$maxRev)*100) ?>%; background:linear-gradient(90deg,#2563eb,#60a5fa); height:100%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <table>
                <tr><th>Date</th><th>Amount</th></tr>
                <?php foreach ($revByDay as $r): ?>
                    <tr><td><?= htmlspecialchars($r["d"]) ?></td><td>₱<?= number_format($r["amt"], 2) ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h3>Retail Sales by Day</h3>
        <?php if (empty($daily)): ?>
            <p>No sales data.</p>
        <?php else: ?>
            <table>
                <tr><th>Date</th><th>Amount</th></tr>
                <?php foreach ($daily as $r): ?>
                    <tr><td><?= htmlspecialchars($r["d"]) ?></td><td>₱<?= number_format($r["amt"], 2) ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h3>Payments by Method</h3>
        <?php if (empty($byMethod)): ?>
            <p>No data.</p>
        <?php else: ?>
            <?php $maxM = max(array_column($byMethod, "amt")) ?: 1; ?>
            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
                <?php foreach ($byMethod as $r): ?>
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:3px;">
                            <span><?= htmlspecialchars($r["payment_method"]) ?> (<?= (int)$r["cnt"] ?>)</span>
                            <span>₱<?= number_format($r["amt"], 2) ?></span>
                        </div>
                        <div style="background:#e2e8f0; border-radius:6px; overflow:hidden; height:10px;">
                            <div style="width:<?= round(($r["amt"]/$maxM)*100) ?>%; background:linear-gradient(90deg,#8b5cf6,#a78bfa); height:100%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <table>
                <tr><th>Method</th><th>Count</th><th>Amount</th></tr>
                <?php foreach ($byMethod as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r["payment_method"]) ?></td>
                        <td><?= (int) $r["cnt"] ?></td>
                        <td>₱<?= number_format($r["amt"], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>