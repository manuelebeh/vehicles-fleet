<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleService;
use App\Traits\HandlesPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected RoleService $roleService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $roles = $this->roleService->getAll($perPage);

        return RoleResource::collection($roles)->response();
    }

    public function store(RoleRequest $request): JsonResponse
    {
        try {
            $role = $this->roleService->create($request->validated());

            Log::info('Role created', [
                'role_id' => $role->id,
                'name' => $role->name,
                'created_by' => auth()->id(),
            ]);

            return (new RoleResource($role))->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error creating role', [
                'error' => $e->getMessage(),
                'name' => $request->name,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du rôle.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('users');
        return (new RoleResource($role))->response();
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        try {
            $this->roleService->update($role, $request->validated());
            $role->refresh();

            Log::info('Role updated', [
                'role_id' => $role->id,
                'updated_by' => auth()->id(),
            ]);

            return (new RoleResource($role))->response();
        } catch (\Exception $e) {
            Log::error('Error updating role', [
                'error' => $e->getMessage(),
                'role_id' => $role->id,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du rôle.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Role $role): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $roleId = $role->id;
            $this->roleService->delete($role);

            Log::info('Role deleted', [
                'role_id' => $roleId,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            Log::error('Error deleting role', [
                'error' => $e->getMessage(),
                'role_id' => $role->id,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression du rôle.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
