<?php

namespace App\Services;

use App\Enums\VehicleStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleService
{
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Vehicle::paginate($perPage);
    }

    public function getAllWithoutPagination(): Collection
    {
        return Vehicle::all();
    }

    public function getById(int $id): ?Vehicle
    {
        return Vehicle::find($id);
    }

    public function getByLicensePlate(string $licensePlate): ?Vehicle
    {
        return Vehicle::where('license_plate', $licensePlate)->first();
    }

    public function getAvailable(): Collection
    {
        return Vehicle::where('status', VehicleStatus::AVAILABLE)->get();
    }

    public function create(array $data): Vehicle
    {
        return Vehicle::create($data);
    }

    public function update(Vehicle $vehicle, array $data): bool
    {
        return $vehicle->update($data);
    }

    public function delete(Vehicle $vehicle): bool
    {
        return $vehicle->delete();
    }

    public function updateStatus(Vehicle $vehicle, string $status): bool
    {
        if (!VehicleStatus::isValid($status)) {
            throw new \InvalidArgumentException("Le statut '{$status}' n'est pas valide.");
        }

        $currentStatus = $vehicle->status;

        if (!VehicleStatus::isValidTransition($currentStatus, $status)) {
            $validTransitions = VehicleStatus::validTransitions($currentStatus);
            $transitionsList = empty($validTransitions) 
                ? 'aucune' 
                : implode(', ', $validTransitions);
            
            throw new InvalidStatusTransitionException(
                "Impossible de passer de '{$currentStatus}' à '{$status}'. " .
                "Transitions valides depuis '{$currentStatus}': {$transitionsList}."
            );
        }

        return $vehicle->update(['status' => $status]);
    }
}
