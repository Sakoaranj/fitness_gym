<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_plan']) || isset($_POST['edit_plan'])) {
        $plan_id = $_POST['plan_id'] ?? null;
        $name = $_POST['name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $features = $_POST['features'];
        $description = $_POST['description'];
        $status = $_POST['status'] ?? 'active';

        try {
            if (isset($_POST['edit_plan']) && $plan_id) {
                // Update existing plan
                $stmt = $db->prepare("
                    UPDATE plans 
                    SET name = ?, price = ?, duration = ?, 
                        features = ?, description = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $price, $duration, $features, $description, $status, $plan_id]);
                $message = "Plan updated successfully!";
                $message_type = 'success';
            } else {
                // Add new plan
                $stmt = $db->prepare("
                    INSERT INTO plans 
                    (name, price, duration, features, description, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $price, $duration, $features, $description, $status]);
                $message = "New plan added successfully!";
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
        }
    } elseif (isset($_POST['toggle_status'])) {
        $plan_id = $_POST['plan_id'];
        $new_status = $_POST['new_status'];
        
        // Check for active subscriptions if deactivating
        if ($new_status === 'inactive') {
            $check_stmt = $db->prepare("
                SELECT COUNT(*) as sub_count 
                FROM subscriptions s 
                WHERE s.plan_id = ? AND s.status = 'active'
            ");
            $check_stmt->execute([$plan_id]);
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['sub_count'] > 0) {
                $message = "Cannot deactivate plan: There are " . $result['sub_count'] . " active subscriptions using this plan.";
                $message_type = 'error';
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
        }
        
        try {
            $stmt = $db->prepare("UPDATE plans SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $plan_id]);
            $message = "Plan " . ($new_status === 'active' ? 'activated' : 'deactivated') . " successfully!";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
        }
    } elseif (isset($_POST['delete_plan'])) {
        $plan_id = $_POST['plan_id'];
        
        // Check for active subscriptions
        $check_stmt = $db->prepare("
            SELECT COUNT(*) as sub_count 
            FROM subscriptions s 
            WHERE s.plan_id = ? AND s.status = 'active'
        ");
        $check_stmt->execute([$plan_id]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['sub_count'] > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Cannot delete plan: There are " . $result['sub_count'] . " active subscription(s) using this plan."
            ]);
            exit;
        }

        try {
            $stmt = $db->prepare("DELETE FROM plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => "Plan deleted successfully!"
            ]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => "Error deleting plan: " . $e->getMessage()
            ]);
            exit;
        }
    }
}

// Get all plans with subscription counts
$stmt = $db->prepare("
    SELECT p.*, 
           COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.id END) as active_subscriptions,
           COUNT(DISTINCT s.id) as total_subscriptions
    FROM plans p
    LEFT JOIN subscriptions s ON s.plan_id = p.id
    GROUP BY p.id
    ORDER BY p.status DESC, p.price ASC
");
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="container-fluid" style="padding: 20px;">
        <!-- Success/Error Message Container -->
        <div id="message-container" style="display: none;" class="card-panel">
            <span id="message-text"></span>
        </div>

        <div class="row">
            <!-- Add/Edit Plan Form -->
            <div class="col s12 m4">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">add_circle</i>
                            Add New Plan
                        </span>
                        <form method="POST" id="planForm">
                            <input type="hidden" name="plan_id" id="plan_id">
                            
                            <div class="input-field">
                                <input type="text" id="name" name="name" required>
                                <label for="name">Plan Name</label>
                            </div>

                            <div class="input-field">
                                <input type="number" id="price" name="price" step="0.01" required>
                                <label for="price">Price (₱)</label>
                            </div>

                            <div class="input-field">
                                <input type="number" id="duration" name="duration" required min="1">
                                <label for="duration">Duration (months)</label>
                            </div>

                            <div class="input-field">
                                <textarea id="features" name="features" class="materialize-textarea" required></textarea>
                                <label for="features">Features (one per line)</label>
                                <span class="helper-text">Enter each feature on a new line</span>
                            </div>

                            <div class="input-field">
                                <textarea id="description" name="description" class="materialize-textarea"></textarea>
                                <label for="description">Description</label>
                            </div>

                            <div class="input-field">
                                <select name="status" id="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <label>Status</label>
                            </div>

                            <div class="input-field center-align">
                                <button type="submit" name="add_plan" class="btn waves-effect waves-light">
                                    <i class="material-icons left">add</i>
                                    Add Plan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Plans List -->
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">list</i>
                            Membership Plans
                        </span>

                        <?php if ($plans): ?>
                            <table class="striped responsive-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Subscriptions</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plans as $plan): ?>
                                        <tr class="<?php echo $plan['status'] === 'inactive' ? 'grey lighten-2' : ''; ?>">
                                            <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                            <td>₱<?php echo number_format($plan['price'], 2); ?></td>
                                            <td>
                                                <?php echo $plan['duration']; ?> month<?php echo $plan['duration'] > 1 ? 's' : ''; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $plan['status'] === 'active' ? 'green' : 'grey'; ?> white-text">
                                                    <?php echo ucfirst($plan['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($plan['active_subscriptions'] > 0): ?>
                                                    <span class="new badge orange" data-badge-caption="active">
                                                        <?php echo $plan['active_subscriptions']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="grey-text">No active</span>
                                                <?php endif; ?>
                                                <br>
                                                <small class="grey-text">
                                                    Total: <?php echo $plan['total_subscriptions']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn-small blue waves-effect waves-light edit-plan" 
                                                        data-id="<?php echo $plan['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($plan['name']); ?>"
                                                        data-price="<?php echo $plan['price']; ?>"
                                                        data-duration="<?php echo $plan['duration']; ?>"
                                                        data-features="<?php echo htmlspecialchars($plan['features']); ?>"
                                                        data-description="<?php echo htmlspecialchars($plan['description']); ?>"
                                                        data-status="<?php echo $plan['status']; ?>">
                                                    <i class="material-icons">edit</i>
                                                </button>
                                                
                                                <?php if ($plan['active_subscriptions'] == 0): ?>
                                                    <button class="btn-small red waves-effect waves-light delete-plan"
                                                            data-id="<?php echo $plan['id']; ?>">
                                                        <i class="material-icons">delete</i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn-small <?php echo $plan['status'] === 'active' ? 'orange' : 'green'; ?> waves-effect waves-light toggle-status"
                                                        data-id="<?php echo $plan['id']; ?>"
                                                        data-status="<?php echo $plan['status']; ?>">
                                                    <i class="material-icons">
                                                        <?php echo $plan['status'] === 'active' ? 'pause' : 'play_arrow'; ?>
                                                    </i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="center-align grey-text">
                                <i class="material-icons medium">info_outline</i><br>
                                No plans found.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);
    
    // Function to show message
    function showMessage(message, isError = false) {
        const messageContainer = document.getElementById('message-container');
        const messageText = document.getElementById('message-text');
        
        messageContainer.className = 'card-panel ' + (isError ? 'red' : 'green') + ' lighten-4';
        messageText.className = (isError ? 'red-text' : 'green-text');
        messageText.textContent = message;
        messageContainer.style.display = 'block';
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            messageContainer.style.display = 'none';
        }, 3000);
    }

    // Handle edit plan button
    document.querySelectorAll('.edit-plan').forEach(function(button) {
        button.addEventListener('click', function() {
            var planId = this.getAttribute('data-id');
            var planName = this.getAttribute('data-name');
            var planPrice = this.getAttribute('data-price');
            var planDuration = this.getAttribute('data-duration');
            var planFeatures = this.getAttribute('data-features');
            var planDescription = this.getAttribute('data-description');
            var planStatus = this.getAttribute('data-status');

            document.getElementById('plan_id').value = planId;
            document.getElementById('name').value = planName;
            document.getElementById('price').value = planPrice;
            document.getElementById('duration').value = planDuration;
            document.getElementById('features').value = planFeatures;
            document.getElementById('description').value = planDescription;
            document.getElementById('status').value = planStatus;

            // Update form labels
            M.updateTextFields();
            M.FormSelect.init(document.getElementById('status'));

            // Update form button
            var submitButton = document.querySelector('#planForm button[type="submit"]');
            submitButton.innerHTML = '<i class="material-icons left">edit</i> Update Plan';
            submitButton.name = 'edit_plan';

            // Scroll to form
            document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Handle delete plan button
    document.querySelectorAll('.delete-plan').forEach(function(button) {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this plan?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_plan" value="1">
                    <input type="hidden" name="plan_id" value="${this.getAttribute('data-id')}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    
    // Handle toggle status button
    document.querySelectorAll('.toggle-status').forEach(function(button) {
        button.addEventListener('click', function() {
            var currentStatus = this.getAttribute('data-status');
            var newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            var actionText = currentStatus === 'active' ? 'deactivate' : 'activate';
            
            if (confirm(`Are you sure you want to ${actionText} this plan?`)) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="toggle_status" value="1">
                    <input type="hidden" name="plan_id" value="${this.getAttribute('data-id')}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    
    // Show message if exists
    if ('<?php echo $message; ?>') {
        showMessage('<?php echo $message; ?>', '<?php echo $message_type; ?>' === 'error');
    }
});
</script>

<style>
#message-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    border-radius: 4px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
