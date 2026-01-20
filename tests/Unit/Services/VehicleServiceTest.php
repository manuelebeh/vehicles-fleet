<?php

namespace Tests\Unit\Services;

use App\Enums\VehicleStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleServiceTest extends TestCase
{
    use RefreshDatabase;

    private VehicleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VehicleService();
    }

    public function test_create_vehicle_creates_vehicle_with_data(): void
    {
        $data = [
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'license_plate' => 'AB-123-CD',
            'year' => 2020,
            'color' => 'Rouge',
            'status' => VehicleStatus::AVAILABLE,
        ];

        $vehicle = $this->service->create($data);

        $this->assertDatabaseHas('vehicles', [
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'license_plate' => 'AB-123-CD',
        ]);
        $this->assertNotNull($vehicle->id);
    }

    public function test_update_vehicle_updates_vehicle_data(): void
    {
        $vehicle = Vehicle::factory()->create(['color' => 'Rouge']);

        $this->service->update($vehicle, ['color' => 'Bleu']);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'color' => 'Bleu',
        ]);
    }

    public function test_delete_vehicle_removes_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->service->delete($vehicle);

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_update_status_updates_vehicle_status(): void
    {
        $vehicle = Vehicle::factory()->available()->create();

        $this->service->updateStatus($vehicle, VehicleStatus::MAINTENANCE);

        $this->assertEquals(VehicleStatus::MAINTENANCE, $vehicle->fresh()->status);
    }

    public function test_update_status_validates_transition(): void
    {
        $vehicle = Vehicle::factory()->available()->create();

        $this->service->updateStatus($vehicle, VehicleStatus::MAINTENANCE);

        $this->assertEquals(VehicleStatus::MAINTENANCE, $vehicle->fresh()->status);
    }

    public function test_update_status_throws_exception_for_invalid_status(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->updateStatus($vehicle, 'invalid_status');
    }

    public function test_get_available_returns_only_available_vehicles(): void
    {
        Vehicle::factory()->available()->count(3)->create();
        Vehicle::factory()->maintenance()->count(2)->create();
        Vehicle::factory()->outOfService()->count(1)->create();

        $vehicles = $this->service->getAvailable();

        $this->assertCount(3, $vehicles);
        $vehicles->each(function ($vehicle) {
            $this->assertEquals(VehicleStatus::AVAILABLE, $vehicle->status);
        });
    }

    public function test_get_by_license_plate_returns_vehicle_with_matching_plate(): void
    {
        $vehicle = Vehicle::factory()->create(['license_plate' => 'AB-123-CD']);

        $result = $this->service->getByLicensePlate('AB-123-CD');

        $this->assertNotNull($result);
        $this->assertEquals($vehicle->id, $result->id);
    }

    public function test_get_by_license_plate_returns_null_for_nonexistent_plate(): void
    {
        $result = $this->service->getByLicensePlate('ZZ-999-ZZ');

        $this->assertNull($result);
    }
}
