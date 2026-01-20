<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

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

    private function createCsvFile(array $rows): UploadedFile
    {
        $content = "brand;model;license_plate;year;color;status\n";
        foreach ($rows as $row) {
            $content .= implode(';', $row) . "\n";
        }

        return UploadedFile::fake()->createWithContent('vehicles.csv', $content);
    }

    public function test_admin_can_import_vehicles(): void
    {
        $admin = $this->createAdmin();
        $file = $this->createCsvFile([
            ['Toyota', 'Corolla', 'AB-123-CD', '2020', 'Rouge', 'available'],
            ['Honda', 'Civic', 'EF-456-GH', '2021', 'Bleu', 'available'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/import/vehicles', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'imported',
                'failed',
                'errors',
            ]);

        $this->assertEquals(2, $response->json('imported'));
        $this->assertEquals(0, $response->json('failed'));
        $this->assertDatabaseHas('vehicles', ['license_plate' => 'AB-123-CD']);
        $this->assertDatabaseHas('vehicles', ['license_plate' => 'EF-456-GH']);
    }

    public function test_non_admin_cannot_import_vehicles(): void
    {
        $employee = $this->createEmployee();
        $file = $this->createCsvFile([
            ['Toyota', 'Corolla', 'AB-123-CD', '2020', 'Rouge', 'available'],
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson('/api/import/vehicles', [
                'file' => $file,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent importer des véhicules.',
            ]);
    }

    public function test_import_validates_file_type(): void
    {
        $admin = $this->createAdmin();
        $file = UploadedFile::fake()->create('vehicles.pdf', 100);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/import/vehicles', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_import_validates_file_size(): void
    {
        $admin = $this->createAdmin();
        $file = UploadedFile::fake()->create('vehicles.csv', 3000);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/import/vehicles', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_import_handles_invalid_csv_format(): void
    {
        $admin = $this->createAdmin();
        $file = UploadedFile::fake()->createWithContent('vehicles.csv', "brand;model\nToyota;Corolla");

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/import/vehicles', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_import_validates_required_fields(): void
    {
        $admin = $this->createAdmin();
        $file = $this->createCsvFile([
            ['', 'Corolla', 'AB-123-CD', '2020', 'Rouge', 'available'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/import/vehicles', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('imported'));
        $this->assertEquals(1, $response->json('failed'));
        $this->assertNotEmpty($response->json('errors'));
    }

    public function test_import_validates_unique_license_plate(): void
    {
        $admin = $this->createAdmin();
        Vehicle::factory()->create(['license_plate' => 'AB-123-CD']);

        $file = $this->createCsvFile([
            ['Toyota', 'Corolla', 'AB-123-CD', '2020', 'Rouge', 'available'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/import/vehicles', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('imported'));
        $this->assertEquals(1, $response->json('failed'));
    }

    public function test_import_handles_partial_success(): void
    {
        $admin = $this->createAdmin();
        Vehicle::factory()->create(['license_plate' => 'AB-123-CD']);

        $file = $this->createCsvFile([
            ['Toyota', 'Corolla', 'AB-123-CD', '2020', 'Rouge', 'available'],
            ['Honda', 'Civic', 'EF-456-GH', '2021', 'Bleu', 'available'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/import/vehicles', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('imported'));
        $this->assertEquals(1, $response->json('failed'));
        $this->assertDatabaseHas('vehicles', ['license_plate' => 'EF-456-GH']);
    }

    public function test_import_requires_authentication(): void
    {
        $file = $this->createCsvFile([
            ['Toyota', 'Corolla', 'AB-123-CD', '2020', 'Rouge', 'available'],
        ]);

        $response = $this->postJson('/api/import/vehicles', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }
}
