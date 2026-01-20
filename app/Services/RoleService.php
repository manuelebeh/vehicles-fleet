<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class RoleService
{
    private const CACHE_KEY_ALL_ROLES = 'roles:all';
    private const CACHE_TTL = 3600; // 1 heure

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Role::paginate($perPage);
    }

    public function getAllWithoutPagination(): Collection
    {
        return Cache::remember(self::CACHE_KEY_ALL_ROLES, self::CACHE_TTL, function () {
            return Role::all();
        });
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
        $role = Role::create($data);
        $this->clearCache();
        
        return $role;
    }

    public function update(Role $role, array $data): bool
    {
        $result = $role->update($data);
        $this->clearCache();
        
        return $result;
    }

    public function delete(Role $role): bool
    {
        $result = $role->delete();
        $this->clearCache();
        
        return $result;
    }

    /**
     * Invalide le cache des rôles
     */
    private function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL_ROLES);
    }
}
