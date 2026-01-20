<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\RoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $roles = $this->roleService->getAll($perPage);

        return response()->json($roles);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create($request->validated());

        return response()->json($role, Response::HTTP_CREATED);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('users');
        return response()->json($role);
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        $this->roleService->update($role, $request->validated());
        $role->refresh();

        return response()->json($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->roleService->delete($role);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
