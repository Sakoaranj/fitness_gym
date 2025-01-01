<?php
session_start();
require_once 'config/Database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error = '';
$register_success = false;

if (isset($_SESSION['register_success'])) {
    $register_success = true;
    unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($user->login($username, $password)) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['role'] = $user->role;
        
        header("Location: " . ($user->role === 'admin' ? 'admin/dashboard.php' : 'member/dashboard.php'));
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vikings Fitness</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hero.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
        }
        .card .card-content {
            padding: 40px 30px 30px;
        }
        .card-title {
            font-size: 1.8rem !important;
            font-weight: 600 !important;
            margin-bottom: 30px !important;
            color: #1a237e;
        }
        .input-field {
            margin-top: 25px;
        }
        .input-field input {
            border-bottom: 2px solid #e0e0e0 !important;
            box-shadow: none !important;
            font-size: 1rem;
        }
        .input-field input:focus {
            border-bottom: 2px solid #1976d2 !important;
        }
        .input-field .prefix {
            color: #1976d2;
            font-size: 1.5rem;
            top: 0.5rem;
        }
        .input-field .prefix.active {
            color: #1976d2;
        }
        .input-field label {
            font-size: 1rem;
            transform: translateY(-14px) scale(0.8);
            color: #9e9e9e;
        }
        .input-field input:focus + label {
            color: #1976d2 !important;
        }
        .btn {
            width: 100%;
            height: 48px;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            margin-top: 20px;
            background-color: #1976d2;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.3);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background-color: #1565c0;
            box-shadow: 0 8px 20px rgba(25, 118, 210, 0.4);
            transform: translateY(-2px);
        }
        .btn i {
            margin-left: 8px;
        }
        .card-action {
            background-color: #f5f5f5;
            border-top: none !important;
            padding: 20px 30px !important;
        }
        .card-action p {
            margin: 0 0 10px 0;
            color: #757575;
        }
        .card-action a {
            color: #1976d2 !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .card-action a:hover {
            color: #1565c0 !important;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        .error-message i {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        @media (max-width: 600px) {
            .card-content {
                padding: 30px 20px 20px !important;
            }
            .card-action {
                padding: 15px 20px !important;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-content">
                <h4 class="card-title center-align">Welcome Back</h4>
                <?php if ($error): ?>
                    <div class="error-message center-align">
                        <i class="material-icons">error_outline</i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <div class="input-field">
                        <i class="material-icons prefix">person_outline</i>
                        <input id="username" type="text" name="username" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="input-field">
                        <i class="material-icons prefix">lock_outline</i>
                        <input id="password" type="password" name="password" required>
                        <label for="password">Password</label>
                    </div>
                    <button class="btn waves-effect waves-light" type="submit">
                        Sign In
                        <i class="material-icons right">arrow_forward</i>
                    </button>
                </form>
            </div>
            <div class="card-action center-align">
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
                <a href="index.php">Back to Home</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Materialize components
            M.updateTextFields();
            
            // Show error toast if there's an error
            <?php if ($error): ?>
            M.toast({
                html: '<i class="material-icons left">error_outline</i><?php echo addslashes($error); ?>',
                classes: 'red',
                displayLength: 4000
            });
            <?php endif; ?>

            // Show success toast if registration was successful
            <?php if ($register_success): ?>
            M.toast({
                html: '<i class="material-icons left">check_circle</i>Registration successful! Please login.',
                classes: 'green',
                displayLength: 4000
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
