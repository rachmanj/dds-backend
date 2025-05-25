<?php

namespace App\Services;

use App\Repositories\AdditionalDocumentTypeRepository;

class AdditionalDocumentTypeService
{
    protected AdditionalDocumentTypeRepository $additionalDocumentTypeRepository;

    public function __construct(AdditionalDocumentTypeRepository $additionalDocumentTypeRepository)
    {
        $this->additionalDocumentTypeRepository = $additionalDocumentTypeRepository;
    }

    public function getAll()
    {
        return $this->additionalDocumentTypeRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->additionalDocumentTypeRepository->getById($id);
    }

    public function create(array $data)
    {
        return $this->additionalDocumentTypeRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->additionalDocumentTypeRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->additionalDocumentTypeRepository->delete($id);
    }
}
