<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
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

    public function test_authenticated_user_can_list_vehicles(): void
    {
        $user = $this->createEmployee();
        Vehicle::factory()->count(5)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/vehicles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'brand',
                        'model',
                        'license_plate',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_admin_can_create_vehicle(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/vehicles', [
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'license_plate' => 'AB-123-CD',
                'year' => 2020,
                'color' => 'Rouge',
                'status' => VehicleStatus::AVAILABLE,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'brand' => 'Toyota',
                    'model' => 'Corolla',
                    'license_plate' => 'AB-123-CD',
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'license_plate' => 'AB-123-CD',
        ]);
    }

    public function test_employee_cannot_create_vehicle(): void
    {
        $employee = $this->createEmployee();

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson('/api/vehicles', [
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'license_plate' => 'AB-123-CD',
            ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_view_vehicle(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/vehicles/{$vehicle->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $vehicle->id,
                    'brand' => $vehicle->brand,
                ],
            ]);
    }

    public function test_admin_can_update_vehicle(): void
    {
        $admin = $this->createAdmin();
        $vehicle = Vehicle::factory()->create(['color' => 'Rouge']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/vehicles/{$vehicle->id}", [
                'color' => 'Bleu',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'color' => 'Bleu',
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'color' => 'Bleu',
        ]);
    }

    public function test_employee_cannot_update_vehicle(): void
    {
        $employee = $this->createEmployee();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($employee, 'sanctum')
            ->putJson("/api/vehicles/{$vehicle->id}", [
                'color' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_vehicle(): void
    {
        $admin = $this->createAdmin();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/vehicles/{$vehicle->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('vehicles', [
            'id' => $vehicle->id,
        ]);
    }

    public function test_authenticated_user_can_list_available_vehicles(): void
    {
        $user = $this->createEmployee();
        Vehicle::factory()->available()->count(3)->create();
        Vehicle::factory()->maintenance()->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/vehicles/available');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'is_available',
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_vehicle_status(): void
    {
        $admin = $this->createAdmin();
        $vehicle = Vehicle::factory()->available()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/vehicles/{$vehicle->id}/status", [
                'status' => VehicleStatus::MAINTENANCE,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => VehicleStatus::MAINTENANCE,
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => VehicleStatus::MAINTENANCE,
        ]);
    }

    public function test_employee_cannot_update_vehicle_status(): void
    {
        $employee = $this->createEmployee();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($employee, 'sanctum')
            ->patchJson("/api/vehicles/{$vehicle->id}/status", [
                'status' => VehicleStatus::MAINTENANCE,
            ]);

        $response->assertStatus(403);
    }

    public function test_create_vehicle_requires_brand_and_model(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/vehicles', [
                'license_plate' => 'AB-123-CD',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand', 'model']);
    }

    public function test_create_vehicle_requires_unique_license_plate(): void
    {
        $admin = $this->createAdmin();
        Vehicle::factory()->create(['license_plate' => 'AB-123-CD']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/vehicles', [
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'license_plate' => 'AB-123-CD',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['license_plate']);
    }
}
