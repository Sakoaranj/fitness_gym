<?php
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!isset($_POST['subscription_id']) || !isset($_POST['action'])) {
        throw new Exception("Invalid request parameters");
    }

    $subscription_id = $_POST['subscription_id'];
    $action = $_POST['action'];

    // Get subscription and plan details
    $stmt = $db->prepare("
        SELECT 
            s.*,
            p.payment_id,
            p.status as payment_status,
            pl.duration
        FROM subscriptions s
        INNER JOIN payments p ON s.payment_id = p.id
        INNER JOIN plans pl ON s.plan_id = pl.id
        WHERE s.id = ?
    ");
    $stmt->execute([$subscription_id]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        throw new Exception("Subscription not found");
    }

    $db->beginTransaction();

    try {
        if ($action === 'approve') {
            // Set subscription dates based on plan duration
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+{$subscription['duration']} months"));

            // Update subscription status and dates
            $subscription_stmt = $db->prepare("
                UPDATE subscriptions 
                SET status = 'active',
                    start_date = ?,
                    end_date = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $subscription_stmt->execute([$start_date, $end_date, $subscription_id]);

            // Update payment status
            $payment_stmt = $db->prepare("
                UPDATE payments 
                SET status = 'approved',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $payment_stmt->execute([$subscription['payment_id']]);

            $_SESSION['message'] = "Subscription has been approved successfully.";
            $_SESSION['message_type'] = "success";
        } 
        elseif ($action === 'reject') {
            // Update subscription status to cancelled
            $subscription_stmt = $db->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $subscription_stmt->execute([$subscription_id]);

            // Update payment status
            $payment_stmt = $db->prepare("
                UPDATE payments 
                SET status = 'rejected',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $payment_stmt->execute([$subscription['payment_id']]);

            $_SESSION['message'] = "Subscription has been rejected.";
            $_SESSION['message_type'] = "warning";
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

header("Location: subscriptions.php");
exit;
?>
