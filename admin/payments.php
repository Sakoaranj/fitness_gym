<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/Database.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Process payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'verify' or 'reject'
    
    if ($payment_id && ($action === 'verify' || $action === 'reject')) {
        try {
            $db->beginTransaction();
            
            // Update payment status
            $stmt = $db->prepare("
                UPDATE payments 
                SET status = ?
                WHERE id = ?
            ");
            $new_status = ($action === 'verify') ? 'verified' : 'rejected';
            $stmt->execute([$new_status, $payment_id]);

            // If verified, update or create subscription
            if ($action === 'verify') {
                // Get payment details
                $stmt = $db->prepare("
                    SELECT p.*, pl.duration, pl.name as plan_name, pl.id as plan_id
                    FROM payments p
                    JOIN plans pl ON pl.price = p.amount
                    WHERE p.id = ?
                ");
                $stmt->execute([$payment_id]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($payment) {
                    // Calculate subscription dates
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d', strtotime("+{$payment['duration']} months"));

                    // Check for existing subscription
                    $stmt = $db->prepare("
                        SELECT id FROM subscriptions 
                        WHERE user_id = ? AND status IN ('pending', 'active')
                    ");
                    $stmt->execute([$payment['user_id']]);
                    $existing_subscription = $stmt->fetch();

                    if ($existing_subscription) {
                        // Update existing subscription
                        $stmt = $db->prepare("
                            UPDATE subscriptions 
                            SET status = 'active',
                                start_date = ?,
                                end_date = ?,
                                plan_id = ?,
                                payment_id = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $start_date, 
                            $end_date, 
                            $payment['plan_id'],
                            $payment_id,
                            $existing_subscription['id']
                        ]);
                    } else {
                        // Create new subscription
                        $stmt = $db->prepare("
                            INSERT INTO subscriptions (
                                user_id, plan_id, payment_id, amount, start_date, end_date, status
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, 'active'
                            )
                        ");
                        $stmt->execute([
                            $payment['user_id'],
                            $payment['plan_id'],
                            $payment_id,
                            $payment['amount'],
                            $start_date,
                            $end_date
                        ]);
                    }
                }
            } else {
                // If rejected, cancel any pending subscription
                $stmt = $db->prepare("
                    UPDATE subscriptions 
                    SET status = 'cancelled'
                    WHERE user_id = (
                        SELECT user_id FROM payments WHERE id = ?
                    ) AND status = 'pending'
                ");
                $stmt->execute([$payment_id]);
            }

            $db->commit();
            $_SESSION['success'] = "Payment has been " . ($action === 'verify' ? 'verified' : 'rejected') . " successfully.";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error processing payment: " . $e->getMessage();
        }
        
        header("Location: payments.php");
        exit();
    }
}

// Include header after processing
require_once 'includes/header.php';

// Get all payments with user details and plan information
$query = "
    SELECT 
        p.*,
        u.full_name,
        u.email,
        pl.name as plan_name,
        pl.duration,
        COALESCE(s.status, 'pending') as subscription_status
    FROM payments p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN plans pl ON pl.price = p.amount
    LEFT JOIN (
        SELECT user_id, status 
        FROM subscriptions 
        WHERE status IN ('active', 'pending')
    ) s ON s.user_id = p.user_id
    ORDER BY p.payment_date DESC
";
$stmt = $db->prepare($query);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="dashboard-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="card-panel green lighten-4">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="card-panel red lighten-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Payments List -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">payments</i>
                            Payment Verifications
                        </span>

                        <div class="filter-section">
                            <div class="row">
                                <div class="col s12 m6">
                                    <label for="search-input">Search Member:</label>
                                    <div class="input-field-with-button">
                                        <input type="text" id="search-input" class="browser-default" placeholder="Search by name or email...">
                                        <button type="button" id="search-button" class="btn waves-effect waves-light">
                                            <i class="material-icons">search</i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col s12 m6">
                                    <label for="status-filter">Filter by Status:</label>
                                    <select id="status-filter" class="browser-default">
                                        <option value="all">All Payments</option>
                                        <option value="pending">Pending</option>
                                        <option value="verified">Verified</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="no-results-alert" style="display: none; padding: 10px; background-color: #f5f5f5; border-radius: 4px; margin-bottom: 20px;">
                            <i class="material-icons left">info</i>
                            No results found. Please try a different search term or filter.
                        </div>

                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Member</th>
                                    <th>Plan</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('M j, Y g:i A', strtotime($payment['payment_date'])); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($payment['full_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($payment['email']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($payment['plan_name']): ?>
                                                <?php echo htmlspecialchars($payment['plan_name']); ?><br>
                                                <small class="grey-text">
                                                    <?php echo $payment['duration']; ?> Month<?php echo $payment['duration'] > 1 ? 's' : ''; ?>
                                                </small>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo strtoupper($payment['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_reference']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($payment['status'] === 'pending'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                        <input type="hidden" name="action" value="verify">
                                                        <button type="submit" class="btn-small green waves-effect waves-light" 
                                                                onclick="return confirm('Are you sure you want to verify this payment? This will also activate the subscription.')">
                                                            <i class="material-icons">check</i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn-small red waves-effect waves-light"
                                                                onclick="return confirm('Are you sure you want to reject this payment? This will cancel the subscription.')">
                                                            <i class="material-icons">close</i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($payment['payment_screenshot']): ?>
                                                    <a href="#" class="btn-small blue view-screenshot" 
                                                       data-image="../uploads/payments/<?php echo htmlspecialchars($payment['payment_screenshot']); ?>"
                                                       data-reference="<?php echo htmlspecialchars($payment['payment_reference']); ?>">
                                                        <i class="material-icons">visibility</i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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

    <!-- Payment Screenshot Modal -->
    <div id="screenshot-modal" class="modal">
        <div class="modal-content">
            <h4>Payment Proof</h4>
            <p id="payment-reference"></p>
            <div class="modal-image-container">
                <img id="screenshot-image" src="" alt="Payment Proof">
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-red btn-flat">Close</a>
        </div>
    </div>
</main>

<style>
.card {
    height: 100%;
    display: flex;
    flex-direction: column;
}
.card .card-content {
    flex-grow: 1;
}
.modal {
    max-height: 90% !important;
    width: 90% !important;
}
.image-container {
    text-align: center;
    margin: 20px 0;
}
.action-buttons {
    display: flex;
    gap: 5px;
    align-items: center;
}
#status-filter {
    margin-bottom: 20px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
}
select {
    display: block !important;
    height: 2.5rem;
}
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
.status-badge {
    padding: 5px 10px;
    border-radius: 4px;
    color: white;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}
.status-pending {
    background-color: #ff9800;
}
.status-verified {
    background-color: #4caf50;
}
.status-rejected {
    background-color: #f44336;
}
.action-buttons .btn {
    width: 100%;
    margin: 0;
    height: 36px;
    line-height: 36px;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
}
.action-buttons .btn i {
    margin-right: 8px;
    font-size: 1.2rem;
}
.action-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}
table.striped > tbody > tr:nth-child(odd) {
    background-color: rgba(242, 242, 242, 0.5);
}
.table-container {
    overflow-x: auto;
    margin: 0 -20px;
}
table {
    margin: 0 20px;
}
td {
    vertical-align: middle;
    padding: 15px 5px;
}
.payment-proof-container {
    max-height: 80vh;
    overflow-y: auto;
    text-align: center;
}
.payment-proof-container img {
    max-width: 100%;
    height: auto;
}
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
.filter-section input[type="text"],
.filter-section select {
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
.action-buttons {
    display: flex;
    gap: 5px;
    align-items: center;
}
.input-field-with-button {
    display: flex;
    align-items: center;
    gap: 10px;
}
.input-field-with-button input[type="text"] {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    height: 2.5rem;
    width: 100%;
}
.input-field-with-button button[type="button"] {
    padding: 0 10px;
    border: none;
    border-radius: 4px;
    background-color: #26a69a;
    color: white;
    cursor: pointer;
}
.input-field-with-button button[type="button"]:hover {
    background-color: #1a237e;
}
.input-field-with-button button[type="button"]:focus {
    box-shadow: 0 0 0 0.2rem #26a69a;
}

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
#payment-reference {
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
    // Initialize modals
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var statusFilter = document.getElementById('status-filter');
    var searchInput = document.getElementById('search-input');
    var searchButton = document.getElementById('search-button');
    var paymentsTable = document.querySelector('table tbody');
    var noResultsAlert = document.getElementById('no-results-alert');
    
    function filterPayments() {
        var selectedStatus = statusFilter.value;
        var searchTerm = searchInput.value.toLowerCase();
        var rows = paymentsTable.getElementsByTagName('tr');
        var visibleCount = 0;
        
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var memberCell = row.querySelector('td:nth-child(2)');
            var statusCell = row.querySelector('td:nth-child(7)');
            
            if (memberCell && statusCell) {
                var memberText = memberCell.textContent.toLowerCase();
                var status = statusCell.textContent.trim().toLowerCase();
                
                var statusMatch = selectedStatus === 'all' || status === selectedStatus;
                var searchMatch = searchTerm === '' || memberText.includes(searchTerm);
                
                if (statusMatch && searchMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
        }

        // Show/hide no results message
        if (visibleCount === 0 && (searchTerm !== '' || selectedStatus !== 'all')) {
            noResultsAlert.style.display = 'block';
        } else {
            noResultsAlert.style.display = 'none';
        }
    }

    // Add event listeners
    statusFilter.addEventListener('change', filterPayments);
    searchButton.addEventListener('click', filterPayments);
    
    // Add enter key support for search
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            filterPayments();
        }
    });

    // Handle screenshot view
    document.querySelectorAll('.view-screenshot').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var modal = M.Modal.getInstance(document.getElementById('screenshot-modal'));
            var imagePath = this.getAttribute('data-image');
            var reference = this.getAttribute('data-reference');
            
            document.getElementById('screenshot-image').src = imagePath;
            document.getElementById('payment-reference').textContent = reference || 'N/A';
            
            modal.open();
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
