<?php

declare(strict_types=1);

require_once __DIR__ . '/../shared/config/db.php';
require_once __DIR__ . '/../shared/includes/layout.php';
require_once __DIR__ . '/../shared/includes/auth.php';
require_once __DIR__ . '/security/gatekeeper.php';
require_once __DIR__ . '/../shared/tools/PaymentGateway.php';

requireCustomerLogin();

$sessionId = (string) ($_GET['session_id'] ?? '');
$paymentId = (int) ($_GET['payment_id'] ?? 0);
$clientId = customerClientId();

if ($sessionId === '' || $paymentId <= 0) {
    header('Location: payments.php');
    exit;
}

$pdo = db();
$gateway = new PaymentGateway();

try {
    // In a real implementation, we verify the session with the gateway
    $verification = $gateway->verifyPayment($sessionId);

    if ($verification['status'] === 'succeeded') {
        // Update the payment record
        $stmt = $pdo->prepare(
            'UPDATE payments SET 
                status = "paid", 
                paid_date = :paid_date, 
                transaction_id = :transaction_id, 
                payment_method = :payment_method,
                gateway_metadata = :metadata
             WHERE id = :id AND status != "paid"'
        );
        $stmt->execute([
            ':paid_date' => date('Y-m-d'),
            ':transaction_id' => $verification['transaction_id'],
            ':payment_method' => $verification['payment_method'],
            ':metadata' => json_encode($verification['raw_response']),
            ':id' => $paymentId
        ]);
        
        $message = "Payment successful! Your transaction has been recorded.";
        $type = "ok";
    } else {
        $message = "Payment verification failed. Please contact support.";
        $type = "error";
    }
} catch (Exception $e) {
    $message = "An error occurred during payment verification.";
    $type = "error";
}

renderHeader('Payment Status');
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1>Payment Status</h1>
        <p>Thank you for choosing RiskSecure.</p>
    </div>
</div>

<section class="card" style="text-align: center; padding: 3rem;">
    <?php if ($type === 'ok'): ?>
        <div style="color: var(--success); margin-bottom: 1.5rem;">
            <svg style="width: 64px; height: 64px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
    <?php else: ?>
        <div style="color: var(--danger); margin-bottom: 1.5rem;">
            <svg style="width: 64px; height: 64px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
    <?php endif; ?>

    <h2><?= $type === 'ok' ? 'Success!' : 'Oops!'; ?></h2>
    <p style="font-size: 1.1rem; margin-bottom: 2rem;"><?= e($message); ?></p>
    
    <a href="payments.php" class="button">Back to Payments</a>
</section>

<?php
renderFooter();
