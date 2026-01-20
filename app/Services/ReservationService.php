<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\ReservationConflictException;
use App\Exceptions\VehicleNotAvailableException;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::with(['user', 'vehicle'])->paginate($perPage);
    }

    public function getAllWithoutPagination(): Collection
    {
        return Reservation::with(['user', 'vehicle'])->get();
    }

    public function getById(int $id): ?Reservation
    {
        return Reservation::with(['user', 'vehicle'])->find($id);
    }

    public function getByUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::where('user_id', $user->id)
            ->with('vehicle')
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    public function getByVehicle(Vehicle $vehicle, int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::where('vehicle_id', $vehicle->id)
            ->with('user')
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    public function getActiveByVehicle(Vehicle $vehicle): Collection
    {
        return Reservation::where('vehicle_id', $vehicle->id)
            ->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
            ->orderBy('start_date', 'asc')
            ->get();
    }

    public function create(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            if (!$vehicle->isAvailable()) {
                throw new VehicleNotAvailableException('Le véhicule n\'est pas disponible.');
            }

            if (!$this->checkAvailability($vehicle, $startDate, $endDate)) {
                throw new ReservationConflictException('Le véhicule est déjà réservé pour cette période.');
            }

            if (!isset($data['status'])) {
                $data['status'] = ReservationStatus::PENDING;
            }

            $reservation = Reservation::create($data);
            $reservation->load(['user', 'vehicle']);

            return $reservation;
        });
    }

    public function update(Reservation $reservation, array $data): bool
    {
        return DB::transaction(function () use ($reservation, $data) {
            $vehicle = $reservation->vehicle;

            if (isset($data['vehicle_id']) && $data['vehicle_id'] != $reservation->vehicle_id) {
                $vehicle = Vehicle::findOrFail($data['vehicle_id']);
            }

            if (isset($data['start_date']) || isset($data['end_date'])) {
                $startDate = isset($data['start_date'])
                    ? Carbon::parse($data['start_date'])
                    : $reservation->start_date;
                $endDate = isset($data['end_date'])
                    ? Carbon::parse($data['end_date'])
                    : $reservation->end_date;

                if ($reservation->isConfirmed() && !$this->checkAvailability(
                    $vehicle,
                    $startDate,
                    $endDate,
                    $reservation->id
                )) {
                    throw new ReservationConflictException('Le véhicule est déjà réservé pour cette période.');
                }
            }

            return $reservation->update($data);
        });
    }

    public function delete(Reservation $reservation): bool
    {
        return $reservation->delete();
    }

    public function updateStatus(Reservation $reservation, string $status): bool
    {
        if (!ReservationStatus::isValid($status)) {
            throw new \InvalidArgumentException("Le statut '{$status}' n'est pas valide.");
        }

        $currentStatus = $reservation->status;

        if (!ReservationStatus::isValidTransition($currentStatus, $status)) {
            $validTransitions = ReservationStatus::validTransitions($currentStatus);
            $transitionsList = empty($validTransitions) 
                ? 'aucune' 
                : implode(', ', $validTransitions);
            
            throw new InvalidStatusTransitionException(
                "Impossible de passer de '{$currentStatus}' à '{$status}'. " .
                "Transitions valides depuis '{$currentStatus}': {$transitionsList}."
            );
        }

        if ($status === ReservationStatus::CONFIRMED) {
            return DB::transaction(function () use ($reservation, $status) {
                $vehicle = $reservation->vehicle;

                if (!$this->checkAvailability(
                    $vehicle,
                    $reservation->start_date,
                    $reservation->end_date,
                    $reservation->id
                )) {
                    throw new ReservationConflictException('Le véhicule est déjà réservé pour cette période.');
                }

                return $reservation->update(['status' => $status]);
            });
        }

        return $reservation->update(['status' => $status]);
    }

    public function cancel(Reservation $reservation): bool
    {
        return $this->updateStatus($reservation, ReservationStatus::CANCELLED);
    }

    public function confirm(Reservation $reservation): bool
    {
        return $this->updateStatus($reservation, ReservationStatus::CONFIRMED);
    }

    public function complete(Reservation $reservation): bool
    {
        return $this->updateStatus($reservation, ReservationStatus::COMPLETED);
    }

    public function checkAvailability(
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeReservationId = null
    ): bool {
        $query = Reservation::where('vehicle_id', $vehicle->id)
            ->where('status', ReservationStatus::CONFIRMED)
            ->where($this->getOverlapQuery($startDate, $endDate));

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return !$query->exists();
    }

    public function getAvailableVehicles(Carbon $startDate, Carbon $endDate): Collection
    {
        $conflictingReservationIds = Reservation::where('status', ReservationStatus::CONFIRMED)
            ->where($this->getOverlapQuery($startDate, $endDate))
            ->pluck('vehicle_id')
            ->unique();

        return Vehicle::where('status', \App\Enums\VehicleStatus::AVAILABLE)
            ->whereNotIn('id', $conflictingReservationIds)
            ->get();
    }

    private function getOverlapQuery(Carbon $startDate, Carbon $endDate): \Closure
    {
        return function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        };
    }
}
