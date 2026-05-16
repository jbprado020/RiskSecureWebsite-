<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Utilities\Request;
use App\Utilities\Response;

declare(strict_types=1);

class StaffController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function create(): void
    {
        // Placeholder: later will accept validated input and call service
        $fullName = (string) Request::input('full_name', '');
        // For now, reuse existing pages until we wire router
        Response::redirect('/staff_management.php');
    }
}
