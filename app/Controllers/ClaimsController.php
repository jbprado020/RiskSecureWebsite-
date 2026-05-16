<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Utilities\Request;
use App\Utilities\Response;

declare(strict_types=1);

class ClaimsController
{
    public function approve(): void
    {
        // Placeholder: integrate with ClaimsService in next pass
        Response::redirect('/claims.php');
    }
}
