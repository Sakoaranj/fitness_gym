<?php
session_start();
require_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get active plans
$plans = $db->query("
    SELECT * FROM plans 
    WHERE status = 'active' 
    ORDER BY price ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vikings Fitness</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            height: 64px;
            line-height: 64px;
        }
        main {
            padding-top: 64px; /* Height of the navbar */
        }
        @media only screen and (max-width: 600px) {
            main {
                padding-top: 56px; /* Height of the mobile navbar */
            }
        }
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hero.jpg');
            background-size: cover;
            background-position: center;
            height: 60vh;
            display: flex;
            align-items: center;
            color: white;
            margin-top: -64px;
        }
        .hero-content {
            max-width: 600px;
            text-align: left;
            padding: 0 20px;
        }
        @media only screen and (max-width: 600px) {
            .hero {
                margin-top: -56px;
            }
            .hero h1 {
                font-size: 2.5rem;
            }
            .hero p {
                font-size: 1.2rem;
            }
        }
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .hero p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .section-title {
            text-align: center;
            margin: 3rem 0;
        }
        .plan-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .plan-card .card-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding: 24px;
        }
        .plan-features {
            margin: 1.5rem 0;
            white-space: pre-line;
            flex-grow: 1;
        }
        .plan-price {
            font-size: 2.5rem;
            color: #1976d2;
            margin: 1.5rem 0;
            font-weight: 600;
        }
        .plan-duration {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        .plan-card .card-action {
            padding: 16px 24px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        .nav-wrapper {
            height: 100%;
            padding: 0 20px;
        }
        .brand-logo {
            display: inline-flex !important;
            align-items: center;
            height: 64px;
            padding: 7px 0 !important;
            font-size: 1.8rem !important;
        }
        .brand-logo img {
            height: 48px;
            width: 48px;
            margin-right: 12px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.8);
            vertical-align: middle;
        }
        @media only screen and (max-width: 992px) {
            .brand-logo {
                left: 50% !important;
                transform: translateX(-50%);
                padding: 7px 0 !important;
            }
            .brand-logo img {
                height: 40px;
                width: 40px;
                margin-right: 8px;
            }
            nav .brand-logo {
                font-size: 1.6rem !important;
            }
        }
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
            height: 64px;
        }
        .auth-buttons a.btn {
            position: relative;
            overflow: hidden;
            background-color: #2196F3;
            color: white;
            border-radius: 4px;
            text-transform: uppercase;
            padding: 0 24px;
            height: 40px;
            line-height: 36px;
            letter-spacing: 0.5px;
            font-weight: 500;
            font-size: 14px;
            border: 2px solid #2196F3;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
        }
        .auth-buttons a.btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease-out, height 0.6s ease-out;
        }
        .auth-buttons a.btn:hover::before {
            width: 300px;
            height: 300px;
        }
        .auth-buttons a.btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
        }
        .auth-buttons a.btn-outlined {
            background-color: transparent;
            color: #2196F3;
            border-color: currentColor;
        }
        .auth-buttons a.btn-outlined:hover {
            background-color: rgba(33, 150, 243, 0.1);
            border-color: #1976D2;
            color: #1976D2;
        }
        .auth-buttons a.btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
        }
        @media only screen and (max-width: 992px) {
            .auth-buttons {
                justify-content: center;
                padding: 10px 15px;
                margin: 0;
                height: auto;
                gap: 12px;
            }
            .sidenav .auth-buttons {
                flex-direction: column;
                padding: 20px 15px;
            }
            .sidenav .auth-buttons a.btn {
                width: 100%;
                text-align: center;
                height: 42px;
                line-height: 38px;
            }
        }
        /* Smooth scrolling for anchor links */
        html {
            scroll-behavior: smooth;
        }
        /* Offset anchor links to account for fixed header */
        :target::before {
            content: "";
            display: block;
            height: 64px;
            margin: -64px 0 0;
        }
        @media only screen and (max-width: 600px) {
            :target::before {
                height: 56px;
                margin: -56px 0 0;
            }
        }
        /* Footer styles */
        footer {
            padding: 40px 0;
            background-color: #263238;
            color: white;
        }
        footer h5 {
            color: white;
            font-weight: 500;
            margin-bottom: 20px;
        }
        footer p {
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
        }
        footer .social-links {
            margin-top: 20px;
        }
        footer .social-links a {
            color: white;
            font-size: 24px;
            margin-right: 15px;
            transition: color 0.3s ease;
        }
        footer .social-links a:hover {
            color: #2196F3;
        }
        footer .footer-copyright {
            background-color: rgba(0,0,0,0.1);
            padding: 15px 0;
            margin-top: 30px;
        }
    </style>
    <!-- Add Font Awesome for social media icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <div class="navbar-fixed">
        <nav class="blue-grey darken-3">
            <div class="nav-wrapper">
                <a href="#" class="brand-logo">
                    <img src="images/logo.jpg" alt="Vikings Fitness Logo">
                    Vikings Fitness
                </a>
                <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="material-icons">menu</i></a>
                <ul class="right hide-on-med-and-down">
                    <li><a href="#about">About</a></li>
                    <li><a href="#plans">Plans</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="admin/dashboard.php">Admin Panel</a></li>
                        <?php else: ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="auth-buttons">
                            <a href="login.php" class="btn btn-outlined">Login</a>
                            <a href="register.php" class="btn">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </div>

    <!-- Mobile Navigation -->
    <ul class="sidenav" id="mobile-nav">
        <li><a href="#about">About</a></li>
        <li><a href="#plans">Plans</a></li>
        <li><a href="#contact">Contact</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="admin/dashboard.php">Admin Panel</a></li>
            <?php else: ?>
                <li><a href="dashboard.php">Dashboard</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li class="auth-buttons" style="padding: 10px;">
                <a href="login.php" class="btn btn-outlined">Login</a>
                <a href="register.php" class="btn">Register</a>
            </li>
        <?php endif; ?>
    </ul>

    <main>
        <!-- Hero Section -->
        <div class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Transform Your Life</h1>
                    <p>Join Vikings Fitness and embark on your journey to a healthier, stronger you. Expert guidance, state-of-the-art facilities, and a supportive community await.</p>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn-large waves-effect waves-light blue">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <section id="about" class="section">
            <div class="container">
                <h2 class="section-title">Why Choose Vikings Fitness?</h2>
                <div class="row">
                    <div class="col s12 m4">
                        <div class="center">
                            <i class="material-icons large blue-text">fitness_center</i>
                            <h4>State-of-the-art Equipment</h4>
                            <p>Access to the latest fitness equipment and facilities to help you achieve your goals.</p>
                        </div>
                    </div>
                    <div class="col s12 m4">
                        <div class="center">
                            <i class="material-icons large blue-text">group</i>
                            <h4>Expert Trainers</h4>
                            <p>Our certified trainers are here to guide and motivate you throughout your fitness journey.</p>
                        </div>
                    </div>
                    <div class="col s12 m4">
                        <div class="center">
                            <i class="material-icons large blue-text">event</i>
                            <h4>Flexible Classes</h4>
                            <p>Choose from a variety of classes that fit your schedule and fitness level.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Plans Section -->
        <section id="plans" class="section grey lighten-4">
            <div class="container">
                <h2 class="section-title">Membership Plans</h2>
                <div class="row">
                    <?php foreach ($plans as $plan): ?>
                        <div class="col s12 m6 l4">
                            <div class="card plan-card">
                                <div class="card-content center-align">
                                    <span class="card-title"><?php echo htmlspecialchars($plan['name']); ?></span>
                                    <div class="plan-price">
                                        ₱<?php echo number_format($plan['price'], 2); ?>
                                    </div>
                                    <div class="plan-duration">
                                        <?php echo $plan['duration']; ?> month(s)
                                    </div>
                                    <p><?php echo htmlspecialchars($plan['description']); ?></p>
                                    <div class="plan-features">
                                        <?php echo nl2br(htmlspecialchars($plan['features'])); ?>
                                    </div>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'admin'): ?>
                                        <a href="subscribe.php?plan=<?php echo $plan['id']; ?>" class="btn-large waves-effect waves-light blue">Subscribe Now</a>
                                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                                        <a href="register.php" class="btn-large waves-effect waves-light blue">Register to Subscribe</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="section">
            <div class="container">
                <h2 class="section-title">Contact Us</h2>
                <div class="row">
                    <div class="col s12 m6">
                        <div class="card-panel">
                            <i class="material-icons medium blue-text">location_on</i>
                            <h5>Visit Us</h5>
                            <p>Cabilinan, Aurora, Philippines, 7020</p>
                        </div>
                    </div>
                    <div class="col s12 m6">
                        <div class="card-panel">
                            <i class="material-icons medium blue-text">phone</i>
                            <h5>Call Us</h5>
                            <p>Phone: (02) 8123-4567<br>Mobile: +63 912 345 6789</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="page-footer blue-grey darken-3">
            <div class="container">
                <div class="row">
                    <div class="col l6 s12">
                        <h5>Vikings Fitness</h5>
                        <p>Your journey to a healthier lifestyle starts here. Join our community and transform your life with expert guidance and state-of-the-art facilities.</p>
                        <div class="social-links">
                            <a href="https://www.facebook.com/profile.php?id=100093266450750" target="_blank" class="white-text">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col l4 offset-l2 s12">
                        <h5>Quick Links</h5>
                        <ul>
                            <li><a class="grey-text text-lighten-3" href="#about">About Us</a></li>
                            <li><a class="grey-text text-lighten-3" href="#plans">Our Plans</a></li>
                            <li><a class="grey-text text-lighten-3" href="#contact">Contact</a></li>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <li><a class="grey-text text-lighten-3" href="login.php">Login</a></li>
                                <li><a class="grey-text text-lighten-3" href="register.php">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-copyright">
                <div class="container">
                    &copy; <?php echo date('Y'); ?> Vikings Fitness. All rights reserved.
                </div>
            </div>
        </footer>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var elems = document.querySelectorAll('.sidenav');
                M.Sidenav.init(elems);
            });
        </script>
    </main>
</body>
</html>
