<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Financial Summary
$financial_query = "
    SELECT 
        SUM(amount) as total_revenue,
        COUNT(*) as total_transactions,
        AVG(amount) as average_transaction,
        payment_method,
        DATE(payment_date) as payment_day
    FROM payments 
    WHERE payment_date BETWEEN ? AND ?
    AND status = 'verified'
    GROUP BY payment_method, payment_day
    ORDER BY payment_day DESC
";
$stmt = $db->prepare($financial_query);
$stmt->execute([$start_date, $end_date]);
$financial_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overall Membership Stats
$overall_stats_query = "
    SELECT 
        COUNT(DISTINCT s.id) as total_subscriptions,
        COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.id END) as active_subscriptions,
        COUNT(DISTINCT s.user_id) as total_members,
        COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.user_id END) as active_members
    FROM subscriptions s
";
$overall_stats = $db->query($overall_stats_query)->fetch(PDO::FETCH_ASSOC);

// Membership Stats by Plan
$membership_query = "
    SELECT 
        p.name as plan_name,
        COUNT(DISTINCT s.id) as total_subscriptions,
        COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.id END) as active_subscriptions,
        COUNT(DISTINCT s.user_id) as total_members,
        COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.user_id END) as active_members,
        GROUP_CONCAT(DISTINCT CASE WHEN s.status = 'active' THEN u.username END) as active_subscribers
    FROM plans p
    LEFT JOIN subscriptions s ON s.plan = p.name
    LEFT JOIN users u ON s.user_id = u.id
    GROUP BY p.name
    ORDER BY active_subscriptions DESC
";
$stmt = $db->prepare($membership_query);
$stmt->execute();
$membership_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug information
echo "<!-- Membership Statistics Debug:\n";
echo "Overall Stats:\n";
echo json_encode($overall_stats, JSON_PRETTY_PRINT) . "\n";
echo "\nPlan Stats:\n";
foreach ($membership_data as $data) {
    echo "Plan: {$data['plan_name']}\n";
    echo "Active Subscriptions: {$data['active_subscriptions']}\n";
    echo "Active Members: {$data['active_members']}\n";
    echo "Active Subscribers: {$data['active_subscribers']}\n";
    echo "---\n";
}
echo "-->\n";

// Workout Popularity
$workout_query = "
    SELECT 
        w.name as workout_name,
        COUNT(cb.id) as total_bookings,
        w.difficulty_level,
        COUNT(DISTINCT cb.user_id) as unique_members
    FROM workouts w
    LEFT JOIN class_schedules cs ON w.id = cs.workout_id
    LEFT JOIN class_bookings cb ON cs.id = cb.schedule_id
    WHERE cs.schedule_date BETWEEN ? AND ?
    GROUP BY w.id
    ORDER BY total_bookings DESC
";
$stmt = $db->prepare($workout_query);
$stmt->execute([$start_date, $end_date]);
$workout_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="dashboard-container">
        <!-- Date Range Filter -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Report Filters</span>
                        <form method="GET" class="row">
                            <div class="input-field col s5">
                                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                <label for="start_date">Start Date</label>
                            </div>
                            <div class="input-field col s5">
                                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                <label for="end_date">End Date</label>
                            </div>
                            <div class="input-field col s2">
                                <button class="btn waves-effect waves-light" type="submit">
                                    Filter
                                    <i class="material-icons right">filter_list</i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Membership Stats -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Overall Membership Statistics</span>
                        <div class="row">
                            <div class="col s3 center-align">
                                <h3><?php echo $overall_stats['total_members']; ?></h3>
                                <p>Total Members</p>
                            </div>
                            <div class="col s3 center-align">
                                <h3><?php echo $overall_stats['active_members']; ?></h3>
                                <p>Active Members</p>
                            </div>
                            <div class="col s3 center-align">
                                <h3><?php echo $overall_stats['total_subscriptions']; ?></h3>
                                <p>Total Subscriptions</p>
                            </div>
                            <div class="col s3 center-align">
                                <h3><?php echo $overall_stats['active_subscriptions']; ?></h3>
                                <p>Active Subscriptions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Financial Summary</span>
                        <div class="row">
                            <div class="col s12">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col s12">
                                <table class="striped">
                                    <thead>
                                        <tr>
                                            <th>Payment Method</th>
                                            <th>Total Revenue</th>
                                            <th>Transactions</th>
                                            <th>Average Transaction</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $payment_methods = [];
                                        foreach ($financial_data as $data) {
                                            if (!isset($payment_methods[$data['payment_method']])) {
                                                $payment_methods[$data['payment_method']] = [
                                                    'total' => 0,
                                                    'count' => 0,
                                                ];
                                            }
                                            $payment_methods[$data['payment_method']]['total'] += $data['total_revenue'];
                                            $payment_methods[$data['payment_method']]['count'] += $data['total_transactions'];
                                        }
                                        foreach ($payment_methods as $method => $stats):
                                        ?>
                                        <tr>
                                            <td><?php echo ucfirst($method); ?></td>
                                            <td>₱<?php echo number_format($stats['total'], 2); ?></td>
                                            <td><?php echo $stats['count']; ?></td>
                                            <td>₱<?php echo number_format($stats['total'] / $stats['count'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Membership Stats -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Membership Statistics by Plan</span>
                        <div class="row">
                            <div class="col s12">
                                <table class="striped">
                                    <thead>
                                        <tr>
                                            <th>Plan</th>
                                            <th>Total Members</th>
                                            <th>Active Members</th>
                                            <th>Total Subscriptions</th>
                                            <th>Active Subscriptions</th>
                                            <th>Active Subscribers</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($membership_data as $data): ?>
                                        <tr>
                                            <td><?php echo $data['plan_name']; ?></td>
                                            <td><?php echo $data['total_members']; ?></td>
                                            <td><?php echo $data['active_members']; ?></td>
                                            <td><?php echo $data['total_subscriptions']; ?></td>
                                            <td>
                                                <span class="<?php echo $data['active_subscriptions'] > 0 ? 'orange-text' : 'grey-text'; ?>">
                                                    <?php echo $data['active_subscriptions']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($data['active_subscribers']): ?>
                                                    <?php echo str_replace(',', ', ', $data['active_subscribers']); ?>
                                                <?php else: ?>
                                                    <span class="grey-text">None</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col s12">
                                <canvas id="membershipChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workout Popularity -->
        <div class="row">
            <div class="col s12 m6">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Workout Popularity</span>
                        <canvas id="workoutChart"></canvas>
                        <table class="striped">
                            <thead>
                                <tr>
                                    <th>Workout</th>
                                    <th>Bookings</th>
                                    <th>Unique Members</th>
                                    <th>Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workout_data as $data): ?>
                                <tr>
                                    <td><?php echo $data['workout_name']; ?></td>
                                    <td><?php echo $data['total_bookings']; ?></td>
                                    <td><?php echo $data['unique_members']; ?></td>
                                    <td><?php echo ucfirst($data['difficulty_level']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($financial_data, 'payment_day')); ?>,
            datasets: [{
                label: 'Daily Revenue',
                data: <?php echo json_encode(array_column($financial_data, 'total_revenue')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Membership Chart
    const membershipCtx = document.getElementById('membershipChart').getContext('2d');
    new Chart(membershipCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($membership_data, 'plan_name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($membership_data, 'total_subscriptions')); ?>,
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)'
                ]
            }]
        },
        options: {
            responsive: true
        }
    });

    // Workout Chart
    const workoutCtx = document.getElementById('workoutChart').getContext('2d');
    new Chart(workoutCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($workout_data, 'workout_name')); ?>,
            datasets: [{
                label: 'Total Bookings',
                data: <?php echo json_encode(array_column($workout_data, 'total_bookings')); ?>,
                backgroundColor: 'rgb(75, 192, 192)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<style>
.card h3 {
    margin: 0;
    font-size: 2rem;
    color: #26a69a;
}

.card p {
    margin: 5px 0 0;
    color: #666;
}

table.striped tbody tr:nth-child(odd) {
    background-color: rgba(242, 242, 242, 0.5);
}
</style>

<?php require_once 'includes/footer.php'; ?>
