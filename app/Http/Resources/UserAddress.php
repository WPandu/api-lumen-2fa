<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAddress extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_primary' => $this->is_primary,
            'sequence' => $this->sequence,
        ];
    }
}
