<?php
session_start();
require_once 'config/Database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email']) || empty($_POST['full_name'])) {
        $error = 'All fields are required';
    } else {
        try {
            // Check if username exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$_POST['username']]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username already exists';
            }
            // Check if email exists
            else {
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$_POST['email']]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email already exists';
                }
                else {
                    if ($user->register($_POST)) {
                        $_SESSION['register_success'] = true;
                        header("Location: login.php");
                        exit;
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Vikings Fitness</title>
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
        .register-container {
            width: 100%;
            max-width: 450px;
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
            border-bottom: 2px solid #1a237e !important;
        }
        .input-field .prefix {
            color: #1a237e;
            font-size: 1.5rem;
            top: 0.5rem;
        }
        .input-field .prefix.active {
            color: #1a237e;
        }
        .input-field label {
            font-size: 1rem;
            transform: translateY(-14px) scale(0.8);
            color: #9e9e9e;
        }
        .input-field input:focus + label {
            color: #1a237e !important;
        }
        .btn {
            width: 100%;
            height: 48px;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            margin-top: 20px;
            background-color: #1a237e;
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.3);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background-color: #0d0d8b;
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.4);
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
            color: #1a237e !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .card-action a:hover {
            color: #0d0d8b !important;
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
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        .success-message i {
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
    <div class="register-container">
        <div class="card">
            <div class="card-content">
                <h4 class="card-title center-align">Create Account</h4>
                <?php if ($error): ?>
                    <div class="error-message center-align">
                        <i class="material-icons">error_outline</i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message center-align">
                        <i class="material-icons">check_circle</i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="register.php">
                    <div class="input-field">
                        <i class="material-icons prefix">person_outline</i>
                        <input id="username" type="text" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="input-field">
                        <i class="material-icons prefix">email</i>
                        <input id="email" type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <label for="email">Email</label>
                    </div>
                    <div class="input-field">
                        <i class="material-icons prefix">badge</i>
                        <input id="full_name" type="text" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                        <label for="full_name">Full Name</label>
                    </div>
                    <div class="input-field">
                        <i class="material-icons prefix">lock_outline</i>
                        <input id="password" type="password" name="password" required>
                        <label for="password">Password</label>
                    </div>
                    <button class="btn waves-effect waves-light" type="submit">
                        Sign Up
                        <i class="material-icons right">person_add</i>
                    </button>
                </form>
            </div>
            <div class="card-action center-align">
                <p>Already have an account? <a href="login.php">Sign In</a></p>
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
            
            // Show toast for any errors
            <?php if ($error): ?>
            M.toast({
                html: '<i class="material-icons left">error_outline</i><?php echo addslashes($error); ?>',
                classes: 'red',
                displayLength: 4000
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
