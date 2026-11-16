<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Models\Membership_plan;

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ["staff", "owner"], true)) {
    header("Location: ../index.php");
    exit;
}

$planModel = new Membership_plan();
$message = $_SESSION["plan_message"] ?? "";
unset($_SESSION["plan_message"]);

/* ---------- Update plan ---------- */
if (isset($_POST["update_plan"])) {
    $plan_id = (int) ($_POST["plan_id"] ?? 0);
    $plan_name = trim($_POST["plan_name"] ?? "");
    $price = (float) ($_POST["price"] ?? 0);
    $duration_days = (int) ($_POST["duration_days"] ?? 0);
    $description = trim($_POST["description"] ?? "");

    if ($plan_id <= 0 || $plan_name === "" || $price <= 0 || $duration_days < 1) {
        $_SESSION["plan_message"] = "Please provide a valid plan, name, price, and duration.";
    } else {
        $_SESSION["plan_message"] = $planModel->update($plan_name, $price, $duration_days, $description, $plan_id);
    }
    header("Location: edit_plan.php?edit=" . $plan_id);
    exit;
}

$editId = isset($_GET["edit"]) ? (int) $_GET["edit"] : 0;
$editPlan = $editId > 0 ? $planModel->ViewbyId($editId) : null;
$plans = $planModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Plan - FitSync</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .edit-wrap { max-width: 720px; margin: 0 auto; }
        .plan-list { margin-top: 24px; }
        .plan-list a { color: #2563eb; font-weight: 600; }
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
            <a href="account.php" class="nav-cta">Account</a>
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </nav>

    <?php if ($message !== ""): ?>
        <div class="flash"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="edit-wrap">
        <h1>Edit Membership Plan</h1>

        <?php if ($editPlan): ?>
            <div class="card">
                <h2>Editing: <?= htmlspecialchars($editPlan["plan_name"]) ?></h2>
                <form method="POST">
                    <input type="hidden" name="plan_id" value="<?= (int) $editPlan["plan_id"] ?>">
                    <label>Plan Name:</label>
                    <input type="text" name="plan_name" value="<?= htmlspecialchars($editPlan["plan_name"]) ?>" required>
                    <label>Price (₱):</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($editPlan["plan_price"]) ?>" required>
                    <label>Duration (days):</label>
                    <input type="number" name="duration_days" min="1" value="<?= (int) $editPlan["duration_days"] ?>" required>
                    <label>Description:</label>
                    <textarea name="description"><?= htmlspecialchars($editPlan["description"] ?? "") ?></textarea>
                    <button type="submit" name="update_plan">Update Plan</button>
                    <a href="edit_plan.php" class="btn-cancel" style="text-align:center; text-decoration:none; display:inline-block;">Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <p>Select a plan to edit.</p>
        <?php endif; ?>

        <div class="card plan-list">
            <h2>Available Plans</h2>
            <?php if (empty($plans)): ?>
                <p class="empty">No plans yet.</p>
            <?php else: ?>
                <table>
                    <tr><th>Plan</th><th>Price</th><th>Duration</th><th>Action</th></tr>
                    <?php foreach ($plans as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p["plan_name"]) ?></td>
                            <td>₱<?= number_format($p["plan_price"], 2) ?></td>
                            <td><?= (int) $p["duration_days"] ?> days</td>
                            <td><a href="edit_plan.php?edit=<?= (int) $p["plan_id"] ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>