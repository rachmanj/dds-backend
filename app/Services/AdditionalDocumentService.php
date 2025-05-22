<?php

namespace App\Services;

use App\Repositories\AdditionalDocumentTypeRepository;

class AdditionalDocumentService
{
    private AdditionalDocumentTypeRepository $additionalDocumentTypeRepository;

    public function __construct(AdditionalDocumentTypeRepository $additionalDocumentTypeRepository)
    {
        $this->additionalDocumentTypeRepository = $additionalDocumentTypeRepository;
    }

    public function getAll()
    {
        return $this->additionalDocumentTypeRepository->getAll();
    }

    public function getById($id)
    {
        return $this->additionalDocumentTypeRepository->getById($id);
    }

    public function create(array $data)
    {
        return $this->additionalDocumentTypeRepository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->additionalDocumentTypeRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->additionalDocumentTypeRepository->delete($id);
    }
}
