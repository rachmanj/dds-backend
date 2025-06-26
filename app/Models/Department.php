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

    /**
     * Get document locations for this department
     */
    public function documentLocations(): HasMany
    {
        return $this->hasMany(DocumentLocation::class, 'location_code', 'location_code');
    }

    /**
     * Get documents currently at this location
     */
    public function documentsAtLocation()
    {
        return $this->documentLocations()
            ->whereRaw('moved_at = (SELECT MAX(moved_at) FROM document_locations dl2 WHERE dl2.document_type = document_locations.document_type AND dl2.document_id = document_locations.document_id)');
    }
}
