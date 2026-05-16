<?php

namespace App\Validators;

declare(strict_types=1);

class PasswordValidator
{
    public static function validate(string $password): ?string
    {
        $password = trim($password);
        if (strlen($password) < 12) {
            return 'Password too short';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must include uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must include lowercase letter';
        }
        if (!preg_match('/\d/', $password)) {
            return 'Password must include a number';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=[\]{};:\'\",.\/<>?\\|`~]/', $password)) {
            return 'Password must include a special character';
        }
        return null;
    }
}
