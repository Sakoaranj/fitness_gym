<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

$member_id = $_GET['id'] ?? 0;

// Handle subscription update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_subscription':
            $plan = $_POST['plan'] ?? '';
            $amount = $_POST['amount'] ?? 0;
            $duration_months = $_POST['duration_months'] ?? 1;
            
            if ($plan && $amount > 0) {
                try {
                    $db->beginTransaction();
                    
                    // Add subscription
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d', strtotime("+$duration_months months"));
                    
                    $stmt = $db->prepare("
                        INSERT INTO subscriptions (user_id, plan, amount, start_date, end_date, status)
                        VALUES (?, ?, ?, ?, ?, 'active')
                    ");
                    $stmt->execute([$member_id, $plan, $amount, $start_date, $end_date]);
                    
                    // Add payment record
                    $stmt = $db->prepare("
                        INSERT INTO payments (user_id, amount, payment_date, payment_method, status)
                        VALUES (?, ?, CURRENT_TIMESTAMP, 'cash', 'completed')
                    ");
                    $stmt->execute([$member_id, $amount]);
                    
                    $db->commit();
                    $success_message = "Subscription added successfully.";
                } catch (Exception $e) {
                    $db->rollBack();
                    $error_message = "Error adding subscription: " . $e->getMessage();
                }
            }
            break;
            
        case 'cancel_subscription':
            $subscription_id = $_POST['subscription_id'] ?? 0;
            if ($subscription_id) {
                $stmt = $db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$subscription_id, $member_id])) {
                    $success_message = "Subscription cancelled successfully.";
                } else {
                    $error_message = "Error cancelling subscription.";
                }
            }
            break;
    }
}

// Get member details
$stmt = $db->prepare("
    SELECT u.*, 
           s.id as subscription_id,
           s.plan as subscription_plan,
           s.start_date,
           s.end_date,
           s.status as subscription_status
    FROM users u
    LEFT JOIN subscriptions s ON u.id = s.user_id 
    AND s.status = 'active' 
    AND s.end_date >= CURRENT_DATE
    WHERE u.id = ? AND u.role = 'member'
");
$stmt->execute([$member_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: members.php');
    exit;
}

// Get payment history
$stmt = $db->prepare("
    SELECT * FROM payments 
    WHERE user_id = ? 
    ORDER BY payment_date DESC
");
$stmt->execute([$member_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get class booking history
$stmt = $db->prepare("
    SELECT cb.*, cs.schedule_date, cs.start_time, w.name as workout_name
    FROM class_bookings cb
    JOIN class_schedules cs ON cb.schedule_id = cs.id
    JOIN workouts w ON cs.workout_id = w.id
    WHERE cb.user_id = ?
    ORDER BY cs.schedule_date DESC, cs.start_time DESC
");
$stmt->execute([$member_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Details - Vikings Fitness</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        header, main, footer {
            padding-left: 300px;
        }
        @media only screen and (max-width : 992px) {
            header, main, footer {
                padding-left: 0;
            }
        }
        .dashboard-container { padding: 20px; }
        .sidenav-header {
            padding: 20px 15px;
            background: #1a237e;
            margin-bottom: 0;
        }
        .sidenav-header h4 {
            color: white;
            margin: 0;
            font-size: 1.8rem;
        }
        .user-view {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .member-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .member-info p {
            margin: 10px 0;
            font-size: 1.1rem;
        }
        .member-info strong {
            display: inline-block;
            width: 150px;
            color: #666;
        }
        .status-badge {
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 500;
            margin-left: 10px;
        }
        .status-active { background-color: #4caf50; }
        .status-inactive { background-color: #f44336; }
        .status-completed { background-color: #2196f3; }
        .status-pending { background-color: #ff9800; }
        .status-cancelled { background-color: #9e9e9e; }
        
        h5 {
            color: #26a69a;
            font-weight: 500;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #26a69a;
        }
        .table-container {
            margin: 20px 0;
            overflow-x: auto;
        }
        table.striped > tbody > tr:nth-child(odd) {
            background-color: rgba(242, 242, 242, 0.5);
        }
        .btn {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidenav -->
    <ul id="slide-out" class="sidenav sidenav-fixed">
        <li>
            <div class="sidenav-header">
                <h4>Vikings Fitness</h4>
            </div>
        </li>
        <li>
            <div class="user-view">
                <span class="white-text name">Admin Panel</span>
            </div>
        </li>
        <li><a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
        <li class="active"><a href="members.php"><i class="material-icons">group</i>Members</a></li>
        <li><a href="workouts.php"><i class="material-icons">fitness_center</i>Workouts</a></li>
        <li><a href="schedules.php"><i class="material-icons">event</i>Schedules</a></li>
        <li><div class="divider"></div></li>
        <li><a href="../index.php"><i class="material-icons">public</i>View Site</a></li>
        <li><a href="../logout.php"><i class="material-icons">exit_to_app</i>Logout</a></li>
    </ul>

    <!-- Top Navigation -->
    <nav class="blue-grey darken-3">
        <div class="nav-wrapper">
            <a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons">menu</i></a>
            <span class="brand-logo center">Member Details</span>
        </div>
    </nav>

    <main>
        <div class="dashboard-container">
            <?php if ($success_message): ?>
                <div class="card-panel green white-text"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="card-panel red white-text"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Member Profile -->
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <div class="row">
                                <div class="col s12">
                                    <h5>Member Information</h5>
                                    <div class="member-info">
                                        <p><strong>Username:</strong> <?php echo htmlspecialchars($member['username']); ?></p>
                                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($member['full_name']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
                                        <p><strong>Join Date:</strong> <?php echo date('F j, Y', strtotime($member['created_at'])); ?></p>
                                        <p>
                                            <strong>Subscription Status:</strong> 
                                            <span class="status-badge status-<?php echo strtolower($member['subscription_status'] ?? 'inactive'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($member['subscription_status'] ?? 'Inactive')); ?>
                                            </span>
                                        </p>
                                        <?php if ($member['subscription_status'] === 'active'): ?>
                                            <p><strong>Current Plan:</strong> <?php echo htmlspecialchars($member['subscription_plan']); ?></p>
                                            <p><strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($member['start_date'])); ?></p>
                                            <p><strong>End Date:</strong> <?php echo date('F j, Y', strtotime($member['end_date'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment History -->
                            <?php if (!empty($payments)): ?>
                            <div class="row">
                                <div class="col s12">
                                    <h5>Payment History</h5>
                                    <div class="table-container">
                                        <table class="striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($payment['payment_date'])); ?></td>
                                                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></td>
                                                        <td>
                                                            <span class="status-badge status-<?php echo strtolower($payment['status']); ?>">
                                                                <?php echo htmlspecialchars(ucfirst($payment['status'])); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Class Bookings -->
                            <?php if (!empty($bookings)): ?>
                            <div class="row">
                                <div class="col s12">
                                    <h5>Class Booking History</h5>
                                    <div class="table-container">
                                        <table class="striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Workout</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bookings as $booking): ?>
                                                    <tr>
                                                        <td><?php echo date('M j, Y', strtotime($booking['schedule_date'])); ?></td>
                                                        <td><?php echo date('g:i A', strtotime($booking['start_time'])); ?></td>
                                                        <td><?php echo htmlspecialchars($booking['workout_name']); ?></td>
                                                        <td>
                                                            <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                                                <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col s12">
                                    <a href="members.php" class="btn waves-effect waves-light grey">
                                        <i class="material-icons left">arrow_back</i>
                                        Back to Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('.sidenav');
            M.Sidenav.init(elems);
            
            var tabs = document.querySelectorAll('.tabs');
            M.Tabs.init(tabs);
            
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);
        });
    </script>
</body>
</html>
