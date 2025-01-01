<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? '';
        if ($user_id) {
            try {
                // Check for active subscription
                $stmt = $db->prepare("
                    SELECT COUNT(*) as active_count 
                    FROM subscriptions 
                    WHERE user_id = ? AND status = 'active'
                ");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['active_count'] > 0) {
                    throw new Exception("Cannot delete member with active subscription. Please wait for the subscription to expire or cancel it first.");
                }

                // Start transaction
                $db->beginTransaction();

                // Delete from payments table first (if any)
                $stmt = $db->prepare("DELETE FROM payments WHERE user_id = ?");
                $stmt->execute([$user_id]);

                // Delete from subscriptions table (expired/cancelled ones if any)
                $stmt = $db->prepare("DELETE FROM subscriptions WHERE user_id = ?");
                $stmt->execute([$user_id]);

                // Finally delete the user
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $db->commit();
                    $success_message = "Member deleted successfully.";
                } else {
                    throw new Exception("Error deleting member.");
                }
            } catch (Exception $e) {
                $db->rollBack();
                $error_message = $e->getMessage();
            }
        }
    }
}

// Get all members (users with role = 'member')
$query = "SELECT u.*, 
    COALESCE(s.status, 'inactive') as subscription_status,
    COALESCE(s.plan_id, 0) as plan_id,
    p.name as plan_name
    FROM users u 
    LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
    LEFT JOIN plans p ON s.plan_id = p.id
    WHERE u.role = 'member'
    ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="dashboard-container">
        <?php if ($success_message): ?>
            <div class="card-panel green white-text success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="card-panel red white-text error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s6">
                                <span class="card-title">Members</span>
                            </div>
                            <div class="col s6">
                                <div class="input-field">
                                    <i class="material-icons prefix">search</i>
                                    <input type="text" id="search-input" placeholder="Search members...">
                                </div>
                            </div>
                        </div>
                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Join Date</th>
                                    <th>Subscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="members-table-body">
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['username']); ?></td>
                                        <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $member['subscription_status'] === 'active' ? 'green' : 'grey'; ?> white-text">
                                                <?php 
                                                    echo $member['subscription_status'] === 'active' 
                                                        ? htmlspecialchars($member['plan_name']) 
                                                        : 'Inactive';
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-small waves-effect waves-light blue view-member" 
                                                    data-id="<?php echo $member['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($member['username']); ?>"
                                                    data-fullname="<?php echo htmlspecialchars($member['full_name'] ?? $member['username']); ?>"
                                                    data-email="<?php echo htmlspecialchars($member['email']); ?>"
                                                    data-joindate="<?php echo date('M d, Y', strtotime($member['created_at'])); ?>">
                                                <i class="material-icons">visibility</i>
                                            </button>
                                            <button class="btn-small waves-effect waves-light red delete-member modal-trigger"
                                                    href="#delete-member-modal"
                                                    data-id="<?php echo $member['id']; ?>"
                                                    data-fullname="<?php echo htmlspecialchars($member['full_name'] ?? $member['username']); ?>"
                                                    data-subscription="<?php echo $member['subscription_status']; ?>">
                                                <i class="material-icons">delete</i>
                                            </button>
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

    <!-- View Member Modal -->
    <div id="view-member-modal" class="modal">
        <div class="modal-content">
            <h4>Member Details</h4>
            <div class="progress" id="member-details-loader">
                <div class="indeterminate"></div>
            </div>
            <div id="member-details-content" style="display: none;">
                <div class="row">
                    <div class="col s12">
                        <h5>Basic Information</h5>
                        <table class="striped">
                            <tbody>
                                <tr>
                                    <th>Username:</th>
                                    <td id="member-username"></td>
                                </tr>
                                <tr>
                                    <th>Full Name:</th>
                                    <td id="member-fullname"></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td id="member-email"></td>
                                </tr>
                                <tr>
                                    <th>Join Date:</th>
                                    <td id="member-joindate"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col s12">
                        <h5>Subscription Details</h5>
                        <div id="subscription-details"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col s12">
                        <h5>Payment History</h5>
                        <div id="payment-history"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-red btn-flat">Close</a>
        </div>
    </div>

    <!-- Delete Member Modal -->
    <div id="delete-member-modal" class="modal">
        <div class="modal-content">
            <h4>Confirm Delete</h4>
            <p>Are you sure you want to delete <strong id="delete-member-name"></strong>?</p>
            <p class="red-text" id="delete-warning" style="display: none;">
                <i class="material-icons left">warning</i>
                Cannot delete member with active subscription. Please wait for the subscription to expire or cancel it first.
            </p>
            <form method="POST" id="delete-member-form">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete-member-id">
                <div class="modal-footer">
                    <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
                    <button type="submit" class="waves-effect waves-light btn red" id="delete-confirm-btn">Delete</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    // Handle view member button click
    document.querySelectorAll('.view-member').forEach(function(button) {
        button.addEventListener('click', function() {
            var memberId = this.getAttribute('data-id');
            var username = this.getAttribute('data-username');
            var fullname = this.getAttribute('data-fullname');
            var email = this.getAttribute('data-email');
            var joindate = this.getAttribute('data-joindate');

            // Show loader and hide content
            document.getElementById('member-details-loader').style.display = 'block';
            document.getElementById('member-details-content').style.display = 'none';

            // Set basic information
            document.getElementById('member-username').textContent = username;
            document.getElementById('member-fullname').textContent = fullname;
            document.getElementById('member-email').textContent = email;
            document.getElementById('member-joindate').textContent = joindate;

            // Open modal
            var modal = M.Modal.getInstance(document.getElementById('view-member-modal'));
            modal.open();

            // Fetch additional member details
            fetch('get_member_details.php?id=' + memberId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.error || 'Unknown error occurred');
                    }

                    // Update subscription details
                    var subscriptionHtml = '';
                    if (data.subscription) {
                        subscriptionHtml = `
                            <table class="striped">
                                <tbody>
                                    <tr>
                                        <th>Plan:</th>
                                        <td>${data.subscription.plan_name}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td><span class="badge green white-text">Active</span></td>
                                    </tr>
                                    <tr>
                                        <th>Start Date:</th>
                                        <td>${data.subscription.start_date}</td>
                                    </tr>
                                    <tr>
                                        <th>End Date:</th>
                                        <td>${data.subscription.end_date}</td>
                                    </tr>
                                </tbody>
                            </table>`;
                    } else {
                        subscriptionHtml = '<p>No active subscription</p>';
                    }
                    document.getElementById('subscription-details').innerHTML = subscriptionHtml;

                    // Update payment history
                    var paymentsHtml = data.payments.length > 0 
                        ? `<table class="striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.payments.map(payment => `
                                    <tr>
                                        <td>${payment.payment_date}</td>
                                        <td>$${payment.amount}</td>
                                        <td>${payment.payment_method || 'N/A'}</td>
                                        <td><span class="badge ${payment.status === 'completed' ? 'green' : 'grey'} white-text">${payment.status}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>`
                        : '<p>No payment history</p>';
                    document.getElementById('payment-history').innerHTML = paymentsHtml;

                    // Hide loader and show content
                    document.getElementById('member-details-loader').style.display = 'none';
                    document.getElementById('member-details-content').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('member-details-content').innerHTML = `
                        <div class="card-panel red lighten-4 red-text">
                            <i class="material-icons left">error</i>
                            Error loading member details: ${error.message}
                        </div>`;
                    document.getElementById('member-details-loader').style.display = 'none';
                    document.getElementById('member-details-content').style.display = 'block';
                });
        });
    });

    // Handle delete member button click
    document.querySelectorAll('.delete-member').forEach(function(button) {
        button.addEventListener('click', function(e) {
            var memberId = this.getAttribute('data-id');
            var fullname = this.getAttribute('data-fullname');
            var subscription = this.getAttribute('data-subscription');
            
            document.getElementById('delete-member-id').value = memberId;
            document.getElementById('delete-member-name').textContent = fullname;
            
            // Check subscription status
            var deleteWarning = document.getElementById('delete-warning');
            var deleteButton = document.getElementById('delete-confirm-btn');
            
            if (subscription === 'active') {
                deleteWarning.style.display = 'block';
                deleteButton.style.display = 'none';
            } else {
                deleteWarning.style.display = 'none';
                deleteButton.style.display = 'inline-block';
            }

            // Open delete modal
            var modal = M.Modal.getInstance(document.getElementById('delete-member-modal'));
            modal.open();
        });
    });

    // Handle search
    document.getElementById('search-input').addEventListener('keyup', function() {
        var searchValue = this.value.toLowerCase();
        var rows = document.getElementById('members-table-body').getElementsByTagName('tr');
        
        for (var row of rows) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        }
    });
});
</script>