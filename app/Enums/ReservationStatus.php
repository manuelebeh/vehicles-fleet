<?php

namespace App\Enums;

class ReservationStatus
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const CANCELLED = 'cancelled';
    public const COMPLETED = 'completed';

    /**
     * Get all valid statuses.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::CANCELLED,
            self::COMPLETED,
        ];
    }

    /**
     * Get valid transitions from a given status.
     *
     * @param  string  $from
     * @return array<string>
     */
    public static function validTransitions(string $from): array
    {
        return match ($from) {
            self::PENDING => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::COMPLETED, self::CANCELLED],
            self::CANCELLED => [], // Cannot transition from cancelled
            self::COMPLETED => [], // Cannot transition from completed
            default => [],
        };
    }

    /**
     * Check if a transition is valid.
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public static function isValidTransition(string $from, string $to): bool
    {
        if (!in_array($from, self::all()) || !in_array($to, self::all())) {
            return false;
        }

        return in_array($to, self::validTransitions($from));
    }

    /**
     * Check if a status is valid.
     *
     * @param  string  $status
     * @return bool
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::all());
    }
}
