<?php

namespace App\Repositories;

use App\Models\AdditionalDocument;

class AdditionalDocumentRepository
{
    public function getAll(array $fields = ['*'], int $perPage = 15)
    {
        return AdditionalDocument::select($fields)
            ->latest()
            ->paginate($perPage);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return AdditionalDocument::select($fields)->find($id);
    }

    public function create(array $data)
    {
        return AdditionalDocument::create($data);
    }

    public function update(int $id, array $data)
    {
        $document = AdditionalDocument::findOrFail($id);
        $document->update($data);
        return $document;
    }

    public function delete(int $id)
    {
        return AdditionalDocument::findOrFail($id)->delete();
    }
} 