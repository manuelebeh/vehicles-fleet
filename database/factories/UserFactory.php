<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ];
    }

    /**
     * Indicate that the user should be an admin.
     */
    public function admin(): static
    {
        return $this->afterCreating(function ($user) {
            $adminRole = \App\Models\Role::firstOrCreate(
                ['name' => 'admin'],
                ['display_name' => 'Administrateur', 'description' => 'Administrateur du système']
            );
            $user->roles()->attach($adminRole);
        });
    }

    /**
     * Indicate that the user should be an employee.
     */
    public function employee(): static
    {
        return $this->afterCreating(function ($user) {
            $employeeRole = \App\Models\Role::firstOrCreate(
                ['name' => 'employee'],
                ['display_name' => 'Employé', 'description' => 'Employé de l\'entreprise']
            );
            $user->roles()->attach($employeeRole);
        });
    }
}
