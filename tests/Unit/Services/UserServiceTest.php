<?php

namespace Tests\Unit\Services;

use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService();
    }

    public function test_create_user_creates_user_with_data(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $user = $this->service->create($data);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $this->assertNotNull($user->id);
    }

    public function test_update_user_updates_user_data(): void
    {
        $user = User::factory()->create(['first_name' => 'John']);

        $this->service->update($user, ['first_name' => 'Jane']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane',
        ]);
    }

    public function test_delete_user_removes_user(): void
    {
        $user = User::factory()->create();

        $this->service->delete($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_assign_role_attaches_role_to_user(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->service->assignRole($user, $role);

        $this->assertTrue($user->fresh()->roles->contains($role));
    }

    public function test_assign_role_does_not_duplicate_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $this->service->assignRole($user, $role);

        $this->assertEquals(1, $user->fresh()->roles()->where('roles.id', $role->id)->count());
    }

    public function test_remove_role_detaches_role_from_user(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $this->service->removeRole($user, $role);

        $this->assertFalse($user->fresh()->roles->contains($role));
    }

    public function test_sync_roles_replaces_all_user_roles(): void
    {
        $user = User::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        $role3 = Role::factory()->create();
        $user->roles()->attach([$role1->id, $role2->id]);

        $this->service->syncRoles($user, [$role2->id, $role3->id]);

        $user->refresh();
        $this->assertFalse($user->roles->contains($role1));
        $this->assertTrue($user->roles->contains($role2));
        $this->assertTrue($user->roles->contains($role3));
    }

    public function test_get_by_email_returns_user_with_matching_email(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $result = $this->service->getByEmail('test@example.com');

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_get_by_email_returns_null_for_nonexistent_email(): void
    {
        $result = $this->service->getByEmail('nonexistent@example.com');

        $this->assertNull($result);
    }
}
