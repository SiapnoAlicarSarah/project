<?php
namespace App\Models;

use App\Database;
use PDO;

class Subscription
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connection();
    }

    public function getClientId($userId)
    {
        $stmt = $this->conn->prepare("SELECT client_id FROM client WHERE user_id = ?");
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $ins = $this->conn->prepare("INSERT INTO client (user_id, contact_number, address, birthdate, emergency_contact) VALUES (?, '', '', '0000-00-00', '')");
        $ins->execute([$userId]);
        return (int) $this->conn->lastInsertId();
    }

    public function getStaffId($userId)
    {
        $stmt = $this->conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;   // owners have no staff row
    }

    // latest APPROVED membership (the one that decides the status)
    public function latest($clientId)
    {
        $sql = "SELECT m.*, p.plan_name FROM membership m
                JOIN membership_plan p ON p.plan_id = m.plan_id
                WHERE m.client_id = ? AND m.status = 'active'
                ORDER BY m.end_date DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // a request that is still waiting for staff
    public function pending($clientId)
    {
        $sql = "SELECT m.*, p.plan_name FROM membership m
                JOIN membership_plan p ON p.plan_id = m.plan_id
                WHERE m.client_id = ? AND m.status = 'pending'
                ORDER BY m.membership_id DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // renewing early starts the day after the current membership ends
    private function nextDates($clientId, $durationDays)
    {
        $latest = $this->latest($clientId);
        $today  = date("Y-m-d");
        $start  = ($latest && $latest["end_date"] >= $today)
            ? date("Y-m-d", strtotime($latest["end_date"] . " +1 day"))
            : $today;
        $end = date("Y-m-d", strtotime($start . " +" . ((int) $durationDays - 1) . " days"));
        return [$start, $end];
    }

    private function notify($userId, $membershipId, $message)
    {
        $roleStmt = $this->conn->prepare("SELECT role FROM users WHERE user_id = ?");
        $roleStmt->execute([$userId]);
        $role = $roleStmt->fetchColumn() ?: "client";
        $this->conn->prepare(
            "INSERT INTO notification (user_id, membership_id, message, role) VALUES (?, ?, ?, ?)"
        )->execute([$userId, $membershipId, $message, $role]);
    }

    // removes the "new request" alerts staff and owners received for this membership
    private function clearStaffNotifications($membershipId)
    {
        $this->conn->prepare(
            "DELETE FROM notification
             WHERE membership_id = ?
               AND user_id IN (SELECT user_id FROM users WHERE role IN ('staff', 'owner'))"
        )->execute([$membershipId]);
    }

    /* ---------- Client side ---------- */

    // sends a request; staff approve it later
    public function subscribe($clientId, $userId, $planId, $method)
    {
        $allowed = ["cash", "gcash", "bank_transfer"];
        if (!in_array($method, $allowed, true)) {
            return "Please choose a payment method.";
        }

        if ($this->pending($clientId)) {
            return "You already have a request waiting for staff approval.";
        }

        $stmt = $this->conn->prepare("SELECT * FROM membership_plan WHERE plan_id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$plan) {
            return "Plan not found.";
        }

        list($start, $end) = $this->nextDates($clientId, $plan["duration_days"]);

        try {
            $this->conn->beginTransaction();

            $this->conn->prepare(
                "INSERT INTO membership (client_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'pending')"
            )->execute([$clientId, $planId, $start, $end]);
            $membershipId = (int) $this->conn->lastInsertId();

            $this->conn->prepare(
                "INSERT INTO payment (membership_id, client_id, amount, payment_method, payment_status) VALUES (?, ?, ?, ?, 'pending')"
            )->execute([$membershipId, $clientId, $plan["plan_price"], $method]);
            $paymentId = (int) $this->conn->lastInsertId();
            $referenceNo = "FS-" . date("Ymd") . "-" . strtoupper(substr(md5("ref-" . $paymentId), 0, 6));
            $this->conn->prepare("UPDATE payment SET reference_no = ? WHERE payment_id = ?")
                ->execute([$referenceNo, $paymentId]);

            // tell the client
            $this->notify(
                $userId,
                $membershipId,
                "Your request for " . $plan["plan_name"] . " was sent. Please wait for staff to approve it."
            );

            // tell every staff and owner account
            $nameStmt = $this->conn->prepare("SELECT name FROM users WHERE user_id = ?");
            $nameStmt->execute([$userId]);
            $clientName = $nameStmt->fetchColumn();

            $staffUsers = $this->conn->query(
                "SELECT user_id FROM users WHERE role IN ('staff', 'owner')"
            )->fetchAll(PDO::FETCH_COLUMN);

            foreach ($staffUsers as $staffUserId) {
                $this->notify(
                    $staffUserId,
                    $membershipId,
                    "New membership request: " . $clientName . " wants " . $plan["plan_name"] .
                    " (₱" . number_format($plan["plan_price"], 2) . " via " . $method . ")."
                );
            }

            $this->conn->commit();
            return "Request sent! Please wait for staff to approve your " . $plan["plan_name"] . " membership.";
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            return "Request failed: " . $e->getMessage();
        }
    }

    /* ---------- Staff side ---------- */

    public function pendingRequests()
    {
        $sql = "SELECT m.membership_id, p.plan_name, p.plan_price, p.duration_days,
                       u.name AS client_name, pay.payment_method
                FROM membership m
                JOIN membership_plan p ON p.plan_id = m.plan_id
                JOIN client c ON c.client_id = m.client_id
                JOIN users u ON u.user_id = c.user_id
                LEFT JOIN payment pay ON pay.membership_id = m.membership_id
                WHERE m.status = 'pending'
                ORDER BY m.membership_id ASC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findPending($membershipId)
    {
        $sql = "SELECT m.*, p.plan_name, p.duration_days, c.user_id AS client_user_id
                FROM membership m
                JOIN membership_plan p ON p.plan_id = m.plan_id
                JOIN client c ON c.client_id = m.client_id
                WHERE m.membership_id = ? AND m.status = 'pending'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$membershipId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function accept($membershipId, $staffUserId)
    {
        $req = $this->findPending($membershipId);
        if (!$req) {
            return "This request was already handled.";
        }

        list($start, $end) = $this->nextDates($req["client_id"], $req["duration_days"]);
        $staffId = $this->getStaffId($staffUserId);

        try {
            $this->conn->beginTransaction();

            $this->conn->prepare(
                "UPDATE membership SET status = 'active', start_date = ?, end_date = ? WHERE membership_id = ?"
            )->execute([$start, $end, $membershipId]);

            $this->conn->prepare(
                "UPDATE payment SET payment_status = 'paid', received_by = ? WHERE membership_id = ?"
            )->execute([$staffId, $membershipId]);

            $this->clearStaffNotifications($membershipId);

            $this->notify(
                $req["client_user_id"],
                $membershipId,
                "Approved! Your " . $req["plan_name"] . " membership is active until " . date("M d, Y", strtotime($end)) . "."
            );

            $this->conn->commit();
            return "Request accepted. The membership is now active.";
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            return "Could not accept: " . $e->getMessage();
        }
    }

    public function decline($membershipId, $staffUserId)
{
    $req = $this->findPending($membershipId);
    if (!$req) {
        return "This request was already handled.";
    }

    // Get the staff_id from the logged-in user_id (null if owner)
    $staffId = $this->getStaffId($staffUserId);

    try {
        $this->conn->beginTransaction();

        // Include cancelled_by in the update query
        $this->conn->prepare(
            "UPDATE membership SET status = 'cancelled', cancelled_by = ? WHERE membership_id = ?"
        )->execute([$staffId, $membershipId]);

        $this->conn->prepare(
            "UPDATE payment SET payment_status = 'failed' WHERE membership_id = ?"
        )->execute([$membershipId]);

        $this->clearStaffNotifications($membershipId);

        $this->notify(
            $req["client_user_id"],
            $membershipId,
            "Your request for " . $req["plan_name"] . " was declined. Please contact the gym for details."
        );

        $this->conn->commit();
        return "Request declined.";
    } catch (\PDOException $e) {
        $this->conn->rollBack();
        return "Could not decline: " . $e->getMessage();
    }
}

    /* ---------- Manage user plans ---------- */

    public function allMemberships()
    {
        $sql = "SELECT m.membership_id, m.start_date, m.end_date, m.status,
                       p.plan_name, p.plan_price,
                       u.name AS client_name,
                       pay.payment_method, pay.payment_status
                FROM membership m
                JOIN membership_plan p ON p.plan_id = m.plan_id
                JOIN client c ON c.client_id = m.client_id
                JOIN users u ON u.user_id = c.user_id
                LEFT JOIN payment pay ON pay.membership_id = m.membership_id
                ORDER BY (m.status = 'pending') DESC, m.membership_id DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancel($membershipId, $staffUserId)
{
    $sql = "SELECT m.*, p.plan_name, c.user_id AS client_user_id
            FROM membership m
            JOIN membership_plan p ON p.plan_id = m.plan_id
            JOIN client c ON c.client_id = m.client_id
            WHERE m.membership_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$membershipId]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$m) {
        return "Membership not found.";
    }
    if ($m["status"] === "cancelled") {
        return "This membership is already cancelled.";
    }

    $wasPending = ($m["status"] === "pending");
    $staffId = $this->getStaffId($staffUserId);

    try {
        $this->conn->beginTransaction();

        // Include cancelled_by here as well
        $this->conn->prepare(
            "UPDATE membership SET status = 'cancelled', cancelled_by = ? WHERE membership_id = ?"
        )->execute([$staffId, $membershipId]);

        $this->conn->prepare(
            "UPDATE payment
             SET payment_status = CASE WHEN payment_status = 'paid' THEN 'refunded' ELSE 'failed' END
             WHERE membership_id = ?"
        )->execute([$membershipId]);

        $this->clearStaffNotifications($membershipId);

        $this->notify(
            $m["client_user_id"],
            $membershipId,
            $wasPending
                ? "Your request for " . $m["plan_name"] . " was declined. Please contact the gym for details."
                : "Your " . $m["plan_name"] . " membership was cancelled by staff."
        );

        $this->conn->commit();
        return $wasPending ? "Request declined." : "Membership cancelled.";
    } catch (\PDOException $e) {
        $this->conn->rollBack();
        return "Could not cancel: " . $e->getMessage();
    }
}

    /* ---------- Notifications (clients and staff) ---------- */

    public function notifications($userId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC, notification_id DESC LIMIT 10");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function unreadCount($userId)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markAllRead($userId)
    {
        $this->conn->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    }

    public function clearAll($userId)
    {
        $this->conn->prepare("DELETE FROM notification WHERE user_id = ?")->execute([$userId]);
    }

    public function clientPayments($clientId)
    {
        $sql = "SELECT pay.*, p.plan_name FROM payment pay
                JOIN membership m ON m.membership_id = pay.membership_id
                JOIN membership_plan p ON p.plan_id = m.plan_id
                WHERE pay.client_id = ?
                ORDER BY pay.payment_id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ---------- Client records (staff/owner) ---------- */
    public function allClients()
    {
        $sql = "SELECT u.user_id, u.name, u.email, c.client_id
                FROM users u
                JOIN client c ON c.user_id = u.user_id
                WHERE u.role = 'client'
                ORDER BY u.user_id DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ---------- Staff approval (owner) ---------- */
public function pendingStaff()
{
    $sql = "SELECT u.user_id, u.name, u.email, s.staff_id, s.status
            FROM users u JOIN staff s ON s.user_id = u.user_id
            WHERE s.status = 'pending' ORDER BY u.user_id DESC";
    return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

public function allStaff()
{
    $sql = "SELECT u.user_id, u.name, u.email, s.staff_id, s.status, s.position, s.hire_date
            FROM users u JOIN staff s ON s.user_id = u.user_id
            ORDER BY u.user_id DESC";
    return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

public function approveStaff($userId)
{
    $this->conn->prepare("UPDATE staff SET status = 'approved' WHERE user_id = ?")->execute([$userId]);
    return "Staff approved.";
}

public function rejectStaff($userId)
{
    $this->conn->prepare("UPDATE staff SET status = 'rejected' WHERE user_id = ?")->execute([$userId]);
    return "Staff rejected.";
}

public function updateClient($userId, $name, $email)
    {
        $stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ? AND role = 'client'");
        $stmt->execute([$name, $email, $userId]);
        return "Client updated.";
    }

    public function deleteClient($userId)
    {
        $this->conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'client'")->execute([$userId]);
        return "Client deleted.";
    }

    /* ---------- Summary counts ---------- */
    public function counts()
    {
        $out = [];
        $out["clients"] = (int) $this->conn->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
        $out["staff"] = (int) $this->conn->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
        $out["memberships"] = (int) $this->conn->query("SELECT COUNT(*) FROM membership")->fetchColumn();
        $out["pending"] = (int) $this->conn->query("SELECT COUNT(*) FROM membership WHERE status='pending'")->fetchColumn();
        $out["active"] = (int) $this->conn->query("SELECT COUNT(*) FROM membership WHERE status='active'")->fetchColumn();
        $out["expired"] = (int) $this->conn->query("SELECT COUNT(*) FROM membership WHERE status='active' AND end_date < CURDATE()")->fetchColumn();
        return $out;
    }
}