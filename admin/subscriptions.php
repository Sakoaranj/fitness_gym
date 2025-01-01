<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle payment settings management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'verify_payment':
                try {
                    $subscription_id = $_POST['subscription_id'];
                    $payment_id = $_POST['payment_id'];

                    // Start transaction
                    $db->beginTransaction();

                    // Get subscription details
                    $stmt = $db->prepare("
                        SELECT s.*, pl.duration 
                        FROM subscriptions s
                        JOIN plans pl ON s.plan_id = pl.id
                        WHERE s.id = ?
                    ");
                    $stmt->execute([$subscription_id]);
                    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$subscription) {
                        throw new Exception("Subscription not found");
                    }

                    // Calculate subscription dates
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d', strtotime("+{$subscription['duration']} months"));

                    // Update payment status
                    $stmt = $db->prepare("
                        UPDATE payments 
                        SET status = 'approved', 
                            updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$payment_id]);

                    // Update subscription status and dates
                    $stmt = $db->prepare("
                        UPDATE subscriptions 
                        SET status = 'active',
                            start_date = ?,
                            end_date = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$start_date, $end_date, $subscription_id]);

                    // Create notification for user
                    $stmt = $db->prepare("
                        INSERT INTO notifications (
                            user_id, type, message, status, created_at
                        ) VALUES (?, 'subscription', ?, 'unread', NOW())
                    ");
                    $notification_message = "Your subscription payment has been verified and your membership is now active!";
                    $stmt->execute([$subscription['user_id'], $notification_message]);

                    $db->commit();
                    $success_message = "Payment verified and subscription activated successfully.";
                } catch (Exception $e) {
                    $db->rollBack();
                    $error_message = "Error processing verification: " . $e->getMessage();
                }
                break;

            case 'reject_payment':
                try {
                    $subscription_id = $_POST['subscription_id'];
                    $payment_id = $_POST['payment_id'];

                    // Start transaction
                    $db->beginTransaction();

                    // Update payment status
                    $stmt = $db->prepare("
                        UPDATE payments 
                        SET status = 'rejected', 
                            updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$payment_id]);

                    // Update subscription status
                    $stmt = $db->prepare("
                        UPDATE subscriptions 
                        SET status = 'cancelled',
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$subscription_id]);

                    // Get user details for notification
                    $stmt = $db->prepare("
                        SELECT s.user_id, pl.name as plan_name 
                        FROM subscriptions s
                        JOIN plans pl ON s.plan_id = pl.id
                        WHERE s.id = ?
                    ");
                    $stmt->execute([$subscription_id]);
                    $sub_data = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Create notification for user
                    $stmt = $db->prepare("
                        INSERT INTO notifications (
                            user_id, type, message, status, created_at
                        ) VALUES (?, 'subscription', ?, 'unread', NOW())
                    ");
                    $notification_message = "Your subscription payment for {$sub_data['plan_name']} plan was rejected. Please check your payment details and try again.";
                    $stmt->execute([$sub_data['user_id'], $notification_message]);

                    $db->commit();
                    $success_message = "Payment rejected and subscription cancelled.";
                } catch (Exception $e) {
                    $db->rollBack();
                    $error_message = "Error processing rejection: " . $e->getMessage();
                }
                break;

            case 'update_payment_settings':
                try {
                    $payment_id = $_POST['payment_id'] ?? null;
                    $payment_method = $_POST['payment_method'];
                    $account_name = $_POST['account_name'];
                    $account_number = $_POST['account_number'];
                    $status = $_POST['status'];

                    if ($payment_id) {
                        // Update existing payment setting
                        $stmt = $db->prepare("
                            UPDATE payment_settings 
                            SET payment_method = ?, account_name = ?, account_number = ?, 
                                status = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$payment_method, $account_name, $account_number, $status, $payment_id]);
                    } else {
                        // Insert new payment setting
                        $stmt = $db->prepare("
                            INSERT INTO payment_settings (
                                payment_method, account_name, account_number, status
                            ) VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$payment_method, $account_name, $account_number, $status]);
                    }
                    
                    $success_message = "Payment settings updated successfully.";
                } catch (Exception $e) {
                    $error_message = "Error updating payment settings: " . $e->getMessage();
                }
                break;

            case 'delete_payment_setting':
                try {
                    $payment_id = $_POST['payment_id'];
                    $stmt = $db->prepare("DELETE FROM payment_settings WHERE id = ?");
                    $stmt->execute([$payment_id]);
                    $success_message = "Payment setting deleted successfully.";
                } catch (Exception $e) {
                    $error_message = "Error deleting payment setting: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all subscriptions with payment details
$stmt = $db->prepare("
    SELECT 
        s.*,
        p.id as payment_id,
        p.payment_reference,
        p.payment_method,
        p.payment_screenshot,
        p.status as payment_status,
        p.amount,
        p.created_at as payment_date,
        u.full_name,
        u.email,
        pl.name as plan_name,
        pl.duration as plan_duration
    FROM subscriptions s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN payments p ON s.payment_id = p.id
    JOIN plans pl ON s.plan_id = pl.id
    ORDER BY 
        CASE 
            WHEN s.status = 'pending' THEN 1
            WHEN s.status = 'active' THEN 2
            ELSE 3
        END,
        s.created_at DESC
");
$stmt->execute();
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment settings
$stmt = $db->prepare("SELECT * FROM payment_settings ORDER BY payment_method");
$stmt->execute();
$payment_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="dashboard-container">
        <?php if ($success_message): ?>
            <div class="card-panel green white-text"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="card-panel red white-text"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Subscriptions List -->
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">subscriptions</i>
                            Subscription Management
                        </span>
                        
                        <!-- Filter and Search Section -->
                        <div class="filter-section">
                            <div class="row">
                                <div class="col s12">
                                    <label for="search-input">Search Member:</label>
                                    <div class="input-field-with-button">
                                        <input type="text" id="search-input" class="browser-default" placeholder="Search by name or email...">
                                        <button type="button" id="search-button" class="btn waves-effect waves-light">
                                            <i class="material-icons">search</i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="no-results-alert" style="display: none; padding: 10px; background-color: #f5f5f5; border-radius: 4px; margin-bottom: 20px;">
                            <i class="material-icons left">info</i>
                            No results found. Please try a different search term.
                        </div>

                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Member Details</th>
                                    <th>Plan Details</th>
                                    <th>Payment Info</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subscriptions)): ?>
                                    <tr>
                                        <td colspan="5" class="center-align">No subscriptions found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subscriptions as $subscription): ?>
                                        <tr class="subscription-row" data-status="<?php echo $subscription['status']; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($subscription['full_name']); ?></strong><br>
                                                <small>Email: <?php echo htmlspecialchars($subscription['email']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($subscription['plan_name']); ?></strong><br>
                                                <small>Duration: <?php echo $subscription['plan_duration']; ?> months</small><br>
                                                <small>Amount: ₱<?php echo number_format($subscription['amount'], 2); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($subscription['payment_method']); ?></strong><br>
                                                <small>Ref: <?php echo htmlspecialchars($subscription['payment_reference']); ?></small><br>
                                                <small>Date: <?php echo date('M d, Y', strtotime($subscription['payment_date'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($subscription['status'] === 'pending'): ?>
                                                    <span class="badge orange white-text">Pending</span>
                                                <?php elseif ($subscription['status'] === 'active'): ?>
                                                    <span class="badge green white-text">Active</span>
                                                <?php elseif ($subscription['status'] === 'expired'): ?>
                                                    <span class="badge grey white-text">Expired</span>
                                                <?php elseif ($subscription['status'] === 'cancelled'): ?>
                                                    <span class="badge red white-text">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($subscription['payment_screenshot']): ?>
                                                    <a href="#" class="btn-small blue view-proof" 
                                                       data-image="../uploads/payments/<?php echo htmlspecialchars($subscription['payment_screenshot']); ?>"
                                                       data-reference="<?php echo htmlspecialchars($subscription['payment_reference']); ?>">
                                                        <i class="material-icons">visibility</i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Settings Card -->
            <div class="col s12 m4">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">payment</i>
                            Payment Settings
                        </span>
                        <div class="right-align" style="margin-bottom: 20px;">
                            <a class="btn-floating btn-small blue add-payment-setting">
                                <i class="material-icons">add</i>
                            </a>
                        </div>
                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Account</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_settings as $method): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($method['payment_method']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($method['account_name']); ?><br>
                                            <small><?php echo htmlspecialchars($method['account_number']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($method['status'] === 'active'): ?>
                                                <span class="badge green white-text">Active</span>
                                            <?php else: ?>
                                                <span class="badge grey white-text">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="#" class="btn-small blue edit-payment-setting" 
                                               data-id="<?php echo $method['id']; ?>"
                                               data-method="<?php echo htmlspecialchars($method['payment_method']); ?>"
                                               data-name="<?php echo htmlspecialchars($method['account_name']); ?>"
                                               data-number="<?php echo htmlspecialchars($method['account_number']); ?>"
                                               data-status="<?php echo $method['status']; ?>">
                                                <i class="material-icons">edit</i>
                                            </a>
                                            <a href="#" class="btn-small red delete-payment-setting" 
                                               data-id="<?php echo $method['id']; ?>">
                                                <i class="material-icons">delete</i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Settings Modal -->
    <div id="payment-settings-modal" class="modal">
        <form method="POST" id="payment-settings-form">
            <div class="modal-content">
                <h4>Payment Settings</h4>
                <input type="hidden" name="action" value="update_payment_settings">
                <input type="hidden" name="payment_id" id="payment_id">
                
                <div class="input-field">
                    <input type="text" id="payment_method" name="payment_method" required>
                    <label for="payment_method">Payment Method</label>
                </div>
                
                <div class="input-field">
                    <input type="text" id="account_name" name="account_name" required>
                    <label for="account_name">Account Name</label>
                </div>
                
                <div class="input-field">
                    <input type="text" id="account_number" name="account_number" required>
                    <label for="account_number">Account Number</label>
                </div>
                
                <div class="input-field">
                    <select name="status" id="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <label>Status</label>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
                <button type="submit" class="waves-effect waves-green btn-flat">Save</button>
            </div>
        </form>
    </div>

    <!-- Payment Proof Modal -->
    <div id="payment-proof-modal" class="modal">
        <div class="modal-content">
            <h4>Payment Proof</h4>
            <p id="payment-reference-display"></p>
            <div class="modal-image-container">
                <img id="payment-proof-image" src="" alt="Payment Proof">
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
        </div>
    </div>
</main>

<style>
    .filter-section {
        margin: 20px 0;
        padding: 15px;
        background: #f5f5f5;
        border-radius: 4px;
    }
    .filter-section label {
        display: block;
        margin-bottom: 5px;
        color: #666;
        font-size: 0.9rem;
    }
    .filter-section input[type="text"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: white;
        height: 2.5rem;
    }
    .filter-section input[type="text"]:focus {
        border-color: #26a69a;
        box-shadow: 0 1px 0 0 #26a69a;
    }
    .input-field-with-button {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .input-field-with-button input[type="text"] {
        flex: 1;
    }
    .input-field-with-button button[type="button"] {
        padding: 0 10px;
        border: none;
        border-radius: 4px;
        background-color: #26a69a;
        color: white;
        cursor: pointer;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .input-field-with-button button[type="button"]:hover {
        background-color: #2bbbad;
    }
    .status-badge {
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        text-transform: uppercase;
        font-weight: 500;
    }
    .status-pending { background-color: #ff9800; }
    .status-active { background-color: #4caf50; }
    .status-expired { background-color: #9e9e9e; }
    .status-cancelled { background-color: #f44336; }
    
    /* Modal styles */
    .modal {
        max-width: 600px !important;
        width: 90% !important;
        max-height: 80vh !important;
        height: auto !important;
        border-radius: 8px;
    }
    .modal .modal-content {
        padding: 20px;
        height: calc(100% - 56px); /* Subtract modal footer height */
    }
    .modal-image-container {
        text-align: center;
        margin: 15px 0;
        height: calc(100% - 60px); /* Subtract header and reference text height */
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-image-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .modal-footer {
        padding: 10px 20px !important;
        height: 56px;
    }
    #payment-reference-display {
        font-size: 1rem;
        color: #333;
        margin: 0;
        padding: 0;
        line-height: 1.5;
    }
    .modal .modal-close {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px;
        cursor: pointer;
    }
    .modal h4 {
        margin: 0;
        font-size: 1.5rem;
        padding-right: 30px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Materialize components
        var modals = document.querySelectorAll('.modal');
        M.Modal.init(modals);
        var selects = document.querySelectorAll('select');
        M.FormSelect.init(selects);

        var searchInput = document.getElementById('search-input');
        var searchButton = document.getElementById('search-button');
        var subscriptionsTable = document.querySelector('table tbody');
        var noResultsAlert = document.getElementById('no-results-alert');
        
        function filterSubscriptions() {
            var searchTerm = searchInput.value.toLowerCase();
            var rows = subscriptionsTable.getElementsByTagName('tr');
            var visibleCount = 0;
            
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var memberCell = row.querySelector('td:first-child');
                
                if (memberCell) {
                    var memberText = memberCell.textContent.toLowerCase();
                    
                    var searchMatch = searchTerm === '' || memberText.includes(searchTerm);
                    
                    if (searchMatch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            }

            // Show/hide no results message
            if (visibleCount === 0 && searchTerm !== '') {
                noResultsAlert.style.display = 'block';
            } else {
                noResultsAlert.style.display = 'none';
            }
        }

        // Add event listeners
        searchButton.addEventListener('click', filterSubscriptions);
        
        // Add enter key support for search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterSubscriptions();
            }
        });

        // View Payment Proof
        document.querySelectorAll('.view-proof').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var image = this.getAttribute('data-image');
                var reference = this.getAttribute('data-reference');
                document.getElementById('payment-proof-image').src = image;
                document.getElementById('payment-reference-display').textContent = 'Reference: ' + reference;
                var modal = M.Modal.getInstance(document.getElementById('payment-proof-modal'));
                modal.open();
            });
        });

        // Add Payment Setting
        document.querySelector('.add-payment-setting').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('payment_id').value = '';
            document.getElementById('payment-settings-form').reset();
            M.updateTextFields();
            var modal = M.Modal.getInstance(document.getElementById('payment-settings-modal'));
            modal.open();
        });

        // Edit Payment Setting
        document.querySelectorAll('.edit-payment-setting').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                var method = this.getAttribute('data-method');
                var name = this.getAttribute('data-name');
                var number = this.getAttribute('data-number');
                var status = this.getAttribute('data-status');

                document.getElementById('payment_id').value = id;
                document.getElementById('payment_method').value = method;
                document.getElementById('account_name').value = name;
                document.getElementById('account_number').value = number;
                document.getElementById('status').value = status;
                
                M.updateTextFields();
                M.FormSelect.init(document.getElementById('status'));
                
                var modal = M.Modal.getInstance(document.getElementById('payment-settings-modal'));
                modal.open();
            });
        });

        // Delete Payment Setting
        document.querySelectorAll('.delete-payment-setting').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this payment setting?')) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    
                    var action = document.createElement('input');
                    action.type = 'hidden';
                    action.name = 'action';
                    action.value = 'delete_payment_setting';
                    
                    var paymentId = document.createElement('input');
                    paymentId.type = 'hidden';
                    paymentId.name = 'payment_id';
                    paymentId.value = this.getAttribute('data-id');
                    
                    form.appendChild(action);
                    form.appendChild(paymentId);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
