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
$dashboard = $_SESSION["role"] === "owner" ? "ownerDashboard.php" : "staffDashboard.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Plan - FitSync</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <a href="../index.php" class="logo">Fit<span>Sync</span></a>
        <nav class="side-nav">
            <a href="<?= $dashboard ?>">Dashboard</a>
            <a href="<?= $dashboard ?>#plans">Membership Plans</a>
            <a href="#" class="active">Edit Plan</a>
        </nav>
        <div class="side-foot">
            <a href="account.php" class="nav-cta">Account</a>
            <a href="../logout.php" class="nav-cta">Logout</a>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div>
                <h1>Edit Membership Plan</h1>
                <p class="muted">Update plan details and pricing.</p>
            </div>
        </header>

        <?php if ($message !== ""): ?><div class="flash"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="account-grid">
            <?php if ($editPlan): ?>
                <section class="card">
                    <div class="card-head"><div><h2>Editing: <?= htmlspecialchars($editPlan["plan_name"]) ?></h2><p class="muted">Save changes to update this plan.</p></div></div>
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
                        <a href="edit_plan.php" class="btn-cancel">Cancel</a>
                    </form>
                </section>
            <?php endif; ?>

            <section class="card">
                <div class="card-head"><div><h2>Available Plans</h2><p class="muted">Select a plan to edit.</p></div></div>
                <?php if (empty($plans)): ?>
                    <p class="empty">No plans yet.</p>
                <?php else: ?>
                    <div class="table-wrap"><table>
                        <tr><th>Plan</th><th>Price</th><th>Duration</th><th>Action</th></tr>
                        <?php foreach ($plans as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p["plan_name"]) ?></td>
                                <td>₱<?= number_format($p["plan_price"], 2) ?></td>
                                <td><?= (int) $p["duration_days"] ?> days</td>
                                <td><a href="edit_plan.php?edit=<?= (int) $p["plan_id"] ?>" class="btn-sm btn-secondary">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table></div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
