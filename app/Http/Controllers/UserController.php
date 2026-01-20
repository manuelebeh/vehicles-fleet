<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\AssignRoleRequest;
use App\Http\Requests\User\RemoveRoleRequest;
use App\Http\Requests\User\SyncRolesRequest;
use App\Http\Requests\User\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use App\Traits\HandlesPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected UserService $userService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $users = $this->userService->getAll($perPage);

        return UserResource::collection($users)->response();
    }

    public function store(UserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());
            $user->load('roles');

            Log::info('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_by' => auth()->id(),
            ]);

            return (new UserResource($user))->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error creating user', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de l\'utilisateur.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles', 'reservations');
        return (new UserResource($user))->response();
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        try {
            $this->userService->update($user, $request->validated());
            $user->refresh();
            $user->load('roles');

            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
            ]);

            return (new UserResource($user))->response();
        } catch (\Exception $e) {
            Log::error('Error updating user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'utilisateur.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $userId = $user->id;
            $this->userService->delete($user);

            Log::info('User deleted', [
                'user_id' => $userId,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            Log::error('Error deleting user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression de l\'utilisateur.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        try {
            $role = Role::findOrFail($request->role_id);
            $this->userService->assignRole($user, $role);
            $user->load('roles');

            Log::info('Role assigned to user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'assigned_by' => auth()->id(),
            ]);

            return (new UserResource($user))->response();
        } catch (\Exception $e) {
            Log::error('Error assigning role to user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'role_id' => $request->role_id,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'assignation du rôle.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeRole(RemoveRoleRequest $request, User $user): JsonResponse
    {
        try {
            $role = Role::findOrFail($request->role_id);
            $this->userService->removeRole($user, $role);
            $user->load('roles');

            Log::info('Role removed from user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'removed_by' => auth()->id(),
            ]);

            return (new UserResource($user))->response();
        } catch (\Exception $e) {
            Log::error('Error removing role from user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'role_id' => $request->role_id,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression du rôle.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function syncRoles(SyncRolesRequest $request, User $user): JsonResponse
    {
        try {
            $this->userService->syncRoles($user, $request->role_ids);
            $user->load('roles');

            Log::info('Roles synced for user', [
                'user_id' => $user->id,
                'role_ids' => $request->role_ids,
                'synced_by' => auth()->id(),
            ]);

            return (new UserResource($user))->response();
        } catch (\Exception $e) {
            Log::error('Error syncing roles for user', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'role_ids' => $request->role_ids,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la synchronisation des rôles.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
