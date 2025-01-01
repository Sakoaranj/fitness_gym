<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/Database.php';
require_once 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

$member_id = $_GET['id'] ?? 0;

// Get member details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
$stmt->execute([$member_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: members.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    try {
        // Check if username exists (excluding current user)
        if ($username !== $member['username']) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $member_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Username already exists');
            }
        }

        // Check if email exists (excluding current user)
        if ($email !== $member['email']) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $member_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Email already exists');
            }
        }

        // Start transaction
        $db->beginTransaction();

        // Update user details
        $sql = "UPDATE users SET username = ?, email = ?, full_name = ? WHERE id = ?";
        $params = [$username, $email, $full_name, $member_id];

        // Add password to update if provided
        if (!empty($new_password)) {
            $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, password = ? WHERE id = ?";
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $params = [$username, $email, $full_name, $hashed_password, $member_id];
        }

        $stmt = $db->prepare($sql);
        if ($stmt->execute($params)) {
            $db->commit();
            $success_message = "Member updated successfully.";
            
            // Refresh member data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            throw new Exception('Failed to update member');
        }
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<main>
    <div class="dashboard-container">
        <?php if ($success_message): ?>
            <div class="card-panel green lighten-4">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="card-panel red lighten-4">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">edit</i>
                            Edit Member
                        </span>

                        <form method="POST" class="row">
                            <div class="input-field col s12 m6">
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($member['username']); ?>" required>
                                <label for="username">Username</label>
                            </div>

                            <div class="input-field col s12 m6">
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                                <label for="email">Email</label>
                            </div>

                            <div class="input-field col s12">
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
                                <label for="full_name">Full Name</label>
                            </div>

                            <div class="input-field col s12">
                                <input type="password" id="new_password" name="new_password">
                                <label for="new_password">New Password (leave blank to keep current)</label>
                            </div>

                            <div class="col s12">
                                <button type="submit" class="btn waves-effect waves-light">
                                    <i class="material-icons left">save</i>
                                    Save Changes
                                </button>
                                <a href="members.php" class="btn waves-effect waves-light grey">
                                    <i class="material-icons left">arrow_back</i>
                                    Back to Members
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    .dashboard-container {
        padding: 20px;
    }
    .card-title {
        font-size: 1.5rem;
        font-weight: 400;
    }
    .btn {
        margin-right: 10px;
    }
    .input-field label {
        color: #666;
    }
    .input-field input[type=text]:focus + label,
    .input-field input[type=email]:focus + label,
    .input-field input[type=password]:focus + label {
        color: #26a69a;
    }
    .input-field input[type=text]:focus,
    .input-field input[type=email]:focus,
    .input-field input[type=password]:focus {
        border-bottom: 1px solid #26a69a;
        box-shadow: 0 1px 0 0 #26a69a;
    }
</style>

<?php require_once 'includes/footer.php'; ?>
