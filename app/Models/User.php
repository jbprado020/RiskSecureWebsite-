<?php

namespace App\Models;

declare(strict_types=1);

class User
{
    public int $id;
    public string $full_name;
    public string $email;
    public string $role;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;
        $this->full_name = $data['full_name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->role = $data['role'] ?? '';
    }
}
