<?php

namespace App\Services;

use App\Utilities\Database;
use App\Utilities\Session;

declare(strict_types=1);

class AuthService
{
    public function staffLogin(string $email, string $password): array
    {
        $pdo = Database::getPdo();
        $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role, is_active FROM staff_accounts WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        if (!password_verify($password, $row['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        if ((int)$row['is_active'] !== 1) {
            return ['success' => false, 'message' => 'Account inactive.'];
        }

        Session::set('staff_id', (int)$row['id']);
        Session::set('staff_name', $row['full_name']);
        Session::set('staff_role', $row['role']);

        return ['success' => true, 'message' => 'Login successful.'];
    }

    public function logout(): void
    {
        Session::destroy();
    }
}
