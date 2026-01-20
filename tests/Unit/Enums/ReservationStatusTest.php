<?php

namespace Tests\Unit\Enums;

use App\Enums\ReservationStatus;
use Tests\TestCase;

class ReservationStatusTest extends TestCase
{
    public function test_all_returns_all_statuses(): void
    {
        $statuses = ReservationStatus::all();

        $this->assertCount(4, $statuses);
        $this->assertContains(ReservationStatus::PENDING, $statuses);
        $this->assertContains(ReservationStatus::CONFIRMED, $statuses);
        $this->assertContains(ReservationStatus::CANCELLED, $statuses);
        $this->assertContains(ReservationStatus::COMPLETED, $statuses);
    }

    public function test_is_valid_returns_true_for_valid_status(): void
    {
        $this->assertTrue(ReservationStatus::isValid(ReservationStatus::PENDING));
        $this->assertTrue(ReservationStatus::isValid(ReservationStatus::CONFIRMED));
        $this->assertTrue(ReservationStatus::isValid(ReservationStatus::CANCELLED));
        $this->assertTrue(ReservationStatus::isValid(ReservationStatus::COMPLETED));
    }

    public function test_is_valid_returns_false_for_invalid_status(): void
    {
        $this->assertFalse(ReservationStatus::isValid('invalid_status'));
        $this->assertFalse(ReservationStatus::isValid(''));
    }

    public function test_valid_transitions_from_pending(): void
    {
        $transitions = ReservationStatus::validTransitions(ReservationStatus::PENDING);

        $this->assertCount(2, $transitions);
        $this->assertContains(ReservationStatus::CONFIRMED, $transitions);
        $this->assertContains(ReservationStatus::CANCELLED, $transitions);
    }

    public function test_valid_transitions_from_confirmed(): void
    {
        $transitions = ReservationStatus::validTransitions(ReservationStatus::CONFIRMED);

        $this->assertCount(2, $transitions);
        $this->assertContains(ReservationStatus::COMPLETED, $transitions);
        $this->assertContains(ReservationStatus::CANCELLED, $transitions);
    }

    public function test_valid_transitions_from_cancelled(): void
    {
        $transitions = ReservationStatus::validTransitions(ReservationStatus::CANCELLED);

        $this->assertEmpty($transitions);
    }

    public function test_valid_transitions_from_completed(): void
    {
        $transitions = ReservationStatus::validTransitions(ReservationStatus::COMPLETED);

        $this->assertEmpty($transitions);
    }

    public function test_is_valid_transition_returns_true_for_valid_transition(): void
    {
        $this->assertTrue(ReservationStatus::isValidTransition(
            ReservationStatus::PENDING,
            ReservationStatus::CONFIRMED
        ));
        $this->assertTrue(ReservationStatus::isValidTransition(
            ReservationStatus::PENDING,
            ReservationStatus::CANCELLED
        ));
        $this->assertTrue(ReservationStatus::isValidTransition(
            ReservationStatus::CONFIRMED,
            ReservationStatus::COMPLETED
        ));
    }

    public function test_is_valid_transition_returns_false_for_invalid_transition(): void
    {
        $this->assertFalse(ReservationStatus::isValidTransition(
            ReservationStatus::PENDING,
            ReservationStatus::COMPLETED
        ));
        $this->assertFalse(ReservationStatus::isValidTransition(
            ReservationStatus::CANCELLED,
            ReservationStatus::CONFIRMED
        ));
        $this->assertFalse(ReservationStatus::isValidTransition(
            ReservationStatus::COMPLETED,
            ReservationStatus::PENDING
        ));
    }
}
