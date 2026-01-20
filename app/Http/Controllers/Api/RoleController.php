<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleService;
use App\Traits\HandlesPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected RoleService $roleService
    ) {
    }

    #[OA\Get(
        path: "/roles",
        summary: "Liste des rôles",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Liste des rôles"),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $roles = $this->roleService->getAll($perPage);

        return RoleResource::collection($roles)->response();
    }

    #[OA\Post(
        path: "/roles",
        summary: "Créer un rôle",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "manager"),
                    new OA\Property(property: "display_name", type: "string", nullable: true, example: "Manager"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Rôle créé"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
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

    #[OA\Get(
        path: "/roles/{id}",
        summary: "Afficher un rôle",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Détails du rôle"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Rôle non trouvé"),
        ]
    )]
    public function show(Role $role): JsonResponse
    {
        $role->load('users');
        return (new RoleResource($role))->response();
    }

    #[OA\Put(
        path: "/roles/{id}",
        summary: "Mettre à jour un rôle",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", nullable: true),
                    new OA\Property(property: "display_name", type: "string", nullable: true),
                    new OA\Property(property: "description", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Rôle mis à jour"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
            new OA\Response(response: 404, description: "Rôle non trouvé"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
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

    #[OA\Delete(
        path: "/roles/{id}",
        summary: "Supprimer un rôle",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 204, description: "Rôle supprimé"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
            new OA\Response(response: 404, description: "Rôle non trouvé"),
        ]
    )]
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
