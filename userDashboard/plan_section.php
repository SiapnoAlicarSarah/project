<?php
use App\Models\Membership_plan;

$planModel = new Membership_plan();
$planMessage = $_SESSION["plan_message"] ?? "";
unset($_SESSION["plan_message"]);

/* ---------- Add plan ---------- */
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

    // redirect so refreshing the page doesn't add the plan again
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

/* ---------- Delete plan ---------- */
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

<?php if ($planMessage !== ""): ?>
    <p><?= htmlspecialchars($planMessage) ?></p>
<?php endif; ?>

<!-- Create popup -->
<dialog id="planDialog">
    <h2>Create Membership Plan</h2>
    <form method="POST">
        <label for="plan_name">Plan Name:</label>
        <input type="text" name="plan_name" id="plan_name" required> <br><br>

        <label for="price">Price (₱):</label>
        <input type="number" name="price" id="price" step="0.01" min="0" required> <br><br>

        <label for="duration_days">Duration (days):</label>
        <input type="number" name="duration_days" id="duration_days" min="1" required> <br><br>

        <label for="description">Description:</label> <br>
        <textarea name="description" id="description"></textarea> <br><br>

        <input type="submit" name="add_plan" value="Add Plan">
        <button type="button" id="closePlan">Cancel</button>
    </form>
</dialog>

<!-- View popup -->
<dialog id="viewDialog">
    <h2>Membership Plans</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Plan</th>
            <th>Price</th>
            <th>Duration</th>
            <th>Description</th>
        </tr>
        <?php if (empty($plans)): ?>
            <tr><td colspan="4">No plans yet.</td></tr>
        <?php else: ?>
            <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><?= htmlspecialchars($plan["plan_name"]) ?></td>
                    <td>₱<?= number_format($plan["plan_price"], 2) ?></td>
                    <td><?= (int) $plan["duration_days"] ?> days</td>
                    <td><?= htmlspecialchars($plan["description"] ?? "") ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
    <br>
    <button type="button" id="closeView">Close</button>
</dialog>

<!-- Manage popup -->
<dialog id="manageDialog">
    <h2>Manage Plans</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Plan</th>
            <th>Price</th>
            <th>Duration</th>
            <th>Action</th>
        </tr>
        <?php if (empty($plans)): ?>
            <tr><td colspan="4">No plans to manage.</td></tr>
        <?php else: ?>
            <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><?= htmlspecialchars($plan["plan_name"]) ?></td>
                    <td>₱<?= number_format($plan["plan_price"], 2) ?></td>
                    <td><?= (int) $plan["duration_days"] ?> days</td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete this plan? This cannot be undone.');">
                            <input type="hidden" name="plan_id" value="<?= (int) $plan["plan_id"] ?>">
                            <button type="submit" name="delete_plan" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
    <br>
    <button type="button" id="closeManage">Close</button>
</dialog>

<script>
    var planDialog = document.getElementById("planDialog");
    document.getElementById("openPlan").onclick = function () { planDialog.showModal(); };
    document.getElementById("closePlan").onclick = function () { planDialog.close(); };

    var viewDialog = document.getElementById("viewDialog");
    document.getElementById("openView").onclick = function () { viewDialog.showModal(); };
    document.getElementById("closeView").onclick = function () { viewDialog.close(); };

    var manageDialog = document.getElementById("manageDialog");
    document.getElementById("openManage").onclick = function () { manageDialog.showModal(); };
    document.getElementById("closeManage").onclick = function () { manageDialog.close(); };
</script>