<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $vehicles = Vehicle::where('status', 'available')->get();

        if ($users->isEmpty() || $vehicles->isEmpty()) {
            $this->command->warn('Aucun utilisateur ou véhicule disponible. Créez d\'abord des utilisateurs et des véhicules.');
            return;
        }

        // Création de quelques réservations passées (complétées)
        for ($i = 0; $i < 5; $i++) {
            $startDate = Carbon::now()->subDays(rand(10, 30))->subHours(rand(1, 5));
            $endDate = $startDate->copy()->addHours(rand(2, 8));

            Reservation::create([
                'user_id' => $users->random()->id,
                'vehicle_id' => $vehicles->random()->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => ReservationStatus::COMPLETED,
                'purpose' => 'Déplacement professionnel',
            ]);
        }

        // Création de quelques réservations en cours (confirmées)
        for ($i = 0; $i < 3; $i++) {
            $startDate = Carbon::now()->subHours(rand(1, 5));
            $endDate = $startDate->copy()->addHours(rand(2, 6));

            Reservation::create([
                'user_id' => $users->random()->id,
                'vehicle_id' => $vehicles->random()->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => ReservationStatus::CONFIRMED,
                'purpose' => 'Mission client',
            ]);
        }

        // Création de quelques réservations futures (en attente)
        for ($i = 0; $i < 5; $i++) {
            $startDate = Carbon::now()->addDays(rand(1, 15))->addHours(rand(1, 12));
            $endDate = $startDate->copy()->addHours(rand(2, 8));

            Reservation::create([
                'user_id' => $users->random()->id,
                'vehicle_id' => $vehicles->random()->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => ReservationStatus::PENDING,
                'purpose' => 'Réunion externe',
            ]);
        }

        // Création de quelques réservations annulées
        for ($i = 0; $i < 2; $i++) {
            $startDate = Carbon::now()->addDays(rand(5, 20));
            $endDate = $startDate->copy()->addHours(rand(2, 6));

            Reservation::create([
                'user_id' => $users->random()->id,
                'vehicle_id' => $vehicles->random()->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => ReservationStatus::CANCELLED,
                'purpose' => 'Annulé - changement de plan',
            ]);
        }
    }
}
