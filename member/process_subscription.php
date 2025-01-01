<?php
session_start();
require_once '../config/Database.php';

// Ensure the uploads directory exists
$upload_dir = '../uploads/payments/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if user already has an active or pending subscription
    $check_stmt = $db->prepare("
        SELECT id, status
        FROM subscriptions 
        WHERE user_id = ? 
        AND status IN ('active', 'pending')
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $check_stmt->execute([$_SESSION['user_id']]);
    $existing_subscription = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_subscription) {
        $_SESSION['message'] = "You already have an " . $existing_subscription['status'] . 
            " subscription. Please wait for approval or check your subscription status.";
        $_SESSION['message_type'] = "orange";
        header("Location: plans.php");
        exit;
    }

    // Validate required fields
    $required_fields = ['plan_id', 'payment_method', 'payment_reference'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Please fill in all required fields");
        }
    }

    // Validate GCash reference number format if payment method is GCash
    if (strtolower($_POST['payment_method']) === 'gcash') {
        $reference = trim($_POST['payment_reference']);
        if (!preg_match('/^\d{13}$/', $reference)) {
            throw new Exception("Invalid GCash reference number. It should be exactly 13 digits.");
        }
    }

    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Please upload your payment proof");
    }

    // Get plan details
    $plan_stmt = $db->prepare("SELECT * FROM plans WHERE id = ? AND status = 'active'");
    $plan_stmt->execute([$_POST['plan_id']]);
    $plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        throw new Exception("Invalid or inactive plan selected");
    }

    // Validate payment method
    $payment_method_stmt = $db->prepare("
        SELECT * FROM payment_settings 
        WHERE payment_method = ? AND status = 'active'
    ");
    $payment_method_stmt->execute([strtoupper($_POST['payment_method'])]);
    if (!$payment_method_stmt->fetch()) {
        throw new Exception("Invalid payment method selected");
    }

    // Validate and process payment proof upload
    $payment_proof = $_FILES['payment_proof'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($payment_proof['type'], $allowed_types)) {
        throw new Exception("Invalid file type. Only JPG, PNG, and GIF files are allowed.");
    }

    if ($payment_proof['size'] > 5000000) { // 5MB limit
        throw new Exception("File is too large. Maximum size is 5MB.");
    }

    // Generate unique filename
    $file_extension = pathinfo($payment_proof['name'], PATHINFO_EXTENSION);
    $new_filename = 'payment_' . time() . '_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($payment_proof['tmp_name'], $upload_path)) {
        throw new Exception("Failed to upload payment proof. Please try again.");
    }

    $db->beginTransaction();

    try {
        // Create payment record
        $payment_stmt = $db->prepare("
            INSERT INTO payments (
                user_id, 
                amount, 
                payment_method, 
                payment_reference, 
                payment_screenshot, 
                status,
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, 'pending', NOW()
            )
        ");
        
        $payment_stmt->execute([
            $_SESSION['user_id'],
            $plan['price'],
            strtoupper($_POST['payment_method']),
            $_POST['payment_reference'],
            $new_filename
        ]);
        
        $payment_id = $db->lastInsertId();

        // Create subscription record with pending status
        $subscription_stmt = $db->prepare("
            INSERT INTO subscriptions (
                user_id,
                plan_id,
                payment_id,
                status,
                created_at
            ) VALUES (
                ?, ?, ?, 'pending', NOW()
            )
        ");
        
        $subscription_stmt->execute([
            $_SESSION['user_id'],
            $plan['id'],
            $payment_id
        ]);

        $db->commit();

        // Redirect to subscription page without setting a message
        header("Location: subscription.php");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        // Delete uploaded file if exists
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }
        throw $e;
    }
} catch (Exception $e) {
    // Log error with detailed information
    error_log(sprintf(
        "Subscription Error: %s\nUser ID: %s\nPOST Data: %s\n",
        $e->getMessage(),
        $_SESSION['user_id'] ?? 'Not set',
        print_r($_POST, true)
    ));

    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "red";
    header("Location: plans.php");
    exit;
}
?>
