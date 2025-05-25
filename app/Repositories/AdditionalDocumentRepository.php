<?php

namespace App\Repositories;

use App\Models\AdditionalDocument;

class AdditionalDocumentRepository
{
    public function getAll(array $fields = ['*'], int $perPage = 15)
    {
        return AdditionalDocument::select($fields)
            ->with(['type', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return AdditionalDocument::select($fields)
            ->with(['type', 'creator'])
            ->find($id);
    }

    public function create(array $data)
    {
        $document = AdditionalDocument::create($data);
        return $document->load(['type', 'creator']);
    }

    public function update(int $id, array $data)
    {
        $document = AdditionalDocument::findOrFail($id);
        $document->update($data);
        return $document->load(['type', 'creator']);
    }

    public function delete(int $id)
    {
        return AdditionalDocument::findOrFail($id)->delete();
    }
}
