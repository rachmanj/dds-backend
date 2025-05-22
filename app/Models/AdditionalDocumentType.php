<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalDocumentType extends Model
{
    protected $fillable = ['type_name'];

    public function additionalDocuments()
    {
        return $this->hasMany(AdditionalDocument::class);
    }
}
