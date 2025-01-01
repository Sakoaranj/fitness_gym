<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $recipient_id = $_POST['admin_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $db->prepare("
            INSERT INTO messages (sender_id, recipient_id, message)
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $recipient_id, $message])) {
            $success_message = 'Message sent successfully.';
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    M.toast({html: 'Message sent successfully!', classes: 'green'});
                });
                window.location.href = 'messages.php';
            </script>";
            exit;
        }
    }
    
    $error_message = 'Failed to send message.';
}

// Get all messages for the current user
$stmt = $db->prepare("
    SELECT m.*, 
           CASE 
               WHEN m.sender_id != ? THEN u.username
               ELSE 'You'
           END as sender_name,
           CASE 
               WHEN m.sender_id != ? THEN 'admin'
               ELSE 'member'
           END as sender_type
    FROM messages m
    LEFT JOIN users u ON m.sender_id = u.id
    WHERE m.sender_id = ? OR m.recipient_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of admins for the message form
$stmt = $db->prepare("SELECT id, username FROM users WHERE role = 'admin'");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <!-- New Message Form -->
            <div class="col s12 m4">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">send</i>
                            New Message
                        </span>
                        <form action="messages.php" method="POST">
                            <div class="input-field">
                                <select name="admin_id" required>
                                    <option value="" disabled selected>Choose an admin</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option value="<?php echo $admin['id']; ?>">
                                            <?php echo htmlspecialchars($admin['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Select Admin</label>
                            </div>
                            <div class="input-field">
                                <textarea name="message" class="materialize-textarea" required></textarea>
                                <label>Your Message</label>
                            </div>
                            <button type="submit" class="btn waves-effect waves-light blue">
                                Send Message
                                <i class="material-icons right">send</i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Message History -->
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">message</i>
                            Message History
                        </span>
                        <?php if ($messages): ?>
                            <ul class="collection">
                                <?php foreach ($messages as $message): ?>
                                    <li class="collection-item avatar">
                                        <?php if ($message['sender_type'] === 'admin'): ?>
                                            <i class="material-icons circle red">person</i>
                                            <span class="title"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                                        <?php else: ?>
                                            <i class="material-icons circle blue">person</i>
                                            <span class="title">You</span>
                                        <?php endif; ?>
                                        <p class="message-text">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                            <br>
                                            <small class="grey-text">
                                                <?php echo date('M d, Y g:i A', strtotime($message['created_at'])); ?>
                                            </small>
                                        </p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="center-align">No messages found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.collection .collection-item.avatar {
    min-height: 84px;
}
.message-text {
    margin-top: 5px !important;
    white-space: pre-wrap;
}
.title {
    font-weight: bold;
    color: #2196f3;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);
    
    var textareas = document.querySelectorAll('.materialize-textarea');
    M.textareaAutoResize(textareas);
});
</script>

<?php require_once 'includes/footer.php'; ?>
