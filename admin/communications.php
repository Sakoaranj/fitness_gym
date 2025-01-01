<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_message':
            $recipient_type = $_POST['recipient_type'] ?? '';
            $recipient_ids = $_POST['recipient_ids'] ?? [];
            $subject = $_POST['subject'] ?? '';
            $message = $_POST['message'] ?? '';
            
            if ($subject && $message) {
                try {
                    $db->beginTransaction();
                    
                    // Get recipients based on type
                    $recipient_query = "SELECT id, email, full_name FROM users WHERE role = 'member'";
                    if ($recipient_type === 'specific') {
                        $recipient_query .= " AND id IN (" . implode(',', array_map('intval', $recipient_ids)) . ")";
                    } elseif ($recipient_type === 'expiring') {
                        $recipient_query = "
                            SELECT DISTINCT u.id, u.email, u.full_name 
                            FROM users u
                            JOIN subscriptions s ON u.id = s.user_id
                            WHERE s.status = 'active' 
                            AND s.end_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
                        ";
                    }
                    
                    $stmt = $db->prepare($recipient_query);
                    $stmt->execute();
                    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Insert messages
                    $message_query = "
                        INSERT INTO messages (sender_id, recipient_id, subject, message, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ";
                    $stmt = $db->prepare($message_query);
                    
                    foreach ($recipients as $recipient) {
                        $stmt->execute([$_SESSION['user_id'], $recipient['id'], $subject, $message]);
                    }
                    
                    $db->commit();
                    $success_message = "Message sent successfully to " . count($recipients) . " recipients.";
                } catch (Exception $e) {
                    $db->rollBack();
                    $error_message = "Error sending message: " . $e->getMessage();
                }
            } else {
                $error_message = "Subject and message are required.";
            }
            break;
    }
}

// Get all members for recipient selection
$stmt = $db->query("SELECT id, full_name, email FROM users WHERE role = 'member' ORDER BY full_name");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get conversations with replies
$stmt = $db->prepare("
    SELECT 
        m.id,
        m.subject,
        m.message,
        m.created_at,
        m.sender_id,
        m.recipient_id,
        s.full_name as sender_name,
        r.full_name as recipient_name,
        r.email as recipient_email
    FROM messages m
    JOIN users s ON m.sender_id = s.id
    JOIN users r ON m.recipient_id = r.id
    WHERE m.sender_id = ? OR m.recipient_id = ?
    ORDER BY m.created_at DESC
    LIMIT 50
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="container-fluid" style="padding: 20px;">
        <?php if ($success_message): ?>
            <div class="card-panel green white-text success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="card-panel red white-text error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- New Message Form -->
            <div class="col s12 m4">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">mail</i>
                            Send Message
                        </span>
                        <form method="POST">
                            <input type="hidden" name="action" value="send_message">
                            
                            <div class="input-field">
                                <select name="recipient_type" id="recipient_type" required>
                                    <option value="" disabled selected>Choose recipient type</option>
                                    <option value="all">All Members</option>
                                    <option value="expiring">Expiring Subscriptions</option>
                                    <option value="specific">Specific Members</option>
                                </select>
                                <label>Recipients</label>
                            </div>
                            
                            <div id="specific_members" class="input-field" style="display: none;">
                                <select name="recipient_ids[]" multiple>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?php echo $member['id']; ?>">
                                            <?php echo $member['full_name'] . ' (' . $member['email'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Select Members</label>
                            </div>
                            
                            <div class="input-field">
                                <input type="text" id="subject" name="subject" required>
                                <label for="subject">Subject</label>
                            </div>
                            
                            <div class="input-field">
                                <textarea id="message" name="message" class="materialize-textarea" required></textarea>
                                <label for="message">Message</label>
                            </div>
                            
                            <button class="btn waves-effect waves-light" type="submit">
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
                            <i class="material-icons left">forum</i>
                            Message History
                        </span>

                        <ul class="collection">
                            <?php foreach ($conversations as $conv): ?>
                                <li class="collection-item avatar message-item" data-message-id="<?php echo $conv['id']; ?>">
                                    <?php if ($conv['sender_id'] == $_SESSION['user_id']): ?>
                                        <i class="material-icons circle green">send</i>
                                    <?php else: ?>
                                        <i class="material-icons circle orange">mail</i>
                                    <?php endif; ?>
                                    
                                    <div class="message-header">
                                        <?php if ($conv['sender_id'] == $_SESSION['user_id']): ?>
                                            <span class="title">To: <?php echo htmlspecialchars($conv['recipient_name']); ?></span>
                                        <?php else: ?>
                                            <span class="title">From: <?php echo htmlspecialchars($conv['sender_name']); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($conv['subject']): ?>
                                            <p class="subject"><?php echo htmlspecialchars($conv['subject']); ?></p>
                                        <?php endif; ?>
                                        
                                        <span class="secondary-content">
                                            <?php echo date('M j, Y g:i A', strtotime($conv['created_at'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="message-content" style="display: none;">
                                        <div class="divider" style="margin: 10px 0;"></div>
                                        <p><?php echo nl2br(htmlspecialchars($conv['message'])); ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.collection .collection-item.avatar {
    min-height: 84px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.collection .collection-item.avatar:hover {
    background-color: #f5f5f5;
}

.message-header {
    margin-left: 72px;
}

.message-header .title {
    font-weight: 500;
    display: block;
    margin-bottom: 3px;
}

.message-header .subject {
    margin: 0;
    color: #666;
}

.message-content {
    padding: 0 15px;
}

.secondary-content {
    color: #9e9e9e !important;
    font-size: 0.9rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize select elements
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);
    
    // Handle recipient type selection
    document.getElementById('recipient_type').addEventListener('change', function() {
        var specificMembers = document.getElementById('specific_members');
        specificMembers.style.display = this.value === 'specific' ? 'block' : 'none';
    });

    // Handle message click to show/hide content
    document.querySelectorAll('.message-item').forEach(function(item) {
        item.addEventListener('click', function() {
            const content = this.querySelector('.message-content');
            if (content.style.display === 'none') {
                // Hide all other message contents
                document.querySelectorAll('.message-content').forEach(function(el) {
                    el.style.display = 'none';
                });
                // Show this message content
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
