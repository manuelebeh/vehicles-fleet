<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use OpenApi\Attributes as OA;

class ReservationController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected ReservationService $reservationService
    ) {
    }

    #[OA\Get(
        path: "/reservations",
        summary: "Liste des réservations",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Liste des réservations"),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $reservations = $this->reservationService->getAll($perPage);

        return ReservationResource::collection($reservations)->response();
    }

    #[OA\Post(
        path: "/reservations",
        summary: "Créer une réservation",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id", "vehicle_id", "start_date", "end_date"],
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "vehicle_id", type: "integer", example: 1),
                    new OA\Property(property: "start_date", type: "string", format: "date-time", example: "2024-01-21T10:00:00Z"),
                    new OA\Property(property: "end_date", type: "string", format: "date-time", example: "2024-01-21T14:00:00Z"),
                    new OA\Property(property: "purpose", type: "string", nullable: true, example: "Déplacement professionnel"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Réservation créée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé"),
            new OA\Response(response: 409, description: "Conflit (véhicule non disponible ou déjà réservé)"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
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

    #[OA\Get(
        path: "/reservations/{id}",
        summary: "Afficher une réservation",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Détails de la réservation"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé"),
            new OA\Response(response: 404, description: "Réservation non trouvée"),
        ]
    )]
    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load(['user', 'vehicle']);
        return (new ReservationResource($reservation))->response();
    }

    #[OA\Put(
        path: "/reservations/{id}",
        summary: "Mettre à jour une réservation",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "user_id", type: "integer", nullable: true),
                    new OA\Property(property: "vehicle_id", type: "integer", nullable: true),
                    new OA\Property(property: "start_date", type: "string", format: "date-time", nullable: true),
                    new OA\Property(property: "end_date", type: "string", format: "date-time", nullable: true),
                    new OA\Property(property: "purpose", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Réservation mise à jour"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé"),
            new OA\Response(response: 409, description: "Conflit (véhicule non disponible ou déjà réservé)"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
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

    #[OA\Delete(
        path: "/reservations/{id}",
        summary: "Supprimer une réservation",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 204, description: "Réservation supprimée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé"),
            new OA\Response(response: 404, description: "Réservation non trouvée"),
        ]
    )]
    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->reservationService->delete($reservation);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Post(
        path: "/reservations/{id}/cancel",
        summary: "Annuler une réservation",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Réservation annulée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé"),
            new OA\Response(response: 422, description: "Transition de statut invalide"),
        ]
    )]
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

    #[OA\Post(
        path: "/reservations/{id}/confirm",
        summary: "Confirmer une réservation",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Réservation confirmée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé"),
            new OA\Response(response: 409, description: "Conflit (véhicule déjà réservé)"),
            new OA\Response(response: 422, description: "Transition de statut invalide"),
        ]
    )]
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

    #[OA\Post(
        path: "/reservations/{id}/complete",
        summary: "Finaliser une réservation",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Réservation finalisée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé"),
            new OA\Response(response: 422, description: "Transition de statut invalide"),
        ]
    )]
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

    #[OA\Get(
        path: "/users/{id}/reservations",
        summary: "Liste des réservations d'un utilisateur",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Liste des réservations de l'utilisateur"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Utilisateur non trouvé"),
        ]
    )]
    public function byUser(User $user, Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $reservations = $this->reservationService->getByUser($user, $perPage);

        return response()->json($reservations);
    }

    #[OA\Get(
        path: "/vehicles/{id}/reservations",
        summary: "Liste des réservations d'un véhicule",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Liste des réservations du véhicule"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Véhicule non trouvé"),
        ]
    )]
    public function byVehicle(Vehicle $vehicle, Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $reservations = $this->reservationService->getByVehicle($vehicle, $perPage);

        return response()->json($reservations);
    }

    #[OA\Get(
        path: "/reservations/available-vehicles",
        summary: "Rechercher les véhicules disponibles pour une période",
        tags: ["Reservations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "start_date", in: "query", required: true, schema: new OA\Schema(type: "string", format: "date-time")),
            new OA\Parameter(name: "end_date", in: "query", required: true, schema: new OA\Schema(type: "string", format: "date-time")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Liste des véhicules disponibles"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function availableVehicles(AvailableVehiclesRequest $request): JsonResponse
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $vehicles = $this->reservationService->getAvailableVehicles($startDate, $endDate);

        return VehicleResource::collection($vehicles)->response();
    }
}
