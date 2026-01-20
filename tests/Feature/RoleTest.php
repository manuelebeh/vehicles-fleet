<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
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

    public function test_admin_can_list_roles(): void
    {
        $admin = $this->createAdmin();
        Role::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'display_name',
                        'description',
                    ],
                ],
            ]);
    }

    public function test_admin_can_create_role(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/roles', [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Manager role',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'manager',
                    'display_name' => 'Manager',
                ],
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'manager',
            'display_name' => 'Manager',
        ]);
    }

    public function test_employee_cannot_create_role(): void
    {
        $employee = $this->createEmployee();

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson('/api/roles', [
                'name' => 'manager',
                'display_name' => 'Manager',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_role(): void
    {
        $admin = $this->createAdmin();
        $role = Role::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                ],
            ]);
    }

    public function test_admin_can_update_role(): void
    {
        $admin = $this->createAdmin();
        $role = Role::factory()->create(['display_name' => 'Old Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/roles/{$role->id}", [
                'name' => $role->name,
                'display_name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'display_name' => 'New Name',
                ],
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'display_name' => 'New Name',
        ]);
    }

    public function test_employee_cannot_update_role(): void
    {
        $employee = $this->createEmployee();
        $role = Role::factory()->create();

        $response = $this->actingAs($employee, 'sanctum')
            ->putJson("/api/roles/{$role->id}", [
                'display_name' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_role(): void
    {
        $admin = $this->createAdmin();
        $role = Role::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_employee_cannot_delete_role(): void
    {
        $employee = $this->createEmployee();
        $role = Role::factory()->create();

        $response = $this->actingAs($employee, 'sanctum')
            ->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(403);
    }

    public function test_create_role_requires_name(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/roles', [
                'display_name' => 'Manager',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_role_requires_unique_name(): void
    {
        $admin = $this->createAdmin();
        Role::factory()->create(['name' => 'existing']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/roles', [
                'name' => 'existing',
                'display_name' => 'Existing',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
