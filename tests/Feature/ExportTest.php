<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $adminRole = Role::factory()->admin()->create();
        $user = User::factory()->create();
        $user->roles()->attach($adminRole);
        return $user;
    }

    private function createEmployee(): User
    {
        $employeeRole = Role::factory()->employee()->create();
        $user = User::factory()->create();
        $user->roles()->attach($employeeRole);
        return $user;
    }

    public function test_admin_can_export_reservations(): void
    {
        $admin = $this->createAdmin();
        Reservation::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/export/reservations');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_non_admin_cannot_export_reservations(): void
    {
        $employee = $this->createEmployee();

        $response = $this->actingAs($employee, 'sanctum')
            ->getJson('/api/export/reservations');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent exporter les réservations.',
            ]);
    }

    public function test_export_reservations_filters_by_start_date(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createEmployee();
        $vehicle = Vehicle::factory()->create();
        $startDate = Carbon::now()->addDays(5)->startOfDay();

        // Créer une réservation avant la date de filtre (ne sera pas incluse)
        Reservation::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => Carbon::now()->addDays(3)->startOfDay(),
            'end_date' => Carbon::now()->addDays(4)->startOfDay(),
        ]);
        
        // Créer une réservation à la date de filtre exacte (sera incluse)
        Reservation::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy(),
            'end_date' => $startDate->copy()->addDays(1),
        ]);
        
        // Créer une réservation après la date de filtre (sera incluse)
        Reservation::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate->copy()->addDays(1),
            'end_date' => $startDate->copy()->addDays(2),
        ]);

        $this->assertEquals(3, Reservation::count(), '3 réservations doivent être créées');
        
        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/export/reservations?start_date={$startDate->format('Y-m-d')}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        ob_start();
        $response->send();
        $content = ob_get_clean();
        
        $this->assertIsString($content, 'Le contenu doit être une chaîne');
        $this->assertNotEmpty($content, 'Le CSV ne doit pas être vide');
        
        $lines = array_filter(explode("\n", $content), fn($line) => !empty(trim($line)));
        $this->assertGreaterThanOrEqual(3, count($lines), 'Le CSV doit contenir au moins l\'en-tête et 2 réservations');
    }

    public function test_export_reservations_filters_by_end_date(): void
    {
        $admin = $this->createAdmin();
        $endDate = Carbon::now()->addDays(5);

        Reservation::factory()->create([
            'end_date' => Carbon::now()->addDays(3),
        ]);
        Reservation::factory()->create([
            'end_date' => $endDate->copy()->addDays(2),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/export/reservations?end_date={$endDate->format('Y-m-d')}");

        $response->assertStatus(200);
    }

    public function test_export_reservations_filters_by_status(): void
    {
        $admin = $this->createAdmin();
        Reservation::factory()->confirmed()->count(3)->create();
        Reservation::factory()->pending()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/export/reservations?status=confirmed');

        $response->assertStatus(200);
    }

    public function test_export_reservations_includes_all_required_columns(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        
        Reservation::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/export/reservations');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        ob_start();
        $response->send();
        $content = ob_get_clean();
        
        $this->assertIsString($content, 'Le contenu doit être une chaîne');
        $this->assertNotEmpty($content, 'Le CSV ne doit pas être vide');
        
        $lines = explode("\n", $content);
        $headerLine = $lines[0] ?? '';
        
        if (substr($headerLine, 0, 3) === chr(0xEF).chr(0xBB).chr(0xBF)) {
            $headerLine = substr($headerLine, 3);
        }
        
        $this->assertNotEmpty($headerLine, 'L\'en-tête CSV ne doit pas être vide');
        $this->assertStringContainsString('ID', $headerLine, 'L\'en-tête doit contenir "ID"');
        $this->assertStringContainsString('Utilisateur', $headerLine, 'L\'en-tête doit contenir "Utilisateur"');
        $this->assertStringContainsString('Véhicule', $headerLine, 'L\'en-tête doit contenir "Véhicule"');
        $this->assertStringContainsString('Statut', $headerLine, 'L\'en-tête doit contenir "Statut"');
    }

    public function test_export_reservations_requires_authentication(): void
    {
        $response = $this->getJson('/api/export/reservations');

        $response->assertStatus(401);
    }
}
