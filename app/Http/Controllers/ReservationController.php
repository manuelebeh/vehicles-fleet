<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\ReservationConflictException;
use App\Exceptions\VehicleNotAvailableException;
use App\Http\Requests\Reservation\AvailableVehiclesRequest;
use App\Http\Requests\Reservation\ReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Http\Resources\VehicleResource;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReservationService;
use App\Traits\HandlesPagination;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected ReservationService $reservationService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $reservations = $this->reservationService->getAll($perPage);

        return ReservationResource::collection($reservations)->response();
    }

    public function store(ReservationRequest $request): JsonResponse
    {
        try {
            $reservation = $this->reservationService->create($request->validated());

            Log::info('Reservation created', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'vehicle_id' => $reservation->vehicle_id,
                'start_date' => $reservation->start_date,
                'end_date' => $reservation->end_date,
                'created_by' => auth()->id(),
            ]);

            return (new ReservationResource($reservation))->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (ReservationConflictException|VehicleNotAvailableException $e) {
            Log::warning('Reservation creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id,
                'vehicle_id' => $request->vehicle_id,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            Log::error('Error creating reservation', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de la réservation.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load(['user', 'vehicle']);
        return (new ReservationResource($reservation))->response();
    }

    public function update(ReservationRequest $request, Reservation $reservation): JsonResponse
    {
        try {
            $this->reservationService->update($reservation, $request->validated());
            return $this->refreshReservation($reservation);
        } catch (ReservationConflictException|VehicleNotAvailableException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (InvalidStatusTransitionException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
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
        try {
            $this->reservationService->cancel($reservation);

            Log::info('Reservation cancelled', [
                'reservation_id' => $reservation->id,
                'cancelled_by' => auth()->id(),
            ]);

            return $this->refreshReservation($reservation);
        } catch (\Exception $e) {
            Log::error('Error cancelling reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'annulation de la réservation.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function confirm(Reservation $reservation): JsonResponse
    {
        try {
            $this->reservationService->confirm($reservation);

            Log::info('Reservation confirmed', [
                'reservation_id' => $reservation->id,
                'confirmed_by' => auth()->id(),
            ]);

            return $this->refreshReservation($reservation);
        } catch (ReservationConflictException|VehicleNotAvailableException $e) {
            Log::warning('Reservation confirmation failed', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'confirmed_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (InvalidStatusTransitionException $e) {
            Log::warning('Invalid status transition for reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'current_status' => $reservation->status,
                'confirmed_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function complete(Reservation $reservation): JsonResponse
    {
        try {
            $this->reservationService->complete($reservation);

            Log::info('Reservation completed', [
                'reservation_id' => $reservation->id,
                'completed_by' => auth()->id(),
            ]);

            return $this->refreshReservation($reservation);
        } catch (\Exception $e) {
            Log::error('Error completing reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la finalisation de la réservation.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function refreshReservation(Reservation $reservation): JsonResponse
    {
        $reservation->refresh();
        $reservation->load(['user', 'vehicle']);
        return (new ReservationResource($reservation))->response();
    }

    public function byUser(User $user, Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $reservations = $this->reservationService->getByUser($user, $perPage);

        return response()->json($reservations);
    }

    public function byVehicle(Vehicle $vehicle, Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $reservations = $this->reservationService->getByVehicle($vehicle, $perPage);

        return response()->json($reservations);
    }

    public function availableVehicles(AvailableVehiclesRequest $request): JsonResponse
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $vehicles = $this->reservationService->getAvailableVehicles($startDate, $endDate);

        return VehicleResource::collection($vehicles)->response();
    }
}
