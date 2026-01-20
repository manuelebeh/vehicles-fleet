<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\AssignRoleRequest;
use App\Http\Requests\User\RemoveRoleRequest;
use App\Http\Requests\User\SyncRolesRequest;
use App\Http\Requests\User\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use App\Traits\HandlesPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        return response()->json($users);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        $user->load('roles');

        return response()->json($user, Response::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles', 'reservations');
        return response()->json($user);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $this->userService->update($user, $request->validated());
        $user->refresh();
        $user->load('roles');

        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $role = Role::findOrFail($request->role_id);
        $this->userService->assignRole($user, $role);
        $user->load('roles');

        return response()->json($user);
    }

    public function removeRole(RemoveRoleRequest $request, User $user): JsonResponse
    {
        $role = Role::findOrFail($request->role_id);
        $this->userService->removeRole($user, $role);
        $user->load('roles');

        return response()->json($user);
    }

    public function syncRoles(SyncRolesRequest $request, User $user): JsonResponse
    {
        $this->userService->syncRoles($user, $request->role_ids);
        $user->load('roles');

        return response()->json($user);
    }
}
