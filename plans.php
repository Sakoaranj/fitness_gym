<?php
session_start();
require_once 'includes/header.php';
require_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get active plans
$stmt = $db->prepare("
    SELECT * FROM plans 
    WHERE status = 'active' 
    ORDER BY price ASC
");
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's active subscription if any
$stmt = $db->prepare("
    SELECT s.*, p.name as plan_name, p.price as plan_price
    FROM subscriptions s
    LEFT JOIN plans p ON s.plan = p.name
    WHERE s.user_id = ? AND s.status = 'active' AND s.end_date >= CURRENT_DATE
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$active_subscription = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<main>
    <div class="dashboard-container">
        <?php if ($active_subscription): ?>
        <div class="row">
            <div class="col s12">
                <div class="card blue-grey darken-1">
                    <div class="card-content white-text">
                        <span class="card-title">Current Subscription</span>
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s12 m6">
                                <p><strong>Plan:</strong> <?php echo htmlspecialchars($active_subscription['plan_name']); ?></p>
                                <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($active_subscription['start_date'])); ?></p>
                            </div>
                            <div class="col s12 m6">
                                <p><strong>End Date:</strong> <?php echo date('M d, Y', strtotime($active_subscription['end_date'])); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="status-badge status-<?php echo strtolower($active_subscription['status']); ?>">
                                        <?php echo ucfirst($active_subscription['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Available Plans</span>
                        <div class="row">
                            <?php foreach ($plans as $plan): ?>
                                <div class="col s12 m6 l4">
                                    <div class="card hoverable">
                                        <div class="card-content">
                                            <span class="card-title">
                                                <i class="material-icons left">card_membership</i>
                                                <?php echo htmlspecialchars($plan['name']); ?>
                                            </span>
                                            <div class="divider"></div>
                                            <div class="section">
                                                <h5 class="center-align">₱<?php echo number_format($plan['price'], 2); ?></h5>
                                                <p class="center-align grey-text"><?php echo $plan['duration']; ?> months</p>
                                            </div>
                                            <div class="section">
                                                <p><?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
                                            </div>
                                            <?php if ($plan['features']): ?>
                                            <div class="section">
                                                <ul class="collection">
                                                    <?php foreach (explode("\n", $plan['features']) as $feature): ?>
                                                        <li class="collection-item">
                                                            <i class="material-icons tiny green-text">check</i>
                                                            <?php echo htmlspecialchars($feature); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-action center-align">
                                            <?php if ($active_subscription): ?>
                                                <?php if ($active_subscription['plan_name'] == $plan['name']): ?>
                                                    <button class="btn disabled">Current Plan</button>
                                                <?php else: ?>
                                                    <a href="subscription.php?plan=<?php echo $plan['name']; ?>" class="btn waves-effect waves-light blue">
                                                        <i class="material-icons left">upgrade</i>Upgrade Plan
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="subscription.php?plan=<?php echo $plan['name']; ?>" class="btn waves-effect waves-light green">
                                                    <i class="material-icons left">add_circle</i>Subscribe Now
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.card .card-title {
    font-size: 20px;
    font-weight: 500;
}
.card .section {
    padding-top: 15px;
    padding-bottom: 15px;
}
.collection .collection-item {
    padding: 10px 20px;
}
.collection-item i {
    margin-right: 8px;
}
.status-badge {
    padding: 5px 10px;
    border-radius: 3px;
    color: white;
    font-size: 0.8rem;
    text-transform: uppercase;
}
.status-active { background-color: #4caf50; }
.status-expired { background-color: #f44336; }
.status-cancelled { background-color: #ff9800; }
</style>
