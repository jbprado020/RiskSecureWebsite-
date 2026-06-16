<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../tools/PaymentGateway.php';

/**
 * Payment Webhook Handler
 * 
 * This endpoint is called by the payment gateway (e.g., PayMongo/Stripe) 
 * asynchronously to notify the system of successful payments.
 */

// In a real implementation, you would verify the signature here.
// $payload = file_get_contents('php://input');
// $signature = $_SERVER['HTTP_X_PAYMONGO_SIGNATURE'] ?? '';

// For this educational system, we'll implement the logic structure.

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    exit('Invalid payload');
}

// Logic to identify payment and update status
// Example for a generic gateway notification:
if (isset($input['type']) && $input['type'] === 'payment.paid') {
    $transactionId = $input['data']['id'];
    $paymentId = $input['data']['attributes']['external_id']; // We passed this during session creation
    
    $stmt = $pdo->prepare(
        'UPDATE payments SET 
            status = "paid", 
            paid_date = :paid_date, 
            transaction_id = :transaction_id,
            payment_method = :method
         WHERE id = :id AND status != "paid"'
    );
    
    $stmt->execute([
        ':paid_date' => date('Y-m-d'),
        ':transaction_id' => $transactionId,
        ':method' => $input['data']['attributes']['source']['type'] ?? 'online',
        ':id' => $paymentId
    ]);
}

http_response_code(200);
echo 'Webhook processed';
