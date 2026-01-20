<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Role::paginate($perPage);
    }

    public function getAllWithoutPagination(): Collection
    {
        return Role::all();
    }

    public function getById(int $id): ?Role
    {
        return Role::find($id);
    }

    public function getByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): bool
    {
        return $role->update($data);
    }

    public function delete(Role $role): bool
    {
        return $role->delete();
    }
}
