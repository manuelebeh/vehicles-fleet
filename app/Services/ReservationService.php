<?php

namespace App\Services;

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
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('start_date', 'asc')
            ->get();
    }

    public function create(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $reservation = Reservation::create($data);
            $reservation->load(['user', 'vehicle']);

            return $reservation;
        });
    }

    public function update(Reservation $reservation, array $data): bool
    {
        return DB::transaction(function () use ($reservation, $data) {
            return $reservation->update($data);
        });
    }

    public function delete(Reservation $reservation): bool
    {
        return $reservation->delete();
    }

    public function cancel(Reservation $reservation): bool
    {
        return $reservation->update(['status' => 'cancelled']);
    }

    public function confirm(Reservation $reservation): bool
    {
        return $reservation->update(['status' => 'confirmed']);
    }

    public function complete(Reservation $reservation): bool
    {
        return $reservation->update(['status' => 'completed']);
    }

    public function checkAvailability(
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeReservationId = null
    ): bool {
        $query = Reservation::where('vehicle_id', $vehicle->id)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return !$query->exists();
    }

    public function getAvailableVehicles(Carbon $startDate, Carbon $endDate): Collection
    {
        $conflictingReservationIds = Reservation::where('status', 'confirmed')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->pluck('vehicle_id')
            ->unique();

        return Vehicle::where('status', 'available')
            ->whereNotIn('id', $conflictingReservationIds)
            ->get();
    }
}
