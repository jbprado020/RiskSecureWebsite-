<?php

declare(strict_types=1);

require_once __DIR__ . '/../shared/config/db.php';
require_once __DIR__ . '/../shared/includes/auth.php';
require_once __DIR__ . '/security/gatekeeper.php';
require_once __DIR__ . '/../shared/tools/PaymentGateway.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payments.php');
    exit;
}

requireCsrfToken();

$paymentId = (int) ($_POST['payment_id'] ?? 0);
$clientId = customerClientId();

if ($paymentId <= 0) {
    header('Location: payments.php');
    exit;
}

$pdo = db();

// Ensure the payment belongs to the logged-in client and is pending/overdue
$stmt = $pdo->prepare(
    'SELECT pay.*, p.policy_number 
     FROM payments pay
     INNER JOIN policies p ON p.id = pay.policy_id
     WHERE pay.id = :id AND p.client_id = :client_id AND pay.status IN ("pending", "overdue")'
);
$stmt->execute([':id' => $paymentId, ':client_id' => $clientId]);
$payment = $stmt->fetch();

if (!$payment) {
    // Payment not found or already paid
    header('Location: payments.php?error=invalid_payment');
    exit;
}

// Prepare checkout session
$gateway = new PaymentGateway();
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$successUrl = $protocol . $host . "/customer/payment_success.php";
$cancelUrl = $protocol . $host . "/customer/payments.php?status=cancelled";

try {
    $redirectUrl = $gateway->createCheckoutSession([
        'payment_id' => $paymentId,
        'amount' => (float) $payment['amount'],
        'currency' => 'PHP',
        'description' => 'Insurance Premium for Policy ' . $payment['policy_number'],
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl
    ]);

    header('Location: ' . $redirectUrl);
    exit;
} catch (Exception $e) {
    header('Location: payments.php?error=gateway_error');
    exit;
}
