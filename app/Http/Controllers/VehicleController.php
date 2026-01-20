<?php

namespace App\Http\Controllers;

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

class VehicleController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected VehicleService $vehicleService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $vehicles = $this->vehicleService->getAll($perPage);

        return VehicleResource::collection($vehicles)->response();
    }

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

    public function show(Vehicle $vehicle): JsonResponse
    {
        $vehicle->load('reservations');
        return (new VehicleResource($vehicle))->response();
    }

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

    public function available(Request $request): JsonResponse
    {
        $vehicles = $this->vehicleService->getAvailable();

        return VehicleResource::collection($vehicles)->response();
    }

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
