<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Require user login
require_user_login();

// Get user data
$user = get_user_data();

if ($user === null) {
    redirect('index.php');
}

// Check if user is a winner
$winner_info = is_winner($user['id']);

if (!$winner_info['is_winner']) {
    redirect('results.php');
}

// Check if payment is enabled
if (!PAYMENT_ENABLED) {
    $payment_disabled_message = "Payment portal is currently disabled. Please check back later.";
}

// Get existing payment details
$payment = get_payment_status($user['id']);
$prize_amount = get_prize_amount($winner_info['rank']);

// Get prize position name
$position_names = [
    1 => '🥇 1st Place',
    2 => '🥈 2nd Place',
    3 => '🥉 3rd Place'
];
$position = $position_names[$winner_info['rank']] ?? 'Winner';

$page_title = "Winner Payment Portal";
?>

<?php include 'includes/header.php'; ?>

<div class="winner-portal-page min-vh-100">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Winner Banner -->
                <div class="card shadow-lg border-0 mb-4" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-trophy-fill display-1 text-white mb-3"></i>
                        <h2 class="fw-bold text-white mb-2">Congratulations! 🎉</h2>
                        <h3 class="text-white mb-3"><?php echo $position; ?></h3>
                        <h1 class="display-3 fw-bold text-white">₦<?php echo number_format($prize_amount, 2); ?></h1>
                        <p class="text-white fs-5 mb-0">Prize Money</p>
                    </div>
                </div>
                
                <?php if (isset($payment_disabled_message)): ?>
                    <!-- Payment Disabled -->
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <?php echo $payment_disabled_message; ?>
                    </div>
                <?php elseif ($payment): ?>
                    <!-- Bank Details Submitted -->
                    <div class="card bg-dark border-success shadow-lg">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-check-circle-fill me-2"></i>Bank Details Submitted
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="text-light mb-1"><strong>Bank Name:</strong></p>
                                    <p class="text-white"><?php echo htmlspecialchars($payment['bank_name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-light mb-1"><strong>Account Number:</strong></p>
                                    <p class="text-white"><?php echo htmlspecialchars($payment['account_number']); ?></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="text-light mb-1"><strong>Account Name:</strong></p>
                                    <p class="text-white"><?php echo htmlspecialchars($payment['account_name']); ?></p>
                                </div>
                            </div>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-clock-fill me-2"></i>
                                <strong>Details received!</strong> Your payment will be sent within 10-30 minutes.
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Payment Form -->
                    <div class="card bg-dark border-success shadow-lg">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-bank me-2"></i>Submit Bank Details
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Important:</strong> Please provide accurate bank details. Your payment will be sent within 10-30 minutes after submission.
                            </div>
                            
                            <div id="alertContainer"></div>
                            
                            <form id="paymentForm">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="rank" value="<?php echo $winner_info['rank']; ?>">
                                <input type="hidden" name="prize_amount" value="<?php echo $prize_amount; ?>">
                                
                                <div class="mb-4">
                                    <label for="bank_name" class="form-label text-light">
                                        <i class="bi bi-building me-2"></i>Bank Name *
                                    </label>
                                    <select class="form-select form-select-lg" id="bank_name" name="bank_name" required>
                                        <option value="">-- Select Your Bank --</option>
                                        <?php foreach (get_nigerian_banks() as $bank): ?>
                                            <option value="<?php echo htmlspecialchars($bank); ?>">
                                                <?php echo htmlspecialchars($bank); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="account_number" class="form-label text-light">
                                        <i class="bi bi-credit-card me-2"></i>Account Number *
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control form-control-lg" 
                                        id="account_number" 
                                        name="account_number" 
                                        placeholder="1234567890"
                                        required
                                        pattern="[0-9]{10}"
                                        maxlength="10"
                                        title="Account number must be 10 digits"
                                    >
                                    <small class="text-light">Enter your 10-digit account number</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="account_name" class="form-label text-light">
                                        <i class="bi bi-person me-2"></i>Account Name *
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control form-control-lg" 
                                        id="account_name" 
                                        name="account_name" 
                                        placeholder="Full name as registered with bank"
                                        required
                                        maxlength="100"
                                    >
                                    <small class="text-light">Enter the name on your bank account</small>
                                </div>
                                
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="confirm_details" required>
                                    <label class="form-check-label text-light" for="confirm_details">
                                        I confirm that the above bank details are correct. I understand that incorrect details may result in payment delays.
                                    </label>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                        <i class="bi bi-send-fill me-2"></i>Submit Bank Details
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Footer -->
                <div class="text-center mt-4">
                    <a href="results.php" class="btn btn-outline-light">
                        <i class="bi bi-arrow-left me-2"></i>Back to Results
                    </a>
                    <a href="leaderboard.php" class="btn btn-outline-light ms-2">
                        <i class="bi bi-trophy me-2"></i>View Leaderboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="assets/js/winner-portal.js"></script>';
include 'includes/footer.php';
?>