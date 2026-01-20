<?php

namespace Database\Seeders;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            [
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'license_plate' => 'AB-123-CD',
                'year' => 2020,
                'color' => 'Rouge',
                'status' => VehicleStatus::AVAILABLE,
            ],
            [
                'brand' => 'Honda',
                'model' => 'Civic',
                'license_plate' => 'EF-456-GH',
                'year' => 2021,
                'color' => 'Bleu',
                'status' => VehicleStatus::AVAILABLE,
            ],
            [
                'brand' => 'Ford',
                'model' => 'Focus',
                'license_plate' => 'IJ-789-KL',
                'year' => 2019,
                'color' => 'Noir',
                'status' => VehicleStatus::AVAILABLE,
            ],
            [
                'brand' => 'BMW',
                'model' => 'Série 3',
                'license_plate' => 'MN-012-OP',
                'year' => 2022,
                'color' => 'Blanc',
                'status' => VehicleStatus::MAINTENANCE,
            ],
            [
                'brand' => 'Mercedes',
                'model' => 'Classe C',
                'license_plate' => 'QR-345-ST',
                'year' => 2021,
                'color' => 'Gris',
                'status' => VehicleStatus::AVAILABLE,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::firstOrCreate(
                ['license_plate' => $vehicle['license_plate']],
                $vehicle
            );
        }

        // Création de véhicules supplémentaires avec la factory
        Vehicle::factory(10)->create();
    }
}
