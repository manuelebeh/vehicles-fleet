<?php

namespace App\Http\Controllers\Web;

use App\Enums\VehicleStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\UpdateStatusRequest;
use App\Http\Requests\Vehicle\VehicleRequest;
use App\Models\Vehicle;
use App\Services\VehicleService;
use App\Traits\HandlesPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VehicleController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected VehicleService $vehicleService
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $perPage = $this->getPerPage($request);
        $vehicles = $this->vehicleService->getAll($perPage);

        return Inertia::render('Admin/Vehicles/Index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Vehicles/Create', [
            'statuses' => VehicleStatus::all(),
        ]);
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        try {
            $vehicle = $this->vehicleService->create($request->validated());

            Log::info('Vehicle created', [
                'vehicle_id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.vehicles.index')
                ->with('success', 'Véhicule créé avec succès.');
        } catch (\Exception $e) {
            Log::error('Error creating vehicle', [
                'error' => $e->getMessage(),
                'license_plate' => $request->license_plate,
                'created_by' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la création du véhicule.',
            ])->withInput();
        }
    }

    public function show(Vehicle $vehicle): InertiaResponse
    {
        $vehicle->load('reservations');
        $statuses = VehicleStatus::all();

        return Inertia::render('Admin/Vehicles/Show', [
            'vehicle' => $vehicle,
            'statuses' => $statuses,
        ]);
    }

    public function edit(Vehicle $vehicle): InertiaResponse
    {
        $statuses = VehicleStatus::all();

        return Inertia::render('Admin/Vehicles/Edit', [
            'vehicle' => $vehicle,
            'statuses' => $statuses,
        ]);
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        try {
            $this->vehicleService->update($vehicle, $request->validated());
            $vehicle->refresh();

            Log::info('Vehicle updated', [
                'vehicle_id' => $vehicle->id,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.vehicles.show', $vehicle)
                ->with('success', 'Véhicule mis à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Error updating vehicle', [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'updated_by' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la mise à jour du véhicule.',
            ])->withInput();
        }
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        try {
            $vehicleId = $vehicle->id;
            $licensePlate = $vehicle->license_plate;
            $this->vehicleService->delete($vehicle);

            Log::info('Vehicle deleted', [
                'vehicle_id' => $vehicleId,
                'license_plate' => $licensePlate,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.vehicles.index')
                ->with('success', 'Véhicule supprimé avec succès.');
        } catch (\Exception $e) {
            Log::error('Error deleting vehicle', [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Une erreur est survenue lors de la suppression du véhicule.');
        }
    }

    public function updateStatus(UpdateStatusRequest $request, Vehicle $vehicle): RedirectResponse
    {
        try {
            $this->vehicleService->updateStatus($vehicle, $request->status);
            $vehicle->refresh();

            Log::info('Vehicle status updated', [
                'vehicle_id' => $vehicle->id,
                'status' => $request->status,
                'updated_by' => auth()->id(),
            ]);

            return back()->with('success', 'Statut du véhicule mis à jour avec succès.');
        } catch (InvalidStatusTransitionException $e) {
            return back()->withErrors([
                'status' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating vehicle status', [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'status' => $request->status,
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la mise à jour du statut.',
            ]);
        }
    }
}
