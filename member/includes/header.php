<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Check for active/pending subscription
$stmt = $db->prepare("
    SELECT 
        s.status as subscription_status,
        p.status as payment_status
    FROM subscriptions s
    LEFT JOIN payments p ON s.payment_id = p.id
    WHERE s.user_id = ? 
    AND (s.status = 'active' OR (s.status = 'pending' AND p.status = 'pending'))
    ORDER BY s.created_at DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'dashboard' => 'Dashboard',
    'subscription' => 'My Subscription',
    'payments' => 'Payment History',
    'workouts' => 'My Workouts',
    'schedule' => 'Class Schedule',
    'messages' => 'Messages',
    'profile' => 'My Profile',
    'plans' => 'Membership Plans'
];
$page_title = $page_titles[$current_page] ?? 'Member Dashboard';

// Define premium pages that require active subscription
$premium_pages = ['workouts', 'schedule'];

// Redirect to plans page if trying to access premium feature without active subscription
if (in_array($current_page, $premium_pages) && 
    (!$subscription || $subscription['subscription_status'] !== 'active')) {
    $_SESSION['message'] = "This feature requires an active subscription. Please subscribe to a plan to access this feature.";
    $_SESSION['message_type'] = "orange";
    header('Location: plans.php');
    exit();
}

// Clear one-time messages after displaying
if (isset($_SESSION['message_displayed'])) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    unset($_SESSION['message_displayed']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - VikingsFit</title>
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
        .sidenav {
            width: 300px;
        }
        .sidenav-header {
            padding: 20px 16px;
            background: #1565c0;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .sidenav-header img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }
        .sidenav-header h4 {
            margin: 0;
            font-size: 24px;
        }
        .sidenav-header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .brand-logo {
            display: flex !important;
            align-items: center;
            gap: 12px;
            padding-left: 20px !important;
            height: 64px;
        }
        .brand-logo img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }
        .brand-logo.left {
            left: 0;
            transform: none;
            display: flex !important;
            align-items: center;
            gap: 12px;
        }
        .brand-logo.left span {
            font-size: 1.5rem;
            font-weight: 500;
        }
        .page-title {
            position: absolute;
            width: 100%;
            text-align: center;
            left: 0;
            padding: 0 220px;
            font-size: 1.4rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media only screen and (max-width: 992px) {
            .brand-logo.left {
                left: 50%;
                transform: translateX(-50%);
            }
            .brand-logo img {
                height: 36px;
                width: 36px;
            }
            .page-title {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidenav -->
    <ul id="slide-out" class="sidenav sidenav-fixed">
        <li>
            <div class="sidenav-header">
                <img src="../images/logo.jpg" alt="VikingsFit Logo">
                <div>
                    <h4>VikingsFit</h4>
                    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
            </div>
        </li>
        <li class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a>
        </li>
        
        <li class="<?php echo $current_page === 'plans' ? 'active' : ''; ?>">
            <a href="plans.php"><i class="material-icons">stars</i>Membership Plans</a>
        </li>
    
        <li class="<?php echo $current_page === 'subscription' ? 'active' : ''; ?>">
            <a href="subscription.php"><i class="material-icons">card_membership</i>My Subscription</a>
        </li>
        <li class="<?php echo $current_page === 'payments' ? 'active' : ''; ?>">
            <a href="payments.php"><i class="material-icons">payment</i>Payment History</a>
        </li>

        <?php if ($subscription && $subscription['subscription_status'] === 'active'): ?>
            <li class="<?php echo $current_page === 'workouts' ? 'active' : ''; ?>">
                <a href="workouts.php"><i class="material-icons">fitness_center</i>My Workouts</a>
            </li>
            <li class="<?php echo $current_page === 'schedule' ? 'active' : ''; ?>">
                <a href="schedule.php"><i class="material-icons">schedule</i>Class Schedule</a>
            </li>
        <?php else: ?>
            <li class="premium-feature">
                <a class="disabled grey-text">
                    <i class="material-icons">fitness_center</i>My Workouts
                    <i class="material-icons right tiny">lock</i>
                </a>
            </li>
            <li class="premium-feature">
                <a class="disabled grey-text">
                    <i class="material-icons">schedule</i>Class Schedule
                    <i class="material-icons right tiny">lock</i>
                </a>
            </li>
        <?php endif; ?>

        <li>
            <div class="divider"></div>
        </li>
        <li class="<?php echo $current_page === 'messages' ? 'active' : ''; ?>">
            <a href="messages.php"><i class="material-icons">message</i>Messages</a>
        </li>
        <li class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>">
            <a href="profile.php"><i class="material-icons">person</i>My Profile</a>
        </li>
        <li>
            <div class="divider"></div>
        </li>
        <li>
            <a href="../logout.php" class="waves-effect">
                <i class="material-icons">exit_to_app</i>Logout
            </a>
        </li>
    </ul>

    <!-- Navbar -->
    <div class="navbar-fixed">
        <nav class="blue darken-3">
            <div class="nav-wrapper">
                <a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons">menu</i></a>
                <a href="#!" class="brand-logo left">
                    <img src="../images/logo.jpg" alt="VikingsFit Logo">
                    <span>VikingsFit</span>
                </a>
                <span class="page-title"><?php echo $page_title; ?></span>
                <ul class="right">
                    <li><a href="../logout.php"><i class="material-icons">exit_to_app</i></a></li>
                </ul>
            </div>
        </nav>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="dashboard-container">
            <div class="card-panel <?php echo $_SESSION['message_type']; ?> lighten-4">
                <?php 
                echo $_SESSION['message'];
                $_SESSION['message_displayed'] = true;
                ?>
            </div>
        </div>
    <?php endif; ?>
