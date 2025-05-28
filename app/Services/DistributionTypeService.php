<?php

namespace App\Services;

use App\Repositories\DistributionTypeRepository;

class DistributionTypeService
{
    protected DistributionTypeRepository $distributionTypeRepository;

    /**
     * Create a new class instance.
     */
    public function __construct(DistributionTypeRepository $distributionTypeRepository)
    {
        $this->distributionTypeRepository = $distributionTypeRepository;
    }

    public function getAll(array $fields = ['*'])
    {
        return $this->distributionTypeRepository->getAll($fields);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return $this->distributionTypeRepository->getById($id, $fields);
    }

    public function getByCode(string $code, array $fields = ['*'])
    {
        return $this->distributionTypeRepository->getByCode($code, $fields);
    }

    public function create(array $data)
    {
        // Validate code uniqueness
        if (!$this->distributionTypeRepository->validateCode($data['code'])) {
            throw new \InvalidArgumentException('Distribution type code already exists.');
        }

        return $this->distributionTypeRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        try {
            // Validate code uniqueness if code is being updated
            if (isset($data['code']) && !$this->distributionTypeRepository->validateCode($data['code'], $id)) {
                throw new \InvalidArgumentException('Distribution type code already exists.');
            }

            return $this->distributionTypeRepository->update($id, $data);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            // Check if distribution type is being used
            $distributionType = $this->distributionTypeRepository->getById($id);
            if ($distributionType->distributions()->count() > 0) {
                throw new \InvalidArgumentException('Cannot delete distribution type that is being used by distributions.');
            }

            $this->distributionTypeRepository->delete($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validateCode(string $code, ?int $typeId = null): bool
    {
        return $this->distributionTypeRepository->validateCode($code, $typeId);
    }
}
