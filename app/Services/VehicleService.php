<?php

namespace App\Services;

use App\Enums\VehicleStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class VehicleService
{
    private const CACHE_KEY_AVAILABLE_VEHICLES = 'vehicles:available';
    private const CACHE_TTL = 1800; // 30 minutes (plus court car les véhicules peuvent changer de statut plus souvent)

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
        return Cache::remember(self::CACHE_KEY_AVAILABLE_VEHICLES, self::CACHE_TTL, function () {
            return Vehicle::where('status', VehicleStatus::AVAILABLE)->get();
        });
    }

    public function create(array $data): Vehicle
    {
        $vehicle = Vehicle::create($data);
        $this->clearAvailableCache();
        
        return $vehicle;
    }

    public function update(Vehicle $vehicle, array $data): bool
    {
        $result = $vehicle->update($data);
        
        // Invalide le cache si le statut ou d'autres données importantes ont été modifiées
        if (isset($data['status']) || array_key_exists('status', $data)) {
            $this->clearAvailableCache();
        }
        
        return $result;
    }

    public function delete(Vehicle $vehicle): bool
    {
        $result = $vehicle->delete();
        $this->clearAvailableCache();
        
        return $result;
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

        $result = $vehicle->update(['status' => $status]);
        $this->clearAvailableCache();
        
        return $result;
    }

    /**
     * Invalide le cache des véhicules disponibles
     */
    private function clearAvailableCache(): void
    {
        Cache::forget(self::CACHE_KEY_AVAILABLE_VEHICLES);
    }
}
