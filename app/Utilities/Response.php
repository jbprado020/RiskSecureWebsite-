<?php

namespace App\Utilities;

declare(strict_types=1);

class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
