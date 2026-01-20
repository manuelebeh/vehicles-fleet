<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $employeeRole = Role::where('name', 'employee')->first();

        // Création d'un admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'first_name' => 'Admin',
                'last_name' => 'User',
            ]
        );

        if ($adminRole && !$admin->roles->contains($adminRole)) {
            $admin->roles()->attach($adminRole);
        }

        // Création d'employés
        $employees = [
            [
                'email' => 'employee1@example.com',
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
            ],
            [
                'email' => 'employee2@example.com',
                'first_name' => 'Marie',
                'last_name' => 'Martin',
            ],
            [
                'email' => 'employee3@example.com',
                'first_name' => 'Pierre',
                'last_name' => 'Bernard',
            ],
        ];

        foreach ($employees as $employeeData) {
            $employee = User::firstOrCreate(
                ['email' => $employeeData['email']],
                array_merge($employeeData, [
                    'password' => Hash::make('password'),
                ])
            );

            if ($employeeRole && !$employee->roles->contains($employeeRole)) {
                $employee->roles()->attach($employeeRole);
            }
        }

        // Création d'utilisateurs supplémentaires sans rôle spécifique
        User::factory(5)->create();
    }
}
