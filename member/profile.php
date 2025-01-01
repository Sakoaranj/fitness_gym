<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current subscription if any
$stmt = $db->prepare("
    SELECT * FROM subscriptions 
    WHERE user_id = ? AND status IN ('active', 'pending')
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$current_subscription = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Invalid email format';
        $_SESSION['message_type'] = 'red';
    } 
    // Check if email exists for other users
    else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['message'] = 'Email already exists';
            $_SESSION['message_type'] = 'red';
        }
        else {
            try {
                $stmt = $db->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $full_name, 
                    $email, 
                    $_SESSION['user_id']
                ]);

                $_SESSION['message'] = 'Profile updated successfully!';
                $_SESSION['message_type'] = 'green';
                header('Location: profile.php');
                exit;
            } catch (PDOException $e) {
                $_SESSION['message'] = 'Error updating profile';
                $_SESSION['message_type'] = 'red';
                error_log('Profile update error: ' . $e->getMessage());
            }
        }
    }
}
?>

<main>
    <div class="profile-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="card-panel <?php echo $_SESSION['message_type']; ?> lighten-4">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Subscription Status Card -->
            <div class="col s12 m4">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">card_membership</i>
                            Membership Status
                        </span>
                        <?php if ($current_subscription): ?>
                            <div class="membership-status">
                                <p class="plan-name"><?php echo htmlspecialchars($current_subscription['plan']); ?></p>
                                <div class="status-badges">
                                    <span class="new badge <?php 
                                        echo $current_subscription['status'] === 'active' ? 'green' : 'orange'; 
                                    ?>" data-badge-caption="">
                                        <?php echo ucfirst($current_subscription['status']); ?>
                                    </span>
                                </div>
                                <div class="subscription-info">
                                    <p>
                                        <i class="material-icons tiny">date_range</i>
                                        Valid until: <?php echo date('M d, Y', strtotime($current_subscription['end_date'])); ?>
                                    </p>
                                    <?php if ($current_subscription['status'] === 'pending'): ?>
                                        <p class="orange-text">
                                            <i class="material-icons tiny">info</i>
                                            Pending admin approval
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="subscription-actions">
                                    <a href="subscription.php" class="btn-small blue waves-effect waves-light">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="center-align grey-text">
                                <p>No active subscription</p>
                                <a href="plans.php" class="btn blue waves-effect waves-light">
                                    Subscribe Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">person</i>
                            Profile Information
                        </span>
                        <form method="POST" action="profile.php">
                            <div class="row">
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">account_circle</i>
                                    <input id="full_name" type="text" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    <label for="full_name">Full Name</label>
                                </div>
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">email</i>
                                    <input id="email" type="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <label for="email">Email</label>
                                </div>
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">person_outline</i>
                                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <label>Username (cannot be changed)</label>
                                </div>
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">event</i>
                                    <input type="text" value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" disabled>
                                    <label>Member Since</label>
                                </div>
                            </div>
                            <div class="card-action center-align">
                                <button type="submit" class="btn blue waves-effect waves-light">
                                    Update Profile
                                    <i class="material-icons right">save</i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.profile-container {
    padding: 20px;
}
.membership-status {
    margin-top: 20px;
}
.plan-name {
    font-size: 1.2rem;
    font-weight: bold;
    margin: 10px 0;
}
.status-badges {
    margin: 10px 0;
}
.subscription-info {
    margin: 15px 0;
}
.subscription-info p {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 5px 0;
}
.subscription-actions {
    margin-top: 20px;
    text-align: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.updateTextFields();
});
</script>

<?php require_once 'includes/footer.php'; ?>
