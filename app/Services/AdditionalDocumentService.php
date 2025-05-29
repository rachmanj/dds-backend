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
        $user = request()->user();
        $data['created_by'] = $user->id;

        // Set default cur_loc to user's department location_code if not provided
        if (empty($data['cur_loc']) && $user->department && $user->department->location_code) {
            $data['cur_loc'] = $user->department->location_code;
        }

        return $this->additionalDocumentRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        $user = request()->user();

        // Check if user is trying to edit cur_loc
        if (isset($data['cur_loc'])) {
            // Check if user has permission to edit cur_loc
            if (!$user->can('document.edit-cur_loc')) {
                // Remove cur_loc from data if user doesn't have permission
                unset($data['cur_loc']);
            }
        }

        return $this->additionalDocumentRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->additionalDocumentRepository->delete($id);
    }
}
