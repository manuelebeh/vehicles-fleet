<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
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

    public function test_authenticated_user_can_list_reservations(): void
    {
        $user = $this->createEmployee();
        Reservation::factory()->count(5)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reservations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'vehicle_id',
                        'start_date',
                        'end_date',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_user_can_create_reservation_for_themselves(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reservations', [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
                'purpose' => 'Business trip',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'user_id' => $user->id,
                    'vehicle_id' => $vehicle->id,
                    'status' => ReservationStatus::PENDING,
                ],
            ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => ReservationStatus::PENDING,
        ]);
    }

    public function test_user_cannot_create_reservation_for_another_user(): void
    {
        $user = $this->createEmployee();
        $otherUser = User::factory()->create();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reservations', [
                'user_id' => $otherUser->id,
                'vehicle_id' => $vehicle->id,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_reservation_for_any_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/reservations', [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
            ]);

        $response->assertStatus(201);
    }

    public function test_cannot_create_reservation_for_unavailable_vehicle(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->maintenance()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reservations', [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Le véhicule n\'est pas disponible.',
            ]);
    }

    public function test_cannot_create_overlapping_reservations(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(4);

        // Create first confirmed reservation
        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addHours(1),
            'end_date' => $startDate->copy()->addHours(3),
        ]);

        // Try to create overlapping reservation
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reservations', [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Le véhicule est déjà réservé pour cette période.',
            ]);
    }

    public function test_user_can_view_their_own_reservation(): void
    {
        $user = $this->createEmployee();
        $reservation = Reservation::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/reservations/{$reservation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $reservation->id,
                    'user_id' => $user->id,
                ],
            ]);
    }

    public function test_user_can_update_their_own_reservation(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->available()->create();
        $reservation = Reservation::factory()->pending()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $newStartDate = Carbon::now()->addDays(2);
        $newEndDate = $newStartDate->copy()->addHours(3);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/reservations/{$reservation->id}", [
                'start_date' => $newStartDate->toIso8601String(),
                'end_date' => $newEndDate->toIso8601String(),
                'purpose' => 'Updated purpose',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'purpose' => 'Updated purpose',
                ],
            ]);
    }

    public function test_user_cannot_update_other_user_reservation(): void
    {
        $user = $this->createEmployee();
        $otherUser = User::factory()->create();
        $reservation = Reservation::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/reservations/{$reservation->id}", [
                'purpose' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_cancel_their_reservation(): void
    {
        $user = $this->createEmployee();
        $reservation = Reservation::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/reservations/{$reservation->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => ReservationStatus::CANCELLED,
                ],
            ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED,
        ]);
    }

    public function test_admin_can_confirm_reservation(): void
    {
        $admin = $this->createAdmin();
        $vehicle = Vehicle::factory()->available()->create();
        $reservation = Reservation::factory()->pending()->create([
            'vehicle_id' => $vehicle->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/reservations/{$reservation->id}/confirm");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => ReservationStatus::CONFIRMED,
                ],
            ]);
    }

    public function test_cannot_confirm_reservation_with_conflicting_reservation(): void
    {
        $admin = $this->createAdmin();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(4);

        // Create conflicting confirmed reservation
        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addHours(1),
            'end_date' => $startDate->copy()->addHours(3),
        ]);

        // Try to confirm overlapping reservation
        $reservation = Reservation::factory()->pending()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/reservations/{$reservation->id}/confirm");

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Le véhicule est déjà réservé pour cette période.',
            ]);
    }

    public function test_admin_can_complete_reservation(): void
    {
        $admin = $this->createAdmin();
        $reservation = Reservation::factory()->confirmed()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/reservations/{$reservation->id}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => ReservationStatus::COMPLETED,
                ],
            ]);
    }

    public function test_user_can_view_their_reservations(): void
    {
        $user = $this->createEmployee();
        Reservation::factory()->count(3)->create(['user_id' => $user->id]);
        Reservation::factory()->count(2)->create(); // Other users' reservations

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/users/{$user->id}/reservations");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_search_available_vehicles(): void
    {
        $user = $this->createEmployee();
        $availableVehicle = Vehicle::factory()->available()->create();
        $maintenanceVehicle = Vehicle::factory()->maintenance()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reservations/available-vehicles?start_date=' . urlencode($startDate->toIso8601String()) . '&end_date=' . urlencode($endDate->toIso8601String()));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'brand',
                        'model',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_create_reservation_requires_valid_date_range(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->subHours(1); // End before start

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reservations', [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_create_reservation_requires_minimum_duration(): void
    {
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addMinutes(30); // Less than 1 hour

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reservations', [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }
}
