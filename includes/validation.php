<?php

declare(strict_types=1);

function isValidDate(string $value, string $format = 'Y-m-d'): bool
{
    $value = trim($value);
    if ($value === '') {
        return false;
    }

    $dt = DateTime::createFromFormat($format, $value);
    return $dt instanceof DateTime && $dt->format($format) === $value;
}

function parseDateTimeLocal(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    if (!$dt) {
        return null;
    }

    return $dt->format('Y-m-d H:i:s');
}

function isValidEmail(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePasswordStrength(string $password): ?string
{
    $password = trim($password);

    if (strlen($password) < 12) {
        return 'Password must be at least 12 characters long.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }

    if (!preg_match('/\d/', $password)) {
        return 'Password must contain at least one number.';
    }

    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.\/\<\>?\\|`~]/', $password)) {
        return 'Password must contain at least one special character (!@#$%^&*, etc).';
    }

    return null;
}
