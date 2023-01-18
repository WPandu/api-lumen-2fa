<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserRole extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'role_name' => $this->role_name,
            'reject_reason' => $this->reject_reason,
            'status' => $this->status,
        ];
    }
}
