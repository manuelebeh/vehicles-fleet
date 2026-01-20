<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    public function __construct(
        protected VehicleService $vehicleService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $vehicles = $this->vehicleService->getAll($perPage);

        return response()->json($vehicles);
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        $vehicle = $this->vehicleService->create($request->validated());

        return response()->json($vehicle, Response::HTTP_CREATED);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        $vehicle->load('reservations');
        return response()->json($vehicle);
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->vehicleService->update($vehicle, $request->validated());
        $vehicle->refresh();

        return response()->json($vehicle);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->vehicleService->delete($vehicle);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function available(Request $request): JsonResponse
    {
        $vehicles = $this->vehicleService->getAvailable();

        return response()->json($vehicles);
    }

    public function updateStatus(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:available,maintenance,out_of_service',
        ]);

        $this->vehicleService->updateStatus($vehicle, $request->status);
        $vehicle->refresh();

        return response()->json($vehicle);
    }
}
