<?php
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'Invalid payment ID.';
    $_SESSION['message_type'] = 'red';
    header('Location: payments.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Get payment details
    $stmt = $db->prepare("
        SELECT * FROM payments 
        WHERE id = ? AND user_id = ? 
        AND status != 'verified'
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('Payment not found or cannot be cancelled.');
    }

    // Update payment status
    $stmt = $db->prepare("
        UPDATE payments 
        SET status = 'cancelled', 
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$_GET['id']]);

    // Update related subscription if exists
    $stmt = $db->prepare("
        UPDATE subscriptions 
        SET status = 'cancelled' 
        WHERE payment_id = ?
    ");
    $stmt->execute([$_GET['id']]);

    $db->commit();

    $_SESSION['message'] = 'Payment cancelled successfully.';
    $_SESSION['message_type'] = 'green';

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'red';
}

header('Location: payments.php');
exit;
