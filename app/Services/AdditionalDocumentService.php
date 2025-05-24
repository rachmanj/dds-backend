<?php

namespace App\Services;

use App\Repositories\AdditionalDocumentRepository;

class AdditionalDocumentService
{
    protected AdditionalDocumentRepository $additionalDocumentRepository;

    public function __construct(AdditionalDocumentRepository $additionalDocumentRepository)
    {
        $this->additionalDocumentRepository = $additionalDocumentRepository;
    }

    public function getAll(array $fields = ['*'], int $perPage = 15)
    {
        return $this->additionalDocumentRepository->getAll($fields, $perPage);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return $this->additionalDocumentRepository->getById($id, $fields);
    }

    public function create(array $data)
    {
        return $this->additionalDocumentRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->additionalDocumentRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->additionalDocumentRepository->delete($id);
    }
}
