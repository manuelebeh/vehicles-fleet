<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class StatisticsController extends Controller
{
    public function __construct(
        protected StatisticsService $statisticsService
    ) {
    }

    #[OA\Get(
        path: "/statistics/general",
        summary: "Obtenir les statistiques générales",
        tags: ["Statistics"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Statistiques générales",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "vehicles", type: "object"),
                        new OA\Property(property: "reservations", type: "object"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function general(Request $request): JsonResponse
    {
        try {
            $statistics = $this->statisticsService->getGeneralStatistics();

            return response()->json([
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching general statistics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des statistiques.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/statistics/reservations-by-month",
        summary: "Obtenir le nombre de réservations par mois",
        tags: ["Statistics"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "months", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 12)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Réservations par mois",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "month", type: "string"),
                            new OA\Property(property: "month_label", type: "string"),
                            new OA\Property(property: "count", type: "integer"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function reservationsByMonth(Request $request): JsonResponse
    {
        try {
            $months = (int) $request->get('months', 12);
            $statistics = $this->statisticsService->getReservationsByMonth($months);

            return response()->json([
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reservations by month', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des statistiques.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/statistics/most-used-vehicles",
        summary: "Obtenir les véhicules les plus utilisés",
        tags: ["Statistics"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Véhicules les plus utilisés",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "brand", type: "string"),
                            new OA\Property(property: "model", type: "string"),
                            new OA\Property(property: "license_plate", type: "string"),
                            new OA\Property(property: "full_name", type: "string"),
                            new OA\Property(property: "reservation_count", type: "integer"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function mostUsedVehicles(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 10);
            $statistics = $this->statisticsService->getMostUsedVehicles($limit);

            return response()->json([
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching most used vehicles', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des statistiques.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/statistics/vehicle-occupancy",
        summary: "Obtenir le taux d'occupation des véhicules",
        tags: ["Statistics"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "start_date", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "end_date", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Taux d'occupation des véhicules",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "vehicle_id", type: "integer"),
                            new OA\Property(property: "brand", type: "string"),
                            new OA\Property(property: "model", type: "string"),
                            new OA\Property(property: "license_plate", type: "string"),
                            new OA\Property(property: "full_name", type: "string"),
                            new OA\Property(property: "occupied_days", type: "integer"),
                            new OA\Property(property: "total_days", type: "integer"),
                            new OA\Property(property: "occupancy_rate", type: "number"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function vehicleOccupancy(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date') 
                ? Carbon::parse($request->get('start_date')) 
                : Carbon::now()->startOfMonth();
            
            $endDate = $request->get('end_date') 
                ? Carbon::parse($request->get('end_date')) 
                : Carbon::now()->endOfMonth();

            $statistics = $this->statisticsService->getVehicleOccupancyRate($startDate, $endDate);

            return response()->json([
                'data' => $statistics,
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching vehicle occupancy', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des statistiques.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
