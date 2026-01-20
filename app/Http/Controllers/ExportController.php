<?php

namespace App\Http\Controllers;

use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {
    }

    #[OA\Get(
        path: "/export/reservations",
        summary: "Exporter les réservations en CSV",
        tags: ["Export"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "format", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["csv"], default: "csv"), description: "Format d'export (actuellement seul CSV est supporté)"),
            new OA\Parameter(name: "start_date", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "end_date", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Fichier CSV téléchargé"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
        ]
    )]
    public function exportReservations(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        
        // Seul un admin peut exporter toutes les réservations
        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent exporter les réservations.',
            ], Response::HTTP_FORBIDDEN);
        }

        $format = $request->get('format', 'csv');
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null;
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null;
        $status = $request->get('status');

        try {
            $reservations = $this->reservationService->getAllWithoutPagination();

            // Filtrer par date si fourni
            if ($startDate) {
                $reservations = $reservations->filter(function ($reservation) use ($startDate) {
                    return $reservation->start_date->gte($startDate);
                });
            }

            if ($endDate) {
                $reservations = $reservations->filter(function ($reservation) use ($endDate) {
                    return $reservation->end_date->lte($endDate);
                });
            }

            // Filtrer par statut si fourni
            if ($status) {
                $reservations = $reservations->filter(function ($reservation) use ($status) {
                    return $reservation->status === $status;
                });
            }

            $filename = 'reservations_' . date('Y-m-d_His') . '.csv';

            Log::info('Reservations exported', [
                'format' => $format,
                'count' => $reservations->count(),
                'exported_by' => $user->id,
            ]);

            return $this->generateCsvResponse($reservations, $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting reservations', [
                'error' => $e->getMessage(),
                'exported_by' => $user->id,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'export des réservations.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function generateCsvResponse($reservations, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($reservations) {
            $file = fopen('php://output', 'w');

            // Ajouter BOM pour Excel UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // En-têtes
            fputcsv($file, [
                'ID',
                'Utilisateur',
                'Email Utilisateur',
                'Véhicule',
                'Plaque d\'immatriculation',
                'Date de début',
                'Date de fin',
                'Statut',
                'Raison',
                'Créé le',
                'Mis à jour le',
            ], ';');

            // Données
            foreach ($reservations as $reservation) {
                fputcsv($file, [
                    $reservation->id,
                    $reservation->user ? ($reservation->user->first_name . ' ' . $reservation->user->last_name) : 'N/A',
                    $reservation->user ? $reservation->user->email : 'N/A',
                    $reservation->vehicle ? $reservation->vehicle->full_name : 'N/A',
                    $reservation->vehicle ? $reservation->vehicle->license_plate : 'N/A',
                    $reservation->start_date->format('Y-m-d H:i:s'),
                    $reservation->end_date->format('Y-m-d H:i:s'),
                    $reservation->status,
                    $reservation->purpose ?? '',
                    $reservation->created_at->format('Y-m-d H:i:s'),
                    $reservation->updated_at->format('Y-m-d H:i:s'),
                ], ';');
            }

            fclose($file);
        }, Response::HTTP_OK, $headers);
    }
}
