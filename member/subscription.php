<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get current subscription with payment details
$stmt = $db->prepare("
    SELECT 
        s.id,
        s.user_id,
        s.plan_id,
        s.payment_id,
        s.start_date,
        s.end_date,
        s.status as subscription_status,
        s.created_at,
        p.payment_method,
        p.payment_reference,
        p.payment_screenshot,
        p.status as payment_status,
        p.amount,
        p.created_at as payment_date,
        pl.name as plan_name,
        pl.description as plan_description,
        pl.features as plan_features,
        pl.duration as plan_duration,
        pl.price as plan_price
    FROM subscriptions s
    LEFT JOIN payments p ON s.payment_id = p.id
    LEFT JOIN plans pl ON s.plan_id = pl.id
    WHERE s.user_id = ? 
    AND (s.status = 'active' OR s.status = 'pending')
    ORDER BY s.created_at DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Get subscription history
$history_stmt = $db->prepare("
    SELECT 
        s.id,
        s.plan_id,
        s.payment_id,
        s.start_date,
        s.end_date,
        s.status as subscription_status,
        s.created_at,
        p.payment_method,
        p.payment_reference,
        p.status as payment_status,
        p.amount,
        p.created_at as payment_date,
        pl.name as plan_name,
        pl.duration as plan_duration,
        pl.price as plan_price
    FROM subscriptions s
    LEFT JOIN payments p ON s.payment_id = p.id
    LEFT JOIN plans pl ON s.plan_id = pl.id
    WHERE s.user_id = ? 
    ORDER BY s.created_at DESC
");
$history_stmt->execute([$_SESSION['user_id']]);
$subscription_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Clear success message after displaying once
if (isset($_SESSION['message']) && isset($_SESSION['message_displayed'])) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    unset($_SESSION['message_displayed']);
}
?>

<main>
    <div class="dashboard-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="card-panel <?php echo $_SESSION['message_type']; ?> lighten-4">
                <?php 
                echo $_SESSION['message'];
                $_SESSION['message_displayed'] = true;
                ?>
            </div>
        <?php elseif ($subscription && $subscription['subscription_status'] === 'pending'): ?>
            <div class="card-panel orange lighten-4">
                <i class="material-icons left">pending</i>
                Your subscription request has been submitted and is pending approval. Please wait for admin verification.
            </div>
        <?php endif; ?>

        <?php if (!$subscription): ?>
            <div class="card-panel blue lighten-4">
                <i class="material-icons left">info</i>
                You don't have any active subscription. Visit the <a href="plans.php" class="blue-text text-darken-4">Plans page</a> to subscribe.
            </div>
        <?php endif; ?>

        <?php if (!empty($subscription_history)): ?>
            <!-- Subscription History -->
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">
                                <i class="material-icons left">history</i>
                                Subscription History
                            </span>
                            <table class="striped responsive-table">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th>Amount</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Payment Ref</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscription_history as $history): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($history['plan_name']); ?></td>
                                            <td>₱<?php echo number_format($history['amount'], 2); ?></td>
                                            <td><?php echo $history['start_date'] ? date('M j, Y', strtotime($history['start_date'])) : '-'; ?></td>
                                            <td><?php echo $history['end_date'] ? date('M j, Y', strtotime($history['end_date'])) : '-'; ?></td>
                                            <td>
                                                <?php if ($history['subscription_status'] === 'pending'): ?>
                                                    <span class="orange-text">Pending</span>
                                                <?php elseif ($history['subscription_status'] === 'active'): ?>
                                                    <span class="green-text">Active</span>
                                                <?php elseif ($history['subscription_status'] === 'expired'): ?>
                                                    <span class="grey-text">Expired</span>
                                                <?php elseif ($history['subscription_status'] === 'cancelled'): ?>
                                                    <span class="red-text">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($history['payment_reference']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.querySelectorAll('.modal');
    M.Modal.init(modal);
});

function viewPaymentProof(imagePath, reference) {
    document.getElementById('payment-proof-image').src = imagePath;
    document.getElementById('payment-reference-display').textContent = 'Reference: ' + reference;
    var modal = M.Modal.getInstance(document.getElementById('payment-proof-modal'));
    modal.open();
}
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.card .card-content {
    padding: 24px;
}

.collection {
    border: none;
    margin: 0;
}

.collection .collection-item {
    border-bottom: 1px solid #e0e0e0;
    padding: 15px;
}

.collection .collection-item:last-child {
    border-bottom: none;
}

.plan-features {
    margin-top: 10px;
}

.plan-features ul {
    margin-top: 10px;
    margin-left: 20px;
}

.plan-features li {
    margin-bottom: 5px;
}

.payment-proof {
    margin-top: 10px;
}

table {
    margin-top: 20px;
}

th {
    background-color: #f5f5f5;
    padding: 15px 10px;
}

td {
    padding: 15px 10px;
    vertical-align: middle;
}

.modal {
    max-width: 600px;
    max-height: 90%;
}

#payment-proof-image {
    margin: 20px 0;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
}
</style>

<?php require_once 'includes/footer.php'; ?>
