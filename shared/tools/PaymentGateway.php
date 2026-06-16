<?php

declare(strict_types=1);

/**
 * PaymentGateway Class
 * 
 * Handles interaction with online payment providers (e.g., PayMongo, Stripe).
 * Currently configured with a Mock implementation for demonstration.
 */
class PaymentGateway
{
    private string $apiKey;
    private bool $isMock;

    public function __construct(string $apiKey = '', bool $isMock = true)
    {
        $this->apiKey = $apiKey;
        $this->isMock = $isMock;
    }

    /**
     * Create a checkout session and return the redirect URL.
     */
    public function createCheckoutSession(array $data): string
    {
        if ($this->isMock) {
            // In a real implementation, this would call the API (e.g., PayMongo/Stripe)
            // and return a real hosted checkout URL.
            
            // Mocking a successful response
            $paymentId = $data['payment_id'];
            $successUrl = $data['success_url'] . "?session_id=mock_session_" . bin2hex(random_bytes(8)) . "&payment_id=" . $paymentId;
            
            // For mock purposes, we just return our success page directly as if it were the gateway.
            return $successUrl;
        }

        // Real API implementation would go here
        throw new Exception("Real API implementation not configured.");
    }

    /**
     * Verify a payment session/transaction.
     */
    public function verifyPayment(string $sessionId): array
    {
        if ($this->isMock) {
            return [
                'status' => 'succeeded',
                'transaction_id' => 'TXN_' . strtoupper(bin2hex(random_bytes(4))),
                'payment_method' => 'GCash (Mock)',
                'raw_response' => ['mock' => true]
            ];
        }

        return [];
    }
}
