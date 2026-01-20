<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
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

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $this->userService->assignRole($user, $role);
        $user->load('roles');

        return response()->json($user);
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $this->userService->removeRole($user, $role);
        $user->load('roles');

        return response()->json($user);
    }

    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $this->userService->syncRoles($user, $request->role_ids);
        $user->load('roles');

        return response()->json($user);
    }
}
