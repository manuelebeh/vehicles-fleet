<?php

namespace Tests\Unit\Services;

use App\Enums\ReservationStatus;
use App\Enums\VehicleStatus;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private StatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatisticsService();
    }

    public function test_get_general_statistics_returns_correct_counts(): void
    {
        Vehicle::factory()->count(5)->available()->create();
        Vehicle::factory()->count(3)->maintenance()->create();
        Vehicle::factory()->count(2)->outOfService()->create();

        Reservation::factory()->count(10)->pending()->create();
        Reservation::factory()->count(5)->confirmed()->create();
        Reservation::factory()->count(3)->completed()->create();
        Reservation::factory()->count(2)->cancelled()->create();

        $stats = $this->service->getGeneralStatistics();

        $this->assertGreaterThanOrEqual(10, $stats['vehicles']['total']);
        $this->assertGreaterThanOrEqual(5, $stats['vehicles']['available']);
        $this->assertGreaterThanOrEqual(3, $stats['vehicles']['maintenance']);
        $this->assertGreaterThanOrEqual(2, $stats['vehicles']['out_of_service']);

        $this->assertGreaterThanOrEqual(20, $stats['reservations']['total']);
        $this->assertGreaterThanOrEqual(10, $stats['reservations']['pending']);
        $this->assertGreaterThanOrEqual(5, $stats['reservations']['confirmed']);
        $this->assertGreaterThanOrEqual(3, $stats['reservations']['completed']);
        $this->assertGreaterThanOrEqual(2, $stats['reservations']['cancelled']);
        $this->assertGreaterThanOrEqual(15, $stats['reservations']['active']);
        
        $this->assertEquals(
            $stats['vehicles']['available'] + $stats['vehicles']['maintenance'] + $stats['vehicles']['out_of_service'],
            $stats['vehicles']['total']
        );
    }

    public function test_get_reservations_by_month_returns_correct_data(): void
    {
        $now = Carbon::now();
        
        Reservation::factory()->create([
            'start_date' => $now->copy()->subMonths(2)->startOfMonth(),
        ]);
        Reservation::factory()->count(2)->create([
            'start_date' => $now->copy()->subMonth()->startOfMonth(),
        ]);
        Reservation::factory()->count(3)->create([
            'start_date' => $now->copy()->startOfMonth(),
        ]);

        $result = $this->service->getReservationsByMonth(3);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
        
        $currentMonth = $result[count($result) - 1];
        $this->assertEquals($now->format('Y-m'), $currentMonth['month']);
        $this->assertEquals(3, $currentMonth['count']);
    }

    public function test_get_most_used_vehicles_returns_correct_order(): void
    {
        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();
        $vehicle3 = Vehicle::factory()->create();

        Reservation::factory()->count(5)->create(['vehicle_id' => $vehicle1->id]);
        Reservation::factory()->count(3)->create(['vehicle_id' => $vehicle2->id]);
        Reservation::factory()->count(1)->create(['vehicle_id' => $vehicle3->id]);

        $result = $this->service->getMostUsedVehicles(10);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
        
        $this->assertEquals($vehicle1->id, $result[0]['id']);
        $this->assertEquals(5, $result[0]['reservation_count']);
        $this->assertEquals($vehicle2->id, $result[1]['id']);
        $this->assertEquals(3, $result[1]['reservation_count']);
    }

    public function test_get_most_used_vehicles_excludes_cancelled_reservations(): void
    {
        $vehicle = Vehicle::factory()->create();

        Reservation::factory()->count(5)->confirmed()->create(['vehicle_id' => $vehicle->id]);
        Reservation::factory()->count(3)->cancelled()->create(['vehicle_id' => $vehicle->id]);

        $result = $this->service->getMostUsedVehicles(10);

        $this->assertIsArray($result);
        $vehicleResult = collect($result)->firstWhere('id', $vehicle->id);
        $this->assertNotNull($vehicleResult);
        $this->assertEquals(5, $vehicleResult['reservation_count']);
    }

    public function test_get_vehicle_occupancy_rate_calculates_correctly(): void
    {
        $vehicle = Vehicle::factory()->create();
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $totalDays = $startDate->diffInDays($endDate) + 1;

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addDays(5),
            'end_date' => $startDate->copy()->addDays(10),
        ]);

        $result = $this->service->getVehicleOccupancyRate($startDate, $endDate);

        $this->assertIsArray($result);
        $vehicleResult = collect($result)->firstWhere('vehicle_id', $vehicle->id);
        $this->assertNotNull($vehicleResult);
        $this->assertEquals(6, $vehicleResult['occupied_days']);
        $this->assertEquals($totalDays, $vehicleResult['total_days']);
        $this->assertGreaterThan(0, $vehicleResult['occupancy_rate']);
    }

    public function test_get_vehicle_occupancy_rate_handles_overlapping_periods(): void
    {
        $vehicle = Vehicle::factory()->create();
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->subDays(5),
            'end_date' => $startDate->copy()->addDays(5),
        ]);

        $result = $this->service->getVehicleOccupancyRate($startDate, $endDate);

        $vehicleResult = collect($result)->firstWhere('vehicle_id', $vehicle->id);
        $this->assertNotNull($vehicleResult);
        $this->assertEquals(6, $vehicleResult['occupied_days']);
    }

    public function test_get_vehicle_occupancy_rate_excludes_non_confirmed_reservations(): void
    {
        $vehicle = Vehicle::factory()->create();
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addDays(1),
            'end_date' => $startDate->copy()->addDays(5),
        ]);

        Reservation::factory()->pending()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addDays(10),
            'end_date' => $startDate->copy()->addDays(15),
        ]);

        $result = $this->service->getVehicleOccupancyRate($startDate, $endDate);

        $vehicleResult = collect($result)->firstWhere('vehicle_id', $vehicle->id);
        $this->assertNotNull($vehicleResult);
        $this->assertEquals(5, $vehicleResult['occupied_days']);
    }

    public function test_get_vehicle_occupancy_rate_sorts_by_occupancy_descending(): void
    {
        Reservation::query()->delete();
        Vehicle::query()->delete();
        
        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle1->id,
            'start_date' => $startDate->copy()->addDays(1)->startOfDay(),
            'end_date' => $startDate->copy()->addDays(10)->endOfDay(),
        ]);

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle2->id,
            'start_date' => $startDate->copy()->addDays(1)->startOfDay(),
            'end_date' => $startDate->copy()->addDays(5)->endOfDay(),
        ]);

        $result = $this->service->getVehicleOccupancyRate($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
        
        $vehicle1Result = collect($result)->firstWhere('vehicle_id', $vehicle1->id);
        $vehicle2Result = collect($result)->firstWhere('vehicle_id', $vehicle2->id);
        
        $this->assertNotNull($vehicle1Result, 'Le véhicule 1 doit être présent dans les résultats');
        $this->assertNotNull($vehicle2Result, 'Le véhicule 2 doit être présent dans les résultats');
        
        $this->assertNotEquals($vehicle1Result['occupancy_rate'], $vehicle2Result['occupancy_rate'], 
            'Les deux véhicules doivent avoir des taux d\'occupation différents');
        
        if (count($result) > 1) {
            $maxRate = max(array_column($result, 'occupancy_rate'));
            $minRate = min(array_column($result, 'occupancy_rate'));
            
            $this->assertEquals($maxRate, $result[0]['occupancy_rate'], 
                'Le premier élément doit avoir le taux d\'occupation le plus élevé');
            
            for ($i = 0; $i < count($result) - 1; $i++) {
                $rateCurrent = (float) $result[$i]['occupancy_rate'];
                $rateNext = (float) $result[$i + 1]['occupancy_rate'];
                $this->assertTrue(
                    $rateCurrent >= $rateNext,
                    "Le tri doit être décroissant : l'élément à l'index {$i} ({$rateCurrent}%) doit avoir un taux >= à l'élément à l'index " . ($i + 1) . " ({$rateNext}%)"
                );
            }
            
            $vehicleIds = array_column($result, 'vehicle_id');
            $this->assertContains($vehicle1->id, $vehicleIds, 'Le véhicule 1 doit être présent dans les résultats');
            $this->assertContains($vehicle2->id, $vehicleIds, 'Le véhicule 2 doit être présent dans les résultats');
        }
    }
}
