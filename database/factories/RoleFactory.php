<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'display_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the role is an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'display_name' => 'Administrateur',
            'description' => 'Administrateur du système',
        ]);
    }

    /**
     * Indicate that the role is an employee role.
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'employee',
            'display_name' => 'Employé',
            'description' => 'Employé de l\'entreprise',
        ]);
    }
}
