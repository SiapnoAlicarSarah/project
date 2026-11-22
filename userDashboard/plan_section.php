<?php
use App\Models\Membership_plan;

$planModel = new Membership_plan();
$planMessage = $_SESSION["plan_message"] ?? "";
unset($_SESSION["plan_message"]);

if (isset($_POST["add_plan"])) {
    $plan_name     = trim($_POST["plan_name"] ?? "");
    $price         = $_POST["price"] ?? "";
    $duration_days = $_POST["duration_days"] ?? "";
    $description   = trim($_POST["description"] ?? "");

    if ($plan_name === "" || !is_numeric($price) || $price <= 0 || !ctype_digit($duration_days) || $duration_days < 1) {
        $_SESSION["plan_message"] = "Please enter a plan name, a price above 0, and a duration of at least 1 day.";
    } else {
        $_SESSION["plan_message"] = $planModel->insert($plan_name, $price, (int) $duration_days, $description);
    }
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

if (isset($_POST["delete_plan"])) {
    $plan_id = $_POST["plan_id"] ?? "";
    if (!ctype_digit($plan_id)) {
        $_SESSION["plan_message"] = "Invalid plan.";
    } else {
        try {
            $_SESSION["plan_message"] = $planModel->delete((int) $plan_id);
        } catch (\PDOException $e) {
            $_SESSION["plan_message"] = "This plan can't be deleted because members are subscribed to it.";
        }
    }
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

$plans = $planModel->getAll();
?>
<section class="card" id="plans">
    <div class="card-head">
        <div>
            <h2>Membership Plans</h2>
            <p class="muted">Create and maintain the plans clients can subscribe to.</p>
        </div>
        <a href="edit_plan.php" class="btn-secondary btn-sm">Edit Plans</a>
    </div>

    <?php if ($planMessage !== ""): ?>
        <p class="flash-inline"><?= htmlspecialchars($planMessage) ?></p>
    <?php endif; ?>

    <form method="POST" class="form-row">
        <input type="text" name="plan_name" placeholder="Plan name" required>
        <input type="number" name="price" step="0.01" min="0.01" placeholder="Price" required>
        <input type="number" name="duration_days" min="1" placeholder="Days" required>
        <input type="text" name="description" placeholder="Description">
        <button type="submit" name="add_plan">Add Plan</button>
    </form>

    <div class="table-wrap">
        <table>
            <tr><th>Plan</th><th>Price</th><th>Duration</th><th>Description</th><th></th></tr>
            <?php if (empty($plans)): ?>
                <tr><td colspan="5" class="empty">No plans yet.</td></tr>
            <?php else: ?>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><?= htmlspecialchars($plan["plan_name"]) ?></td>
                        <td>₱<?= number_format($plan["plan_price"], 2) ?></td>
                        <td><?= (int) $plan["duration_days"] ?> days</td>
                        <td><?= htmlspecialchars($plan["description"] ?? "") ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this plan?');">
                                <input type="hidden" name="plan_id" value="<?= (int) $plan["plan_id"] ?>">
                                <button type="submit" name="delete_plan" class="btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</section>
