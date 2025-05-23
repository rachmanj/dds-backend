<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'nik' => $this->nik,
            'project' => $this->project,
            'department' => $this->department ? $this->department->name : null,
            'is_active' => $this->is_active ?? true,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }, []),
            'permissions' => $this->when(method_exists($this, 'getAllPermissions'), function () {
                return $this->getAllPermissions()->pluck('name');
            }, []),
        ];
    }
}
