<?php

namespace App\Enums;

class VehicleStatus
{
    public const AVAILABLE = 'available';
    public const MAINTENANCE = 'maintenance';
    public const OUT_OF_SERVICE = 'out_of_service';

    /**
     * Get all valid statuses.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::AVAILABLE,
            self::MAINTENANCE,
            self::OUT_OF_SERVICE,
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
            self::AVAILABLE => [self::MAINTENANCE, self::OUT_OF_SERVICE],
            self::MAINTENANCE => [self::AVAILABLE, self::OUT_OF_SERVICE],
            self::OUT_OF_SERVICE => [self::AVAILABLE, self::MAINTENANCE],
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
