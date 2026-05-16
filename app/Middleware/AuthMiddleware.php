<?php

namespace App\Middleware;

use App\Utilities\Session;
use App\Utilities\Response;

declare(strict_types=1);

class AuthMiddleware
{
    public static function requireStaff(): void
    {
        $id = Session::get('staff_id');
        if (empty($id)) {
            Response::redirect('/staff_login.php');
        }
    }
}
