<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get total active members count (members with active subscriptions)
$active_members_query = "
    SELECT COUNT(DISTINCT u.id) as count 
    FROM users u
    JOIN subscriptions s ON u.id = s.user_id
    WHERE u.role = 'member' 
    AND s.status = 'active'
";
$active_members_count = $db->query($active_members_query)->fetch(PDO::FETCH_ASSOC)['count'];

// Get active subscriptions count
$active_subscriptions_query = "
    SELECT COUNT(*) as count 
    FROM subscriptions s
    WHERE s.status = 'active'
";
$active_subscriptions = $db->query($active_subscriptions_query)->fetch(PDO::FETCH_ASSOC)['count'];

// Get total revenue from verified payments
$total_revenue_query = "
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE status = 'verified'
";
$total_revenue = $db->query($total_revenue_query)->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent payments with more details
$recent_payments_query = "
    SELECT 
        p.*, 
        u.full_name, 
        s.plan as plan_name,
        s.status as subscription_status
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN subscriptions s ON s.user_id = p.user_id AND s.status = 'active'
    ORDER BY p.created_at DESC 
    LIMIT 5
";
$recent_payments = $db->query($recent_payments_query)->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming classes
$upcoming_classes = $db->query("
    SELECT cs.*, w.name as workout_name,
           (SELECT COUNT(*) FROM class_bookings WHERE schedule_id = cs.id AND status = 'booked') as booked_count
    FROM class_schedules cs
    JOIN workouts w ON cs.workout_id = w.id
    WHERE cs.schedule_date >= CURRENT_DATE AND cs.status = 'scheduled'
    ORDER BY cs.schedule_date ASC, cs.start_time ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get expiring subscriptions
$expiring_subscriptions = $db->query("
    SELECT s.*, u.full_name, u.email
    FROM subscriptions s
    JOIN users u ON s.user_id = u.id
    WHERE s.status = 'active'
    ORDER BY s.end_date ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Debug information
echo "<!-- Debug Information\n";
echo "Active Members: " . $active_members_count . "\n";
echo "Active Subscriptions: " . $active_subscriptions . "\n";
echo "Total Revenue: " . $total_revenue . "\n";
echo "-->";

?>

<main>
    <div class="dashboard-container">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col s12 m4">
                <div class="card blue darken-1">
                    <div class="card-content white-text">
                        <span class="card-title">Active Members</span>
                        <h3><?php echo $active_members_count; ?></h3>
                        <p class="small">Members with active subscriptions</p>
                    </div>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="card green darken-1">
                    <div class="card-content white-text">
                        <span class="card-title">Active Subscriptions</span>
                        <h3><?php echo $active_subscriptions; ?></h3>
                        <p class="small">Current active memberships</p>
                    </div>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="card orange darken-1">
                    <div class="card-content white-text">
                        <span class="card-title">Total Revenue</span>
                        <h3>₱<?php echo number_format($total_revenue, 2); ?></h3>
                        <p class="small">From verified payments</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Payments -->
            <div class="col s12 m6">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Recent Payments</span>
                        <table class="striped">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($payment['full_name']); ?>
                                            <br>
                                            <small class="grey-text"><?php echo htmlspecialchars($payment['plan_name'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                        <td>
                                            <span class="new badge <?php 
                                                echo $payment['status'] === 'verified' ? 'green' : 
                                                    ($payment['status'] === 'pending' ? 'orange' : 'grey'); 
                                            ?>" data-badge-caption="">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-action">
                        <a href="payments.php" class="blue-text">View All Payments</a>
                    </div>
                </div>
            </div>

            <!-- Upcoming Classes -->
            <div class="col s12 m6">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Upcoming Classes</span>
                        <table class="striped">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Bookings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_classes as $class): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($class['workout_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($class['schedule_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($class['start_time'])); ?></td>
                                        <td><?php echo $class['booked_count']; ?>/<?php echo $class['max_participants']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-action">
                        <a href="schedules.php" class="blue-text">View All Classes</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expiring Subscriptions -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Expiring Subscriptions</span>
                        <table class="striped">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>End Date</th>
                                    <th>Days Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiring_subscriptions as $subscription): 
                                    $days_left = ceil((strtotime($subscription['end_date']) - time()) / (60 * 60 * 24));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subscription['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($subscription['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></td>
                                        <td>
                                            <span class="new badge <?php echo $days_left <= 3 ? 'red' : 'orange'; ?>" data-badge-caption="days">
                                                <?php echo $days_left; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-action">
                        <a href="subscriptions.php" class="blue-text">View All Subscriptions</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.card .small {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-top: 5px;
}
.card h3 {
    margin: 15px 0 5px 0;
    font-size: 2.5rem;
}
.badge {
    float: none;
    margin-left: 0;
}
</style>

<?php require_once 'includes/footer.php'; ?>
