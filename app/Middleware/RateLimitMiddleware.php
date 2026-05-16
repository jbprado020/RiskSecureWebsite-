<?php

namespace App\Middleware;

use App\Utilities\Database;
use App\Utilities\Response;

declare(strict_types=1);

class RateLimitMiddleware
{
    // Simple IP-based limiter; for now this is a placeholder that will be
    // implemented fully when we create the login_attempts migration and helper.
    public static function check(string $key = 'global'): void
    {
        // TODO: Implement DB-backed rate limiting
        return;
    }
}
