<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
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

    public function test_admin_can_list_users(): void
    {
        $admin = $this->createAdmin();
        User::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'email',
                        'first_name',
                        'last_name',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    public function test_employee_cannot_create_user(): void
    {
        $employee = $this->createEmployee();

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson('/api/users', [
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_view_their_own_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_admin_can_view_any_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                ],
            ]);
    }

    public function test_user_can_update_their_own_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/users/{$user->id}", [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_employee_cannot_update_other_user(): void
    {
        $employee = $this->createEmployee();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($employee, 'sanctum')
            ->putJson("/api/users/{$otherUser->id}", [
                'first_name' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_employee_cannot_delete_user(): void
    {
        $employee = $this->createEmployee();
        $user = User::factory()->create();

        $response = $this->actingAs($employee, 'sanctum')
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_assign_role_to_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/users/{$user->id}/roles", [
                'role_id' => $role->id,
            ]);

        $response->assertStatus(200);

        $this->assertTrue($user->fresh()->roles->contains($role));
    }

    public function test_employee_cannot_assign_role(): void
    {
        $employee = $this->createEmployee();
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson("/api/users/{$user->id}/roles", [
                'role_id' => $role->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_remove_role_from_user(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/users/{$user->id}/roles", [
                'role_id' => $role->id,
            ]);

        $response->assertStatus(200);

        $this->assertFalse($user->fresh()->roles->contains($role));
    }

    public function test_admin_can_sync_user_roles(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        $role3 = Role::factory()->create();
        $user->roles()->attach([$role1->id, $role2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/users/{$user->id}/roles", [
                'role_ids' => [$role2->id, $role3->id],
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertFalse($user->roles->contains($role1));
        $this->assertTrue($user->roles->contains($role2));
        $this->assertTrue($user->roles->contains($role3));
    }

    public function test_create_user_requires_email(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_user_requires_unique_email(): void
    {
        $admin = $this->createAdmin();
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_user_requires_password_confirmation(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'email' => 'newuser@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
