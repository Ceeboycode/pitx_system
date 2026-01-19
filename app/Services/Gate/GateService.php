<?php

namespace App\Services\Gate;

use App\Models\Gate;

class GateService
{
    public function createGate(array $data): Gate
    {
        $data['created_by'] = auth()->id();

        return Gate::create($data);
    }

    public function updateGate(Gate $gate, array $data): Gate
    {
        $data['updated_by'] = auth()->id();

        $gate->update($data);

        return $gate;
    }

    public function deleteGate(Gate $gate): bool
    {
        return $gate->delete();
    }

    public function restoreGate(Gate $gate): bool
    {
        return $gate->restore();
    }

    public function forceDeleteGate(Gate $gate): bool
    {
        return $gate->forceDelete();
    }
}
