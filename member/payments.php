<?php
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Location: ../login.php');
    exit;
}

// Handle payment proof upload first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $payment_id = $_POST['payment_id'];
    $upload_dir = '../uploads/payments/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['message'] = 'Invalid file type. Only JPG, PNG, and PDF files are allowed.';
            $_SESSION['message_type'] = 'red';
        } else {
            $new_filename = 'payment_' . $payment_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_file)) {
                $stmt = $db->prepare("UPDATE payments SET payment_screenshot = ? WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$new_filename, $payment_id, $_SESSION['user_id']])) {
                    $_SESSION['message'] = 'Payment proof uploaded successfully.';
                    $_SESSION['message_type'] = 'green';
                    header('Location: payments.php');
                    exit;
                }
            }
        }
    }
    header('Location: payments.php');
    exit;
}

require_once 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get all payments for the user with subscription details
$stmt = $db->prepare("
    SELECT 
        p.id,
        p.user_id,
        p.amount,
        p.payment_method,
        COALESCE(p.payment_reference, 'N/A') as payment_reference,
        p.payment_screenshot,
        COALESCE(p.payment_date, p.created_at) as payment_date,
        p.status as payment_status,
        p.created_at,
        s.id as subscription_id,
        s.plan as subscription_plan,
        s.start_date,
        s.end_date,
        s.status as subscription_status,
        s.plan_id,
        pl.name as plan_name,
        pl.duration as plan_duration
    FROM payments p
    LEFT JOIN subscriptions s ON p.id = s.payment_id
    LEFT JOIN plans pl ON s.plan_id = pl.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="dashboard-container">
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
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">payment</i>
                            Payment History
                        </span>
                        <?php if (empty($payments)): ?>
                            <p class="center-align grey-text">No payment records found.</p>
                        <?php else: ?>
                            <table class="striped responsive-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Plan</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Duration</th>
                                        <th>Payment Proof</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('F d, Y', strtotime($payment['payment_date'])); ?><br>
                                                <small class="grey-text"><?php echo date('h:i A', strtotime($payment['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($payment['subscription_plan'] ?? 'N/A'); ?>
                                                <?php if ($payment['subscription_status']): ?>
                                                    <br>
                                                    <small class="grey-text">
                                                        Status: 
                                                        <?php if ($payment['subscription_status'] === 'active'): ?>
                                                            <span class="green-text">Active</span>
                                                        <?php elseif ($payment['subscription_status'] === 'pending'): ?>
                                                            <span class="orange-text">Pending</span>
                                                        <?php else: ?>
                                                            <span class="red-text"><?php echo ucfirst($payment['subscription_status']); ?></span>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_reference']); ?></td>
                                            <td>
                                                <?php if ($payment['payment_status'] === 'pending'): ?>
                                                    <span class="new badge yellow darken-2" data-badge-caption="Pending"></span>
                                                <?php elseif ($payment['payment_status'] === 'approved'): ?>
                                                    <span class="new badge green" data-badge-caption="Approved"></span>
                                                <?php else: ?>
                                                    <span class="new badge red" data-badge-caption="<?php echo ucfirst($payment['payment_status']); ?>"></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($payment['start_date'] && $payment['end_date']): ?>
                                                    <?php echo date('M d', strtotime($payment['start_date'])); ?> - 
                                                    <?php echo date('M d, Y', strtotime($payment['end_date'])); ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($payment['payment_screenshot'])): ?>
                                                    <a href="#" onclick="viewPaymentProof('../uploads/payments/<?php echo htmlspecialchars($payment['payment_screenshot']); ?>', '<?php echo htmlspecialchars($payment['payment_reference']); ?>')" class="btn-small blue">
                                                        <i class="material-icons">visibility</i>
                                                    </a>
                                                <?php else: ?>
                                                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                        <div class="file-field input-field">
                                                            <div class="btn-small grey">
                                                                <i class="material-icons">file_upload</i>
                                                                <input type="file" name="payment_proof" onchange="this.form.submit()">
                                                            </div>
                                                        </div>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Proof Modal -->
    <div id="payment-proof-modal" class="modal">
        <div class="modal-content">
            <h4>Payment Proof</h4>
            <p id="payment-reference-display"></p>
            <div class="center-align">
                <img id="payment-proof-image" src="" alt="Payment Proof" style="max-width: 100%; height: auto;">
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.querySelectorAll('.modal');
    M.Modal.init(modal);
});

function viewPaymentProof(imagePath, reference) {
    document.getElementById('payment-proof-image').src = imagePath;
    document.getElementById('payment-reference-display').textContent = 'Reference: ' + reference;
    var modal = M.Modal.getInstance(document.getElementById('payment-proof-modal'));
    modal.open();
}
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.card .card-content {
    padding: 24px;
}

table {
    margin-top: 20px;
}

th {
    background-color: #f5f5f5;
    padding: 15px 10px;
}

td {
    padding: 15px 10px;
    vertical-align: middle;
}

.btn-small {
    padding: 0 8px;
    height: 24px;
    line-height: 24px;
}

.btn-small i {
    font-size: 16px;
    line-height: 24px;
}

.modal {
    max-width: 600px;
    max-height: 90%;
}

#payment-proof-image {
    margin: 20px 0;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
}

.upload-form {
    margin: 0;
}

.upload-form .file-field {
    margin: 0;
}

.upload-form .file-field .btn-small {
    float: none;
    height: 24px;
    line-height: 24px;
}

.upload-form .file-field input[type=file] {
    width: 100%;
    height: 100%;
    cursor: pointer;
}
</style>

<?php require_once 'includes/footer.php'; ?>
