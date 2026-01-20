<?php

namespace Tests\Unit\Enums;

use App\Enums\VehicleStatus;
use Tests\TestCase;

class VehicleStatusTest extends TestCase
{
    public function test_all_returns_all_statuses(): void
    {
        $statuses = VehicleStatus::all();

        $this->assertCount(3, $statuses);
        $this->assertContains(VehicleStatus::AVAILABLE, $statuses);
        $this->assertContains(VehicleStatus::MAINTENANCE, $statuses);
        $this->assertContains(VehicleStatus::OUT_OF_SERVICE, $statuses);
    }

    public function test_is_valid_returns_true_for_valid_status(): void
    {
        $this->assertTrue(VehicleStatus::isValid(VehicleStatus::AVAILABLE));
        $this->assertTrue(VehicleStatus::isValid(VehicleStatus::MAINTENANCE));
        $this->assertTrue(VehicleStatus::isValid(VehicleStatus::OUT_OF_SERVICE));
    }

    public function test_is_valid_returns_false_for_invalid_status(): void
    {
        $this->assertFalse(VehicleStatus::isValid('invalid_status'));
        $this->assertFalse(VehicleStatus::isValid(''));
    }

    public function test_valid_transitions_from_available(): void
    {
        $transitions = VehicleStatus::validTransitions(VehicleStatus::AVAILABLE);

        $this->assertCount(2, $transitions);
        $this->assertContains(VehicleStatus::MAINTENANCE, $transitions);
        $this->assertContains(VehicleStatus::OUT_OF_SERVICE, $transitions);
    }

    public function test_valid_transitions_from_maintenance(): void
    {
        $transitions = VehicleStatus::validTransitions(VehicleStatus::MAINTENANCE);

        $this->assertCount(2, $transitions);
        $this->assertContains(VehicleStatus::AVAILABLE, $transitions);
        $this->assertContains(VehicleStatus::OUT_OF_SERVICE, $transitions);
    }

    public function test_valid_transitions_from_out_of_service(): void
    {
        $transitions = VehicleStatus::validTransitions(VehicleStatus::OUT_OF_SERVICE);

        $this->assertCount(2, $transitions);
        $this->assertContains(VehicleStatus::AVAILABLE, $transitions);
        $this->assertContains(VehicleStatus::MAINTENANCE, $transitions);
    }

    public function test_is_valid_transition_returns_true_for_all_transitions(): void
    {
        // All transitions are valid for vehicles
        $this->assertTrue(VehicleStatus::isValidTransition(
            VehicleStatus::AVAILABLE,
            VehicleStatus::MAINTENANCE
        ));
        $this->assertTrue(VehicleStatus::isValidTransition(
            VehicleStatus::MAINTENANCE,
            VehicleStatus::AVAILABLE
        ));
        $this->assertTrue(VehicleStatus::isValidTransition(
            VehicleStatus::OUT_OF_SERVICE,
            VehicleStatus::AVAILABLE
        ));
    }

    public function test_is_valid_transition_returns_false_for_invalid_statuses(): void
    {
        $this->assertFalse(VehicleStatus::isValidTransition('invalid', VehicleStatus::AVAILABLE));
        $this->assertFalse(VehicleStatus::isValidTransition(VehicleStatus::AVAILABLE, 'invalid'));
    }
}
