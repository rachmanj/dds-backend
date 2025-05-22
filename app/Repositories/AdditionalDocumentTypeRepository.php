<?php

namespace App\Repositories;

use App\Models\AdditionalDocumentType;

class AdditionalDocumentTypeRepository
{
    public function getAll()
    {
        return AdditionalDocumentType::orderBy('type_name', 'asc')->get();
    }

    public function getById($id)
    {
        return AdditionalDocumentType::findOrFail($id);
    }

    public function create(array $data)
    {
        return AdditionalDocumentType::create($data);
    }

    public function update($id, array $data)
    {
        $additionalDocumentType = AdditionalDocumentType::findOrFail($id);
        $additionalDocumentType->update($data);

        return $additionalDocumentType;
    }

    public function delete($id)
    {
        $additionalDocumentType = AdditionalDocumentType::findOrFail($id);
        $additionalDocumentType->delete();
    }
} 