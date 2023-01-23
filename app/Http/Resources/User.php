<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'phone' => $this->phone,
            'is_admin' => $this->is_admin,
            'is_contributor' => $this->is_contributor,
            'is_customer' => $this->is_customer,
            'is_active' => $this->is_active,
            'photo_url' => $this->photo_url,
            'address' => new UserAddress($this->addresses()->primary()->first()),
            'addresses' => UserAddress::collection($this->addresses),
            'roles' => UserRole::collection($this->roles),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
