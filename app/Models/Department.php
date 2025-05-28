<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'project',
        'location_code',
        'transit_code',
        'akronim',
        'sap_code'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // Distribution relationships
    public function originDistributions(): HasMany
    {
        return $this->hasMany(Distribution::class, 'origin_department_id');
    }

    public function destinationDistributions(): HasMany
    {
        return $this->hasMany(Distribution::class, 'destination_department_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
