<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'image'         => $this->image,
            'branch_id'     => $this->branch_id,
            'status'        => $this->status,
            'roles'         => $this->getRoleNames(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
