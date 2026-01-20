<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Obtenir le nombre de réservations par mois
     *
     * @param  int  $months
     * @return array
     */
    public function getReservationsByMonth(int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        
        // Approche compatible multi-SGBD
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            $monthExpression = DB::raw('DATE_FORMAT(start_date, "%Y-%m") as month');
        } elseif ($driver === 'pgsql') {
            $monthExpression = DB::raw("TO_CHAR(start_date, 'YYYY-MM') as month");
        } else {
            // SQLite et autres : utilise strftime
            $monthExpression = DB::raw("strftime('%Y-%m', start_date) as month");
        }
        
        $reservations = Reservation::select(
            $monthExpression,
            DB::raw('COUNT(*) as count')
        )
        ->where('start_date', '>=', $startDate)
        ->groupBy('month')
        ->orderBy('month', 'asc')
        ->get();

        // Remplit les mois manquants avec 0
        $result = [];
        $current = $startDate->copy();
        $end = Carbon::now()->endOfMonth();

        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            $reservation = $reservations->firstWhere('month', $monthKey);
            $result[] = [
                'month' => $monthKey,
                'month_label' => $current->format('F Y'),
                'count' => $reservation ? (int) $reservation->count : 0,
            ];
            $current->addMonth();
        }

        return $result;
    }

    /**
     * Obtenir les véhicules les plus utilisés
     *
     * @param  int  $limit
     * @return array
     */
    public function getMostUsedVehicles(int $limit = 10): array
    {
        $cancelledStatus = ReservationStatus::CANCELLED;
        
        $vehicles = Vehicle::select(
            'vehicles.id',
            'vehicles.brand',
            'vehicles.model',
            'vehicles.license_plate',
            DB::raw('COUNT(CASE WHEN reservations.status != ? THEN 1 END) as reservation_count')
        )
        ->leftJoin('reservations', 'vehicles.id', '=', 'reservations.vehicle_id')
        ->groupBy('vehicles.id', 'vehicles.brand', 'vehicles.model', 'vehicles.license_plate')
        ->orderBy('reservation_count', 'desc')
        ->limit($limit)
        ->addBinding($cancelledStatus, 'select')
        ->get();

        return $vehicles->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'license_plate' => $vehicle->license_plate,
                'full_name' => "{$vehicle->brand} {$vehicle->model}",
                'reservation_count' => (int) $vehicle->reservation_count,
            ];
        })->toArray();
    }

    /**
     * Calculer le taux d'occupation des véhicules
     *
     * @param  \Carbon\Carbon|null  $startDate
     * @param  \Carbon\Carbon|null  $endDate
     * @return array
     */
    public function getVehicleOccupancyRate(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $totalDays = $startDate->diffInDays($endDate) + 1;

        $vehicles = Vehicle::select('id', 'brand', 'model', 'license_plate')
            ->with(['reservations' => function ($query) use ($startDate, $endDate) {
                $query->select('id', 'vehicle_id', 'start_date', 'end_date')
                    ->where('status', ReservationStatus::CONFIRMED)
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($subQ) use ($startDate, $endDate) {
                                $subQ->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    });
            }])
            ->get();

        $result = [];

        foreach ($vehicles as $vehicle) {
            $occupiedDays = 0;

            foreach ($vehicle->reservations as $reservation) {
                $reservationStart = max($reservation->start_date, $startDate)->startOfDay();
                $reservationEnd = min($reservation->end_date, $endDate)->endOfDay();
                $occupiedDays += (int) ($reservationStart->diffInDays($reservationEnd) + 1);
            }

            $occupancyRate = $totalDays > 0 ? ($occupiedDays / $totalDays) * 100 : 0;

            $result[] = [
                'vehicle_id' => $vehicle->id,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'license_plate' => $vehicle->license_plate,
                'full_name' => "{$vehicle->brand} {$vehicle->model}",
                'occupied_days' => $occupiedDays,
                'total_days' => $totalDays,
                'occupancy_rate' => (float) round($occupancyRate, 2),
            ];
        }

        usort($result, function ($a, $b) {
            return (float) $b['occupancy_rate'] <=> (float) $a['occupancy_rate'];
        });

        return $result;
    }

    /**
     * Obtenir des statistiques générales
     *
     * @return array
     */
    public function getGeneralStatistics(): array
    {
        $totalVehicles = Vehicle::count();
        $availableVehicles = Vehicle::where('status', \App\Enums\VehicleStatus::AVAILABLE)->count();
        $maintenanceVehicles = Vehicle::where('status', \App\Enums\VehicleStatus::MAINTENANCE)->count();
        $outOfServiceVehicles = Vehicle::where('status', \App\Enums\VehicleStatus::OUT_OF_SERVICE)->count();

        $totalReservations = Reservation::count();
        $pendingReservations = Reservation::where('status', ReservationStatus::PENDING)->count();
        $confirmedReservations = Reservation::where('status', ReservationStatus::CONFIRMED)->count();
        $completedReservations = Reservation::where('status', ReservationStatus::COMPLETED)->count();
        $cancelledReservations = Reservation::where('status', ReservationStatus::CANCELLED)->count();

        $activeReservations = Reservation::whereIn('status', [
            ReservationStatus::PENDING,
            ReservationStatus::CONFIRMED,
        ])->count();

        return [
            'vehicles' => [
                'total' => $totalVehicles,
                'available' => $availableVehicles,
                'maintenance' => $maintenanceVehicles,
                'out_of_service' => $outOfServiceVehicles,
            ],
            'reservations' => [
                'total' => $totalReservations,
                'pending' => $pendingReservations,
                'confirmed' => $confirmedReservations,
                'completed' => $completedReservations,
                'cancelled' => $cancelledReservations,
                'active' => $activeReservations,
            ],
        ];
    }
}
