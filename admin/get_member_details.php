<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get user ID from request
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize arrays
    $subscription = null;
    $payments = [];

    try {
        // First, get subscription details
        $query = "SELECT s.*, p.name as plan_name, p.duration as plan_duration
                FROM subscriptions s 
                JOIN plans p ON s.plan_id = p.id 
                WHERE s.user_id = ? AND s.status = 'active'
                ORDER BY s.start_date DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($subscription) {
            // Format start date
            $subscription['start_date'] = date('M d, Y', strtotime($subscription['start_date']));
            
            // Calculate end date based on plan duration
            $end_date = strtotime($subscription['start_date'] . " +{$subscription['plan_duration']} months");
            $subscription['end_date'] = date('M d, Y', $end_date);
        }
    } catch (Exception $e) {
        error_log("Subscription query error: " . $e->getMessage());
        throw $e;
    }

    try {
        // Get payment history
        $query = "SELECT payment_date, amount, status, payment_method
                FROM payments 
                WHERE user_id = ? 
                ORDER BY payment_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($payments as &$payment) {
            if ($payment['payment_date']) {
                $payment['payment_date'] = date('M d, Y', strtotime($payment['payment_date']));
            }
            if (is_numeric($payment['amount'])) {
                $payment['amount'] = number_format((float)$payment['amount'], 2);
            }
        }
    } catch (Exception $e) {
        error_log("Payment query error: " . $e->getMessage());
        throw $e;
    }

    // Return all data
    echo json_encode([
        'success' => true,
        'subscription' => $subscription,
        'payments' => $payments
    ]);

} catch (Exception $e) {
    error_log("Error in get_member_details.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
