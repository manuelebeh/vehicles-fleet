<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReservationService;
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
        try {
            $reservation = $this->reservationService->create($request->validated());
            return response()->json($reservation, Response::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la réservation', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
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
        try {
            $this->reservationService->update($reservation, $request->validated());
            return $this->refreshReservation($reservation);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la réservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'data' => $request->validated(),
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
        return $this->refreshReservation($reservation);
    }

    public function confirm(Reservation $reservation): JsonResponse
    {
        try {
            $this->reservationService->confirm($reservation);
            return $this->refreshReservation($reservation);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        }
    }

    public function complete(Reservation $reservation): JsonResponse
    {
        $this->reservationService->complete($reservation);
        return $this->refreshReservation($reservation);
    }

    private function refreshReservation(Reservation $reservation): JsonResponse
    {
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
