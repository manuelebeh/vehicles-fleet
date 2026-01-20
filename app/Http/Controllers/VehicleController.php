<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Requests\Vehicle\UpdateStatusRequest;
use App\Http\Requests\Vehicle\VehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\VehicleService;
use App\Traits\HandlesPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class VehicleController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected VehicleService $vehicleService
    ) {
    }

    #[OA\Get(
        path: "/vehicles",
        summary: "Liste des véhicules",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Liste des véhicules"),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $vehicles = $this->vehicleService->getAll($perPage);

        return VehicleResource::collection($vehicles)->response();
    }

    #[OA\Post(
        path: "/vehicles",
        summary: "Créer un véhicule",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["brand", "model", "license_plate"],
                properties: [
                    new OA\Property(property: "brand", type: "string", example: "Toyota"),
                    new OA\Property(property: "model", type: "string", example: "Corolla"),
                    new OA\Property(property: "license_plate", type: "string", example: "AB-123-CD"),
                    new OA\Property(property: "year", type: "integer", nullable: true, example: 2020),
                    new OA\Property(property: "color", type: "string", nullable: true, example: "Rouge"),
                    new OA\Property(property: "status", type: "string", enum: ["available", "maintenance", "out_of_service"], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Véhicule créé"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function store(VehicleRequest $request): JsonResponse
    {
        try {
            $vehicle = $this->vehicleService->create($request->validated());

            Log::info('Vehicle created', [
                'vehicle_id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'created_by' => auth()->id(),
            ]);

            return (new VehicleResource($vehicle))->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error creating vehicle', [
                'error' => $e->getMessage(),
                'license_plate' => $request->license_plate,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du véhicule.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/vehicles/{id}",
        summary: "Afficher un véhicule",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Détails du véhicule"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Véhicule non trouvé"),
        ]
    )]
    public function show(Vehicle $vehicle): JsonResponse
    {
        $vehicle->load('reservations');
        return (new VehicleResource($vehicle))->response();
    }

    #[OA\Put(
        path: "/vehicles/{id}",
        summary: "Mettre à jour un véhicule",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "brand", type: "string", nullable: true),
                    new OA\Property(property: "model", type: "string", nullable: true),
                    new OA\Property(property: "license_plate", type: "string", nullable: true),
                    new OA\Property(property: "year", type: "integer", nullable: true),
                    new OA\Property(property: "color", type: "string", nullable: true),
                    new OA\Property(property: "status", type: "string", enum: ["available", "maintenance", "out_of_service"], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Véhicule mis à jour"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
            new OA\Response(response: 404, description: "Véhicule non trouvé"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function update(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $this->vehicleService->update($vehicle, $request->validated());
            $vehicle->refresh();

            Log::info('Vehicle updated', [
                'vehicle_id' => $vehicle->id,
                'updated_by' => auth()->id(),
            ]);

            return (new VehicleResource($vehicle))->response();
        } catch (\Exception $e) {
            Log::error('Error updating vehicle', [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du véhicule.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: "/vehicles/{id}",
        summary: "Supprimer un véhicule",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 204, description: "Véhicule supprimé"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
            new OA\Response(response: 404, description: "Véhicule non trouvé"),
        ]
    )]
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        try {
            $vehicleId = $vehicle->id;
            $this->vehicleService->delete($vehicle);

            Log::info('Vehicle deleted', [
                'vehicle_id' => $vehicleId,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            Log::error('Error deleting vehicle', [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression du véhicule.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/vehicles/available",
        summary: "Liste des véhicules disponibles",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Liste des véhicules disponibles"),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function available(Request $request): JsonResponse
    {
        $vehicles = $this->vehicleService->getAvailable();

        return VehicleResource::collection($vehicles)->response();
    }

    #[OA\Patch(
        path: "/vehicles/{id}/status",
        summary: "Mettre à jour le statut d'un véhicule",
        tags: ["Vehicles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["available", "maintenance", "out_of_service"]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Statut mis à jour"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
            new OA\Response(response: 422, description: "Transition de statut invalide"),
        ]
    )]
    public function updateStatus(UpdateStatusRequest $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $oldStatus = $vehicle->status;
            $this->vehicleService->updateStatus($vehicle, $request->status);
            $vehicle->refresh();

            Log::info('Vehicle status updated', [
                'vehicle_id' => $vehicle->id,
                'old_status' => $oldStatus,
                'new_status' => $vehicle->status,
                'updated_by' => auth()->id(),
            ]);

            return (new VehicleResource($vehicle))->response();
        } catch (InvalidStatusTransitionException $e) {
            Log::warning('Invalid status transition for vehicle', [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'current_status' => $vehicle->status,
                'requested_status' => $request->status,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error updating vehicle status', [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'status' => $request->status,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du statut.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
