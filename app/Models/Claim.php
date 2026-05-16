<?php

namespace App\Models;

declare(strict_types=1);

class Claim
{
    public int $id;
    public int $policy_id;
    public string $status;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;
        $this->policy_id = isset($data['policy_id']) ? (int)$data['policy_id'] : 0;
        $this->status = $data['status'] ?? '';
    }
}
