<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get member's subscription status
$subscription_stmt = $db->prepare("
    SELECT 
        s.*,
        p.status as payment_status,
        pl.name as plan_name,
        pl.duration as plan_duration
    FROM subscriptions s
    LEFT JOIN payments p ON s.payment_id = p.id
    LEFT JOIN plans pl ON pl.name = s.plan
    WHERE s.user_id = ? 
    AND s.status IN ('active', 'pending')
    ORDER BY s.created_at DESC 
    LIMIT 1
");
$subscription_stmt->execute([$_SESSION['user_id']]);
$subscription = $subscription_stmt->fetch(PDO::FETCH_ASSOC);

// Get member's saved workouts count - with error handling
try {
    $saved_workouts_stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM member_preferences 
        WHERE user_id = ? AND type = 'workout'
    ");
    $saved_workouts_stmt->execute([$_SESSION['user_id']]);
    $saved_workouts = $saved_workouts_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist or other error, set count to 0
    $saved_workouts = ['count' => 0];
}

?>

<main>
    <div class="dashboard-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="card-panel <?php echo $_SESSION['message_type']; ?> lighten-4">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Membership Status Card -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">card_membership</i>
                            Membership Status
                        </span>
                        <?php if ($subscription): ?>
                            <?php if ($subscription['status'] === 'pending'): ?>
                                <div class="card-panel orange lighten-4">
                                    <i class="material-icons left">pending</i>
                                    <strong>Subscription Status: Pending Approval</strong><br>
                                    Your subscription is currently pending approval. Please wait while we verify your payment.
                                    <br><br>
                                    <a href="subscription.php" class="btn-small orange">View Subscription Status</a>
                                </div>
                            <?php else: ?>
                                <div class="membership-info">
                                    <p><strong>Plan:</strong> <?php echo htmlspecialchars($subscription['plan_name']); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="new badge green" data-badge-caption="Active"></span>
                                    </p>
                                    <p><strong>Valid Until:</strong> <?php echo date('F d, Y', strtotime($subscription['end_date'])); ?></p>
                                    <a href="subscription.php" class="btn-small blue">View Subscription Details</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="card-panel blue lighten-4">
                                <i class="material-icons left">info</i>
                                You don't have an active subscription. Subscribe to a plan to access all features.
                                <br><br>
                                <a href="plans.php" class="btn-small blue">View Available Plans</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row">
            <div class="col s12 m6">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">favorite</i>
                            Saved Workouts
                        </span>
                        <div class="center-align">
                            <h3><?php echo $saved_workouts['count']; ?></h3>
                            <p>Workouts Saved</p>
                            <a href="workouts.php" class="btn blue waves-effect waves-light">
                                View Workouts
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.card .card-title {
    font-size: 1.5rem;
    margin-bottom: 20px;
}

.membership-info {
    padding: 15px;
    background: #f5f5f5;
    border-radius: 4px;
}

.membership-info p {
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge {
    float: none !important;
}

.btn-small {
    margin-top: 10px;
}

.center-align h3 {
    margin: 10px 0;
    color: #2196F3;
}
</style>

<?php require_once 'includes/footer.php'; ?>
