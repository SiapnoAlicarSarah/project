<!-- Notifications popup (staff and owner) -->
<dialog id="notifDialog">
    <h2>Notifications</h2>

    <h3>Pending membership requests</h3>
    <?php if (empty($pendingRequests)): ?>
        <p>No pending requests.</p>
    <?php else: ?>
        <?php foreach ($pendingRequests as $r): ?>
            <div class="req-card">
                <p><strong><?= htmlspecialchars($r["client_name"]) ?></strong> wants
                   <strong><?= htmlspecialchars($r["plan_name"]) ?></strong></p>
                <p>₱<?= number_format($r["plan_price"], 2) ?> ·
                   <?= (int) $r["duration_days"] ?> days ·
                   Payment: <?= htmlspecialchars($r["payment_method"] ?? "n/a") ?></p>

                <form method="POST">
                    <input type="hidden" name="membership_id" value="<?= (int) $r["membership_id"] ?>">
                    <button type="submit" name="accept_request">Accept</button>
                    <button type="submit" name="decline_request" class="btn-danger"
                            onclick="return confirm('Decline this request?');">Decline</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h3>Recent notifications</h3>
    <?php if (empty($notifications)): ?>
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
    var notifDialog = document.getElementById("notifDialog");
    document.getElementById("openNotif").onclick = function () { notifDialog.showModal(); };
    document.getElementById("closeNotif").onclick = function () { notifDialog.close(); };
</script>