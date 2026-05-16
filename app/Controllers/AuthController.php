<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Utilities\Request;
use App\Utilities\Response;

declare(strict_types=1);

class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function staffLogin(): void
    {
        $email = (string) Request::input('email', '');
        $password = (string) Request::input('password', '');

        $result = $this->auth->staffLogin($email, $password);
        if ($result['success']) {
            Response::redirect('/index.php');
        }

        // in-progress: render original page or return error
        echo $result['message'];
    }

    public function staffLogout(): void
    {
        $this->auth->logout();
        Response::redirect('/staff_login.php');
    }
}
