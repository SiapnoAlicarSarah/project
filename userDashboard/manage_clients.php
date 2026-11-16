<?php
// expects $sub, $flash? uses $_SESSION flash handled by staff_top
$clients = $sub->allClients();
$clientMessage = $_SESSION["client_message"] ?? "";
unset($_SESSION["client_message"]);

if (isset($_POST["update_client"])) {
    $uid = (int) ($_POST["user_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    if ($name === "" || $email === "") {
        $_SESSION["client_message"] = "Name and email are required.";
    } else {
        $_SESSION["client_message"] = $sub->updateClient($uid, $name, $email);
    }
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

if (isset($_POST["delete_client"])) {
    $uid = (int) ($_POST["user_id"] ?? 0);
    $_SESSION["client_message"] = $sub->deleteClient($uid);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}
$clients = $sub->allClients();
?>
<dialog id="clientsDialog">
    <h2>Manage Clients</h2>
    <?php if ($clientMessage !== ""): ?>
        <p><?= htmlspecialchars($clientMessage) ?></p>
    <?php endif; ?>

    <?php if (empty($clients)): ?>
        <p>No clients yet.</p>
    <?php else: ?>
        <table>
            <tr><th>Name</th><th>Email</th><th>Action</th></tr>
            <?php foreach ($clients as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c["name"]) ?></td>
                    <td><?= htmlspecialchars($c["email"]) ?></td>
                    <td>
                        <form method="POST" style="display:inline-flex; gap:6px; align-items:center;">
                            <input type="hidden" name="user_id" value="<?= (int) $c["user_id"] ?>">
                            <input type="text" name="name" value="<?= htmlspecialchars($c["name"]) ?>" required>
                            <input type="email" name="email" value="<?= htmlspecialchars($c["email"]) ?>" required>
                            <button type="submit" name="update_client">Save</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this client?');">
                            <input type="hidden" name="user_id" value="<?= (int) $c["user_id"] ?>">
                            <button type="submit" name="delete_client" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <br>
    <button type="button" id="closeClients" class="btn-cancel">Close</button>
</dialog>
<script>
    var clientsDialog = document.getElementById("clientsDialog");
    if (document.getElementById("openClients")) {
        document.getElementById("openClients").onclick = function () { clientsDialog.showModal(); };
    }
    if (document.getElementById("closeClients")) {
        document.getElementById("closeClients").onclick = function () { clientsDialog.close(); };
    }
</script>