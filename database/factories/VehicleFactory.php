<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currentYear = (int) date('Y');
        $brands = ['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes', 'Audi', 'Volkswagen', 'Peugeot', 'Renault', 'Citroën'];
        $colors = ['Rouge', 'Bleu', 'Noir', 'Blanc', 'Gris', 'Vert', 'Jaune', 'Argent'];

        return [
            'brand' => fake()->randomElement($brands),
            'model' => fake()->word() . ' ' . fake()->numberBetween(100, 999),
            'license_plate' => strtoupper(fake()->bothify('??-###-??')),
            'year' => fake()->numberBetween(2010, $currentYear),
            'color' => fake()->randomElement($colors),
            'status' => VehicleStatus::AVAILABLE,
        ];
    }

    /**
     * Indicate that the vehicle is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::AVAILABLE,
        ]);
    }

    /**
     * Indicate that the vehicle is in maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::MAINTENANCE,
        ]);
    }

    /**
     * Indicate that the vehicle is out of service.
     */
    public function outOfService(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::OUT_OF_SERVICE,
        ]);
    }
}
