<?php
require_once 'includes/header.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Check if user has active or pending subscription
$subscription_stmt = $db->prepare("
    SELECT 
        s.status as subscription_status,
        p.status as payment_status
    FROM subscriptions s
    LEFT JOIN payments p ON s.payment_id = p.id
    WHERE s.user_id = ? 
    AND (s.status = 'active' OR (s.status = 'pending' AND p.status = 'pending'))
    ORDER BY s.created_at DESC 
    LIMIT 1
");
$subscription_stmt->execute([$_SESSION['user_id']]);
$subscription = $subscription_stmt->fetch(PDO::FETCH_ASSOC);

// Get all active plans
$plans_stmt = $db->prepare("
    SELECT * 
    FROM plans 
    WHERE status = 'active'
    ORDER BY price ASC
");
$plans_stmt->execute();
$plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active payment settings
$payment_settings_stmt = $db->prepare("
    SELECT * 
    FROM payment_settings 
    WHERE status = 'active'
    ORDER BY payment_method ASC
");
$payment_settings_stmt->execute();
$payment_settings = $payment_settings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Clear success message after displaying once
if (isset($_SESSION['message']) && isset($_SESSION['message_displayed'])) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    unset($_SESSION['message_displayed']);
}
?>

<main>
    <div class="plans-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="card-panel <?php echo $_SESSION['message_type']; ?> lighten-4">
                <?php 
                echo $_SESSION['message'];
                $_SESSION['message_displayed'] = true; // Mark message as displayed
                ?>
            </div>
        <?php endif; ?>

        <?php if ($subscription): ?>
            <div class="card">
                <div class="card-content">
                    <?php if ($subscription['subscription_status'] === 'pending'): ?>
                        <div class="card-panel orange lighten-4">
                            <i class="material-icons left">pending</i>
                            <strong>Subscription Status: Pending Approval</strong><br>
                            Your subscription is currently pending approval. Please wait while we verify your payment.
                            You cannot subscribe to a new plan until your current subscription request is processed.
                            <br><br>
                            <a href="subscription.php" class="btn-small orange">View Subscription Status</a>
                        </div>
                    <?php else: ?>
                        <div class="card-panel green lighten-4">
                            <i class="material-icons left">check_circle</i>
                            <strong>Active Subscription</strong><br>
                            You already have an active subscription. Visit your subscription page to view details.
                            <br><br>
                            <a href="subscription.php" class="btn-small green">View Subscription</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($plans as $plan): ?>
                <div class="col s12 m6 l4">
                    <div class="card plan-card hoverable">
                        <div class="card-content">
                            <span class="card-title center-align">
                                <?php echo htmlspecialchars($plan['name']); ?>
                            </span>
                            <div class="price center-align">
                                ₱<?php echo number_format($plan['price'], 2); ?>
                            </div>
                            <div class="duration center-align">
                                <?php echo $plan['duration']; ?> <?php echo $plan['duration'] > 1 ? 'Months' : 'Month'; ?>
                            </div>
                            <div class="divider"></div>
                            <div class="features">
                                <?php 
                                $features = explode("\n", $plan['features']);
                                echo "<ul class='browser-default'>";
                                foreach ($features as $feature) {
                                    if (trim($feature)) {
                                        echo "<li>" . htmlspecialchars(trim($feature)) . "</li>";
                                    }
                                }
                                echo "</ul>";
                                ?>
                            </div>
                        </div>
                        <div class="card-action center-align">
                            <?php if (!$subscription): ?>
                                <button onclick="showSubscribeModal('<?php echo $plan['id']; ?>', '<?php echo htmlspecialchars($plan['name']); ?>', '<?php echo $plan['price']; ?>')" 
                                        class="btn waves-effect waves-light blue subscribe-btn">
                                    Subscribe Now
                                </button>
                            <?php else: ?>
                                <button class="btn disabled grey" disabled>
                                    <?php echo $subscription['subscription_status'] === 'pending' ? 'Pending Approval' : 'Already Subscribed'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Subscribe Modal -->
<div id="subscribe-modal" class="modal">
    <form action="process_subscription.php" method="POST" enctype="multipart/form-data" id="subscription-form">
        <div class="modal-content">
            <h4>Subscribe to <span id="plan-name"></span></h4>
            <input type="hidden" name="plan_id" id="plan-id">
            
            <div class="row">
                <div class="col s12">
                    <div class="payment-details">
                        <p class="total-amount">Total Amount: ₱<span id="plan-price"></span></p>
                        
                        <?php if (empty($payment_settings)): ?>
                            <div class="card-panel red lighten-4">
                                <i class="material-icons left">error</i>
                                No payment methods available. Please contact the administrator.
                            </div>
                        <?php else: ?>
                            <div class="payment-methods-section">
                                <p class="payment-methods-title">Select Payment Method:</p>
                                <div class="payment-methods">
                                    <?php foreach ($payment_settings as $index => $setting): ?>
                                        <p>
                                            <label>
                                                <input name="payment_method" type="radio" 
                                                    value="<?php echo strtolower($setting['payment_method']); ?>"
                                                    <?php echo $index === 0 ? 'checked' : ''; ?> 
                                                    data-method="<?php echo strtolower($setting['payment_method']); ?>" />
                                                <span><?php echo htmlspecialchars($setting['payment_method']); ?></span>
                                            </label>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="payment-instructions">
                                <?php foreach ($payment_settings as $index => $setting): ?>
                                    <div id="<?php echo strtolower($setting['payment_method']); ?>-instructions" 
                                         class="payment-instruction-section"
                                         style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>">
                                        <div class="account-details">
                                            <p>
                                                <strong>Account Name:</strong> 
                                                <?php echo htmlspecialchars($setting['account_name']); ?>
                                            </p>
                                            <p>
                                                <strong><?php echo $setting['payment_method'] === 'GCash' ? 'GCash Number' : 'Account Number'; ?>:</strong>
                                                <span class="account-number-container">
                                                    <span class="account-number"><?php echo htmlspecialchars($setting['account_number']); ?></span>
                                                    <button type="button" class="btn-small blue copy-btn" onclick="copyAccountNumber(this)" 
                                                            data-number="<?php echo htmlspecialchars($setting['account_number']); ?>">
                                                        <i class="material-icons">content_copy</i>
                                                    </button>
                                                </span>
                                            </p>
                                        </div>
                                        <?php if (!empty($setting['instructions'])): ?>
                                            <div class="instructions-text">
                                                <?php echo nl2br(htmlspecialchars($setting['instructions'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="file-field input-field">
                                <div class="btn blue">
                                    <span>Upload Proof</span>
                                    <input type="file" name="payment_proof" accept="image/jpeg,image/png,image/gif" required>
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text" placeholder="Upload your payment proof">
                                </div>
                                <small class="grey-text">Accepted formats: JPG, PNG, GIF (Max size: 5MB)</small>
                            </div>

                            <div class="input-field">
                                <input type="text" id="payment_reference" name="payment_reference" required 
                                       pattern="\d{13}" title="GCash reference number must be exactly 13 digits"
                                       oninput="validatePaymentReference(this)">
                                <label for="payment_reference">Payment Reference Number</label>
                                <span class="helper-text" id="reference-helper"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cancel</a>
            <?php if (!empty($payment_settings)): ?>
                <button type="submit" class="waves-effect waves-light btn blue" id="submit-subscription">
                    <i class="material-icons left">check</i>
                    Confirm Subscription
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<style>
.plans-container {
    padding: 20px;
}
.plan-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    margin: 0.5rem 0 1rem 0;
}
.plan-card .card-content {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    padding: 24px;
}
.card-title {
    font-size: 1.8rem !important;
    font-weight: 600 !important;
    margin-bottom: 1.5rem !important;
    color: #1976d2;
}
.price {
    font-size: 2.5rem;
    color: #1976d2;
    margin: 1.5rem 0;
    font-weight: 600;
}
.duration {
    color: #666;
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}
.features {
    flex-grow: 1;
    margin: 1.5rem 0;
    font-size: 1rem;
    line-height: 1.6;
}
.divider {
    margin: 1.5rem 0;
}
.plan-card .card-action {
    padding: 16px 24px;
    border-top: 1px solid rgba(0,0,0,0.1);
    background-color: #f5f5f5;
}
.plan-card .btn-large {
    width: 100%;
    margin-top: 1rem;
}
.row .col {
    margin-bottom: 20px;
}
@media only screen and (max-width: 600px) {
    .plan-card {
        height: auto;
    }
}
.payment-details {
    margin: 20px 0;
}

.total-amount {
    font-size: 1.2rem;
    font-weight: bold;
    color: #1976D2;
    margin-bottom: 20px;
}

.payment-methods-section {
    margin: 20px 0;
}

.payment-methods-title {
    font-weight: 500;
    color: #333;
    margin-bottom: 10px;
}

.payment-methods {
    margin: 15px 0;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.payment-instructions {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 4px;
    margin: 20px 0;
}

.payment-instruction-section {
    margin: 0;
}

.account-details {
    background: white;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.account-details p {
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.instructions-text {
    color: #666;
    font-style: italic;
    line-height: 1.5;
    padding: 10px;
    border-left: 3px solid #2196F3;
    background: white;
}

.account-number-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.copy-btn {
    padding: 0 8px;
    height: 24px;
    line-height: 24px;
}

.copy-btn i {
    font-size: 16px;
    line-height: 24px;
}

#subscribe-modal {
    width: 90%;
    max-width: 600px;
}

@media only screen and (max-width: 600px) {
    .payment-methods {
        flex-direction: column;
        gap: 10px;
    }
    
    .account-details {
        padding: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('subscribe-modal');
    M.Modal.init(modal);

    // Handle payment method selection
    var paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(function(method) {
        method.addEventListener('change', function() {
            document.querySelectorAll('.payment-instruction-section').forEach(function(section) {
                section.style.display = 'none';
            });
            var selectedMethod = this.getAttribute('data-method');
            document.getElementById(selectedMethod + '-instructions').style.display = 'block';
        });
    });

    function validatePaymentReference(input) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const referenceHelper = document.getElementById('reference-helper');
        
        if (paymentMethod === 'gcash') {
            input.pattern = "\\d{13}";
            input.title = "GCash reference number must be exactly 13 digits";
            
            if (input.value.length > 0) {
                if (!/^\d+$/.test(input.value)) {
                    referenceHelper.textContent = "Reference number should only contain digits";
                    referenceHelper.style.color = "red";
                } else if (input.value.length !== 13) {
                    referenceHelper.textContent = "GCash reference number must be exactly 13 digits";
                    referenceHelper.style.color = "red";
                } else {
                    referenceHelper.textContent = "Valid GCash reference number";
                    referenceHelper.style.color = "green";
                }
            } else {
                referenceHelper.textContent = "";
            }
        } else {
            input.pattern = "";
            input.title = "";
            referenceHelper.textContent = "";
        }
    }

    // Add event listener for payment method change
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const referenceInput = document.getElementById('payment_reference');
            validatePaymentReference(referenceInput);
        });
    });

    // Form validation
    var form = document.getElementById('subscription-form');
    var submitBtn = document.getElementById('submit-subscription');
    
    form.addEventListener('submit', function(e) {
        var paymentProof = document.querySelector('input[name="payment_proof"]');
        var paymentReference = document.querySelector('input[name="payment_reference"]');
        
        if (!paymentProof.files[0]) {
            e.preventDefault();
            M.toast({html: 'Please upload your payment proof', classes: 'red'});
            return;
        }

        if (!paymentReference.value.trim()) {
            e.preventDefault();
            M.toast({html: 'Please enter the payment reference number', classes: 'red'});
            return;
        }

        // File size validation (5MB limit)
        if (paymentProof.files[0].size > 5 * 1024 * 1024) {
            e.preventDefault();
            M.toast({html: 'File size must be less than 5MB', classes: 'red'});
            return;
        }

        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Processing...';
    });
});

function showSubscribeModal(planId, planName, planPrice) {
    document.getElementById('plan-id').value = planId;
    document.getElementById('plan-name').textContent = planName;
    document.getElementById('plan-price').textContent = planPrice;
    
    var modal = M.Modal.getInstance(document.getElementById('subscribe-modal'));
    modal.open();
}

function copyAccountNumber(button) {
    const number = button.getAttribute('data-number');
    navigator.clipboard.writeText(number).then(() => {
        // Temporarily change the icon to indicate success
        const icon = button.querySelector('i');
        icon.textContent = 'check';
        button.classList.remove('blue');
        button.classList.add('green');
        
        // Revert back after 2 seconds
        setTimeout(() => {
            icon.textContent = 'content_copy';
            button.classList.remove('green');
            button.classList.add('blue');
        }, 2000);
        
        M.toast({html: 'Account number copied!', classes: 'green'});
    }).catch(() => {
        M.toast({html: 'Failed to copy account number', classes: 'red'});
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>