<?php
use App\Models\Subscription;

$sub = new Subscription();
$staffUserId = (int) $_SESSION["user_id"];

if (isset($_POST["accept_request"])) {
    $_SESSION["flash"] = $sub->accept((int) ($_POST["membership_id"] ?? 0), $staffUserId);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

if (isset($_POST["decline_request"])) {
    $_SESSION["flash"] = $sub->decline((int) ($_POST["membership_id"] ?? 0), $staffUserId);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

if (isset($_POST["cancel_membership"])) {
    $_SESSION["flash"] = $sub->cancel((int) ($_POST["membership_id"] ?? 0), $staffUserId);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

if (isset($_POST["mark_read"])) {
    $sub->markAllRead($staffUserId);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

$flash = $_SESSION["flash"] ?? "";
unset($_SESSION["flash"]);

$pendingRequests = $sub->pendingRequests();
$notifications   = $sub->notifications($staffUserId);
$allMemberships  = $sub->allMemberships();