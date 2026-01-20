<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\VehicleStatus;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $adminRole = Role::factory()->admin()->create();
        $user = User::factory()->create();
        $user->roles()->attach($adminRole);
        return $user;
    }

    private function createEmployee(): User
    {
        $employeeRole = Role::factory()->employee()->create();
        $user = User::factory()->create();
        $user->roles()->attach($employeeRole);
        return $user;
    }

    public function test_authenticated_user_can_get_general_statistics(): void
    {
        $user = $this->createEmployee();
        
        Vehicle::factory()->count(5)->available()->create();
        Vehicle::factory()->count(3)->maintenance()->create();
        Reservation::factory()->count(10)->pending()->create();
        Reservation::factory()->count(5)->confirmed()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/statistics/general');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'vehicles' => [
                        'total',
                        'available',
                        'maintenance',
                        'out_of_service',
                    ],
                    'reservations' => [
                        'total',
                        'pending',
                        'confirmed',
                        'completed',
                        'cancelled',
                        'active',
                    ],
                ],
            ]);

        $this->assertGreaterThanOrEqual(8, $response->json('data.vehicles.total'));
        $this->assertGreaterThanOrEqual(5, $response->json('data.vehicles.available'));
        $this->assertGreaterThanOrEqual(3, $response->json('data.vehicles.maintenance'));
        $this->assertGreaterThanOrEqual(15, $response->json('data.reservations.total'));
        $this->assertGreaterThanOrEqual(10, $response->json('data.reservations.pending'));
        $this->assertGreaterThanOrEqual(5, $response->json('data.reservations.confirmed'));
        
        $vehicles = $response->json('data.vehicles');
        $this->assertEquals(
            $vehicles['available'] + $vehicles['maintenance'] + $vehicles['out_of_service'],
            $vehicles['total']
        );
    }

    public function test_authenticated_user_can_get_reservations_by_month(): void
    {
        $user = $this->createEmployee();
        $now = Carbon::now();
        
        Reservation::factory()->create([
            'start_date' => $now->copy()->subMonth()->startOfMonth(),
        ]);
        Reservation::factory()->count(2)->create([
            'start_date' => $now->copy()->startOfMonth(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/statistics/reservations-by-month?months=2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'month',
                        'month_label',
                        'count',
                    ],
                ],
            ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_authenticated_user_can_get_most_used_vehicles(): void
    {
        $user = $this->createEmployee();
        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();

        Reservation::factory()->count(5)->create(['vehicle_id' => $vehicle1->id]);
        Reservation::factory()->count(3)->create(['vehicle_id' => $vehicle2->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/statistics/most-used-vehicles?limit=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'brand',
                        'model',
                        'license_plate',
                        'full_name',
                        'reservation_count',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
        $this->assertEquals($vehicle1->id, $data[0]['id']);
        $this->assertEquals(5, $data[0]['reservation_count']);
    }

    public function test_authenticated_user_can_get_vehicle_occupancy(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->create();
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addDays(1),
            'end_date' => $startDate->copy()->addDays(5),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/statistics/vehicle-occupancy?start_date={$startDate->format('Y-m-d')}&end_date={$endDate->format('Y-m-d')}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'vehicle_id',
                        'brand',
                        'model',
                        'license_plate',
                        'full_name',
                        'occupied_days',
                        'total_days',
                        'occupancy_rate',
                    ],
                ],
                'period' => [
                    'start_date',
                    'end_date',
                ],
            ]);

        $data = $response->json('data');
        $vehicleResult = collect($data)->firstWhere('vehicle_id', $vehicle->id);
        $this->assertNotNull($vehicleResult);
        $this->assertEquals(5, $vehicleResult['occupied_days']);
        $this->assertGreaterThan(0, $vehicleResult['occupancy_rate']);
    }

    public function test_vehicle_occupancy_uses_default_period_when_not_provided(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->create();

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->startOfMonth()->addDays(5),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/statistics/vehicle-occupancy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'period',
            ]);
    }

    public function test_statistics_endpoints_require_authentication(): void
    {
        $endpoints = [
            '/api/statistics/general',
            '/api/statistics/reservations-by-month',
            '/api/statistics/most-used-vehicles',
            '/api/statistics/vehicle-occupancy',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(401);
        }
    }

    public function test_most_used_vehicles_excludes_cancelled_reservations(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->create();

        Reservation::factory()->confirmed()->count(5)->create(['vehicle_id' => $vehicle->id]);
        Reservation::factory()->cancelled()->count(3)->create(['vehicle_id' => $vehicle->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/statistics/most-used-vehicles');

        $data = $response->json('data');
        $vehicleResult = collect($data)->firstWhere('id', $vehicle->id);
        $this->assertNotNull($vehicleResult);
        $this->assertEquals(5, $vehicleResult['reservation_count']);
    }
}
