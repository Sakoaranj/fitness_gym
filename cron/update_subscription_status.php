<?php
require_once '../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Update expired subscriptions
    $stmt = $db->prepare("
        UPDATE subscriptions 
        SET status = 'expired',
            updated_at = NOW()
        WHERE status = 'active' 
        AND end_date < CURDATE()
    ");
    $stmt->execute();

    echo "Successfully updated expired subscriptions\n";
} catch (Exception $e) {
    error_log("Error updating subscription status: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>
