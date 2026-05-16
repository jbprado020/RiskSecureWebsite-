<?php

namespace App\Utilities;

declare(strict_types=1);

class Request
{
    public static function input(string $key, $default = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $_POST[$key] ?? $default;
        }

        return $_GET[$key] ?? $default;
    }

    public static function file(string $key)
    {
        return $_FILES[$key] ?? null;
    }

    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
}
