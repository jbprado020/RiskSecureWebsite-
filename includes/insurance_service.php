<?php

declare(strict_types=1);

function calculatePremium(float $coverageAmount, string $policyType, string $riskLevel, int $termMonths): float
{
    $baseRateByPolicy = [
        'life' => 0.015,
        'non-life' => 0.025,
    ];

    $riskMultiplier = [
        'low' => 1.0,
        'medium' => 1.2,
        'high' => 1.5,
    ];

    $baseRate = $baseRateByPolicy[$policyType] ?? 0.02;
    $risk = $riskMultiplier[$riskLevel] ?? 1.2;
    $termFactor = max($termMonths, 1) / 12;

    $premium = $coverageAmount * $baseRate * $risk * $termFactor;

    return round($premium, 2);
}

function generatePolicyNumber(): string
{
    return 'RS-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
}
