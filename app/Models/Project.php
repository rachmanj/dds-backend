<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['code', 'owner', 'location'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
