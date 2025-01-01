<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'dashboard' => 'Dashboard',
    'members' => 'Members Management',
    'plans' => 'Plans Management',
    'payments' => 'Payments Management',
    'subscriptions' => 'Subscriptions Management',
    'workouts' => 'Workouts Management',
    'schedules' => 'Schedules Management',
    'reports' => 'Reports & Analytics',
    'communications' => 'Member Communications',
    'verify_subscriptions' => 'Verify Subscriptions'
];
$page_title = $page_titles[$current_page] ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Vikings Fitness</title>
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
        .dashboard-container { 
            padding: 20px; 
        }
        .sidenav {
            position: fixed;
            top: 0;
            width: 300px;
            height: 100%;
            background-color: #fff;
            overflow-y: auto;
            z-index: 999;
        }
        .sidenav-header {
            padding: 20px 15px;
            background: #1a237e;
            margin-bottom: 0;
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
            color: white;
            margin: 0;
            font-size: 1.8rem;
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
        .success-message {
            animation: fadeOut 2s forwards;
            animation-delay: 1.5s;
        }
        .error-message {
            animation: fadeOut 2s forwards;
            animation-delay: 1.5s;
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>
<body>
    <!-- Sidenav -->
    <ul id="slide-out" class="sidenav sidenav-fixed">
        <li>
            <div class="sidenav-header">
                <img src="../images/logo.jpg" alt="VikingsFit Logo">
                <h4>VikingsFit</h4>
            </div>
        </li>
        <li class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a>
        </li>
        <li class="<?php echo $current_page === 'members' ? 'active' : ''; ?>">
            <a href="members.php"><i class="material-icons">people</i>Members</a>
        </li>
        <li class="<?php echo $current_page === 'plans' ? 'active' : ''; ?>">
            <a href="plans.php"><i class="material-icons">card_membership</i>Plans</a>
        </li>
        <li class="<?php echo $current_page === 'subscriptions' ? 'active' : ''; ?>">
            <a href="subscriptions.php"><i class="material-icons">subscriptions</i>Subscriptions</a>
        </li>
        <li class="<?php echo $current_page === 'payments' ? 'active' : ''; ?>">
            <a href="payments.php"><i class="material-icons">payment</i>Payments</a>
        </li>
        <li class="<?php echo $current_page === 'workouts' ? 'active' : ''; ?>">
            <a href="workouts.php"><i class="material-icons">fitness_center</i>Workouts</a>
        </li>
        <li class="<?php echo $current_page === 'schedules' ? 'active' : ''; ?>">
            <a href="schedules.php"><i class="material-icons">schedule</i>Schedules</a>
        </li>
        <li>
            <div class="divider"></div>
        </li>
        <li class="<?php echo $current_page === 'reports' ? 'active' : ''; ?>">
            <a href="reports.php"><i class="material-icons">analytics</i>Reports</a>
        </li>
        <li class="<?php echo $current_page === 'communications' ? 'active' : ''; ?>">
            <a href="communications.php"><i class="material-icons">message</i>Communications</a>
        </li>
        <li>
            <div class="divider"></div>
        </li>
        <li>
            <a href="../logout.php"><i class="material-icons">exit_to_app</i>Logout</a>
        </li>
    </ul>

    <!-- Navbar -->
    <div class="navbar-fixed">
        <nav class="blue-grey darken-3">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="../assets/js/message-handler.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('.sidenav');
            M.Sidenav.init(elems);
            
            // Highlight current page in sidenav
            var currentPage = '<?php echo $current_page; ?>';
            document.querySelectorAll('.sidenav a').forEach(function(link) {
                if (link.getAttribute('href') === currentPage + '.php') {
                    link.parentElement.classList.add('active');
                }
            });
        });
    </script>
