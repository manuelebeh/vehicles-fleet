<?php

namespace Tests\Unit\Services;

use App\Enums\ReservationStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\ReservationConflictException;
use App\Exceptions\VehicleNotAvailableException;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservationService();
    }

    public function test_create_reservation_sets_default_status_to_pending(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        $reservation = $this->service->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $this->assertEquals(ReservationStatus::PENDING, $reservation->status);
        $this->assertEquals($user->id, $reservation->user_id);
        $this->assertEquals($vehicle->id, $reservation->vehicle_id);
    }

    public function test_create_reservation_throws_exception_for_unavailable_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->maintenance()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        $this->expectException(VehicleNotAvailableException::class);

        $this->service->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function test_create_reservation_throws_exception_for_conflicting_reservation(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(4);

        // Create conflicting confirmed reservation
        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addHours(1),
            'end_date' => $startDate->copy()->addHours(3),
        ]);

        $this->expectException(ReservationConflictException::class);

        $this->service->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function test_check_availability_returns_true_when_no_conflicts(): void
    {
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        $isAvailable = $this->service->checkAvailability($vehicle, $startDate, $endDate);

        $this->assertTrue($isAvailable);
    }

    public function test_check_availability_returns_false_when_conflict_exists(): void
    {
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(4);

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addHours(1),
            'end_date' => $startDate->copy()->addHours(3),
        ]);

        $isAvailable = $this->service->checkAvailability($vehicle, $startDate, $endDate);

        $this->assertFalse($isAvailable);
    }

    public function test_update_status_validates_transition(): void
    {
        $reservation = Reservation::factory()->pending()->create();

        $this->service->updateStatus($reservation, ReservationStatus::CONFIRMED);

        $this->assertEquals(ReservationStatus::CONFIRMED, $reservation->fresh()->status);
    }

    public function test_update_status_throws_exception_for_invalid_transition(): void
    {
        $reservation = Reservation::factory()->completed()->create();

        $this->expectException(InvalidStatusTransitionException::class);

        $this->service->updateStatus($reservation, ReservationStatus::PENDING);
    }

    public function test_confirm_reservation_checks_availability(): void
    {
        $vehicle = Vehicle::factory()->available()->create();
        $reservation = Reservation::factory()->pending()->create([
            'vehicle_id' => $vehicle->id,
        ]);

        $this->service->confirm($reservation);

        $this->assertEquals(ReservationStatus::CONFIRMED, $reservation->fresh()->status);
    }

    public function test_confirm_reservation_throws_exception_when_vehicle_unavailable(): void
    {
        $vehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(4);

        // Create conflicting reservation
        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addHours(1),
            'end_date' => $startDate->copy()->addHours(3),
        ]);

        $reservation = Reservation::factory()->pending()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $this->expectException(ReservationConflictException::class);

        $this->service->confirm($reservation);
    }

    public function test_cancel_reservation_updates_status(): void
    {
        $reservation = Reservation::factory()->pending()->create();

        $this->service->cancel($reservation);

        $this->assertEquals(ReservationStatus::CANCELLED, $reservation->fresh()->status);
    }

    public function test_complete_reservation_updates_status(): void
    {
        $reservation = Reservation::factory()->confirmed()->create();

        $this->service->complete($reservation);

        $this->assertEquals(ReservationStatus::COMPLETED, $reservation->fresh()->status);
    }

    public function test_get_available_vehicles_excludes_conflicting_vehicles(): void
    {
        $availableVehicle = Vehicle::factory()->available()->create();
        $conflictingVehicle = Vehicle::factory()->available()->create();
        $startDate = Carbon::now()->addDays(1);
        $endDate = $startDate->copy()->addHours(2);

        Reservation::factory()->confirmed()->create([
            'vehicle_id' => $conflictingVehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $vehicles = $this->service->getAvailableVehicles($startDate, $endDate);

        $this->assertTrue($vehicles->contains($availableVehicle));
        $this->assertFalse($vehicles->contains($conflictingVehicle));
    }

    public function test_get_by_user_returns_only_user_reservations(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Reservation::factory()->count(3)->create(['user_id' => $user1->id]);
        Reservation::factory()->count(2)->create(['user_id' => $user2->id]);

        $reservations = $this->service->getByUser($user1);

        $this->assertCount(3, $reservations->items());
        $reservations->each(function ($reservation) use ($user1) {
            $this->assertEquals($user1->id, $reservation->user_id);
        });
    }
}
