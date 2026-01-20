<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class UserService
{
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')->paginate($perPage);
    }

    public function getAllWithoutPagination(): Collection
    {
        return User::with('roles')->get();
    }

    public function getById(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    public function getByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function generatePassword(int $length = 12): string
    {
        return Str::random($length);
    }

    public function create(array $data): array
    {
        $generatedPassword = $this->generatePassword();
        $data['password'] = $generatedPassword;
        
        $user = User::create($data);
        
        return [
            'user' => $user,
            'password' => $generatedPassword,
        ];
    }

    public function regeneratePassword(User $user): array
    {
        $generatedPassword = $this->generatePassword();
        $user->password = $generatedPassword;
        $user->save();
        
        return [
            'user' => $user,
            'password' => $generatedPassword,
        ];
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function assignRole(User $user, Role $role): void
    {
        if (!$user->roles()->where('roles.id', $role->id)->exists()) {
            $user->roles()->attach($role->id);
        }
    }

    public function removeRole(User $user, Role $role): void
    {
        $user->roles()->detach($role->id);
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
    }
}
