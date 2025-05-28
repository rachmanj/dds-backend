<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'color',
        'priority',
        'description'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'priority' => 'integer'
    ];

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class, 'type_id');
    }
}
