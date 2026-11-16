<?php $today = date("Y-m-d"); ?>

<!-- Manage User Plans popup -->
<dialog id="userPlansDialog">
    <h2>Manage User Plans</h2>

    <div class="filters">
        <button type="button" class="filter-btn" data-filter="all">All</button>
        <button type="button" class="filter-btn" data-filter="pending">Pending</button>
        <button type="button" class="filter-btn" data-filter="active">Active</button>
        <button type="button" class="filter-btn" data-filter="expired">Expired</button>
        <button type="button" class="filter-btn" data-filter="cancelled">Cancelled</button>
    </div>

    <table>
        <tr>
            <th>Client</th>
            <th>Plan</th>
            <th>Price</th>
            <th>Period</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if (empty($allMemberships)): ?>
            <tr><td colspan="7">No memberships yet.</td></tr>
        <?php else: ?>
            <?php foreach ($allMemberships as $m): ?>
                <?php
                    // work out what to show for each membership
                    if ($m["status"] === "cancelled") {
                        $key = "cancelled"; $label = "Cancelled";
                    } elseif ($m["status"] === "pending") {
                        $key = "pending"; $label = "Pending";
                    } elseif ($m["end_date"] < $today) {
                        $key = "expired"; $label = "Expired";
                    } elseif ($m["start_date"] > $today) {
                        $key = "upcoming"; $label = "Not started";
                    } else {
                        $key = "active"; $label = "Active";
                    }
                ?>
                <tr data-status="<?= $key ?>">
                    <td><?= htmlspecialchars($m["client_name"]) ?></td>
                    <td><?= htmlspecialchars($m["plan_name"]) ?></td>
                    <td>₱<?= number_format($m["plan_price"], 2) ?></td>
                    <td><?= date("M d, Y", strtotime($m["start_date"])) ?> -
                        <?= date("M d, Y", strtotime($m["end_date"])) ?></td>
                    <td>
                        <?= htmlspecialchars($m["payment_method"] ?? "n/a") ?><br>
                        <small><?= htmlspecialchars($m["payment_status"] ?? "") ?></small>
                    </td>
                    <td><span class="chip chip-<?= $key ?>"><?= $label ?></span></td>
                    <td>
                        <?php if ($key === "pending"): ?>
                            <form method="POST">
                                <input type="hidden" name="membership_id" value="<?= (int) $m["membership_id"] ?>">
                                <button type="submit" name="accept_request">Accept</button>
                                <button type="submit" name="decline_request" class="btn-danger"
                                        onclick="return confirm('Decline this request?');">Decline</button>
                            </form>
                        <?php elseif ($key === "upcoming"): ?>
                            <form method="POST">
                                <input type="hidden" name="membership_id" value="<?= (int) $m["membership_id"] ?>">
                                <button type="submit" name="cancel_membership" class="btn-danger"
                                        onclick="return confirm('Cancel this membership?');">Cancel</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <br>
    <button type="button" id="closeUserPlans" class="btn-cancel">Close</button>
</dialog>

<script>
    var userPlansDialog = document.getElementById("userPlansDialog");
    document.getElementById("openUserPlans").onclick = function () { userPlansDialog.showModal(); };
    document.getElementById("closeUserPlans").onclick = function () { userPlansDialog.close(); };

    // filter buttons
    document.querySelectorAll(".filter-btn").forEach(function (btn) {
        btn.onclick = function () {
            var f = btn.dataset.filter;
            document.querySelectorAll("#userPlansDialog tr[data-status]").forEach(function (row) {
                var s = row.dataset.status;
                var show = (f === "all") ||
                           (f === "active" && (s === "active" || s === "upcoming")) ||
                           (f === s);
                row.style.display = show ? "" : "none";
            });
        };
    });
</script>