<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $reservations = $this->reservationService->getAll($perPage);

        return response()->json($reservations);
    }

    public function store(ReservationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        if (!$vehicle->isAvailable()) {
            return response()->json([
                'message' => 'Le véhicule n\'est pas disponible.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->reservationService->checkAvailability($vehicle, $startDate, $endDate)) {
            return response()->json([
                'message' => 'Le véhicule est déjà réservé pour cette période.',
            ], Response::HTTP_CONFLICT);
        }

        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        try {
            $reservation = $this->reservationService->create($data);
            return response()->json($reservation, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la réservation', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de la réservation.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load(['user', 'vehicle']);
        return response()->json($reservation);
    }

    public function update(ReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validated();
        $vehicle = $reservation->vehicle;

        if (isset($data['vehicle_id']) && $data['vehicle_id'] != $reservation->vehicle_id) {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        }

        if (isset($data['start_date']) || isset($data['end_date'])) {
            $startDate = isset($data['start_date'])
                ? Carbon::parse($data['start_date'])
                : $reservation->start_date;
            $endDate = isset($data['end_date'])
                ? Carbon::parse($data['end_date'])
                : $reservation->end_date;

            if ($reservation->isConfirmed() && !$this->reservationService->checkAvailability(
                $vehicle,
                $startDate,
                $endDate,
                $reservation->id
            )) {
                return response()->json([
                    'message' => 'Le véhicule est déjà réservé pour cette période.',
                ], Response::HTTP_CONFLICT);
            }
        }

        try {
            $this->reservationService->update($reservation, $data);
            $reservation->refresh();
            $reservation->load(['user', 'vehicle']);

            return response()->json($reservation);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la réservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'data' => $data,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de la réservation.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->reservationService->delete($reservation);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        $this->reservationService->cancel($reservation);
        $reservation->refresh();
        $reservation->load(['user', 'vehicle']);

        return response()->json($reservation);
    }

    public function confirm(Reservation $reservation): JsonResponse
    {
        $vehicle = $reservation->vehicle;

        if (!$this->reservationService->checkAvailability(
            $vehicle,
            $reservation->start_date,
            $reservation->end_date,
            $reservation->id
        )) {
            return response()->json([
                'message' => 'Le véhicule est déjà réservé pour cette période.',
            ], Response::HTTP_CONFLICT);
        }

        $this->reservationService->confirm($reservation);
        $reservation->refresh();
        $reservation->load(['user', 'vehicle']);

        return response()->json($reservation);
    }

    public function complete(Reservation $reservation): JsonResponse
    {
        $this->reservationService->complete($reservation);
        $reservation->refresh();
        $reservation->load(['user', 'vehicle']);

        return response()->json($reservation);
    }

    public function byUser(User $user, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $reservations = $this->reservationService->getByUser($user, $perPage);

        return response()->json($reservations);
    }

    public function byVehicle(Vehicle $vehicle, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $reservations = $this->reservationService->getByVehicle($vehicle, $perPage);

        return response()->json($reservations);
    }

    public function availableVehicles(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:now',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $vehicles = $this->reservationService->getAvailableVehicles($startDate, $endDate);

        return response()->json($vehicles);
    }
}
