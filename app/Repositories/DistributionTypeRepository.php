<?php

namespace App\Repositories;

use App\Models\DistributionType;

class DistributionTypeRepository
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAll(array $fields = ['*'])
    {
        return DistributionType::select($fields)
            ->orderBy('priority')
            ->get();
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return DistributionType::select($fields)->findOrFail($id);
    }

    public function getByCode(string $code, array $fields = ['*'])
    {
        return DistributionType::select($fields)
            ->where('code', $code)
            ->first();
    }

    public function create(array $data)
    {
        return DistributionType::create($data);
    }

    public function update(int $id, array $data)
    {
        $distributionType = DistributionType::findOrFail($id);
        $distributionType->update($data);
        return $distributionType;
    }

    public function delete(int $id)
    {
        $distributionType = DistributionType::findOrFail($id);
        $distributionType->delete();
    }

    public function validateCode(string $code, ?int $typeId = null): bool
    {
        $query = DistributionType::where('code', $code);

        if ($typeId) {
            $query->where('id', '!=', $typeId);
        }

        return !$query->exists();
    }
}
