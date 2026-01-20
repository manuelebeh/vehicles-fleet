<?php

namespace App\Http\Controllers;

use App\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ImportController extends Controller
{
    public function __construct(
        protected VehicleService $vehicleService
    ) {
    }

    #[OA\Post(
        path: "/import/vehicles",
        summary: "Importer des véhicules depuis un fichier CSV",
        tags: ["Import"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file"],
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary", description: "Fichier CSV à importer"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Import réussi",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "imported", type: "integer"),
                        new OA\Property(property: "failed", type: "integer"),
                        new OA\Property(property: "errors", type: "array", items: new OA\Items(type: "string")),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès non autorisé (admin uniquement)"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function importVehicles(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Seul un admin peut importer des véhicules
        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent importer des véhicules.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = null;
        $handle = null;
        
        try {
            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');
            
            if (!$handle) {
                return response()->json([
                    'message' => 'Impossible d\'ouvrir le fichier.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            // Ignore la première ligne (en-têtes)
            $headers = fgetcsv($handle, 1000, ';');
            if (!$headers || count($headers) < 3) {
                fclose($handle);
                return response()->json([
                    'message' => 'Le fichier CSV est vide ou invalide. Format attendu : brand;model;license_plate;year;color;status',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $imported = 0;
            $failed = 0;
            $errors = [];

            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($row) < 3) {
                    $failed++;
                    $errors[] = "Ligne invalide : " . implode(';', $row);
                    continue;
                }

                // Format attendu : brand, model, license_plate, year (optionnel), color (optionnel), status (optionnel)
                $data = [
                    'brand' => trim($row[0] ?? ''),
                    'model' => trim($row[1] ?? ''),
                    'license_plate' => trim($row[2] ?? ''),
                    'year' => !empty($row[3]) ? (int) trim($row[3]) : null,
                    'color' => trim($row[4] ?? ''),
                    'status' => trim($row[5] ?? 'available'),
                ];

                // Validation des données
                $rowValidator = Validator::make($data, [
                    'brand' => 'required|string|max:100',
                    'model' => 'required|string|max:100',
                    'license_plate' => 'required|string|max:20|unique:vehicles,license_plate',
                    'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                    'color' => 'nullable|string|max:50',
                    'status' => 'nullable|string|in:available,maintenance,out_of_service',
                ]);

                if ($rowValidator->fails()) {
                    $failed++;
                    $errors[] = "Ligne avec plaque {$data['license_plate']}: " . implode(', ', $rowValidator->errors()->all());
                    continue;
                }

                try {
                    $this->vehicleService->create($data);
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Erreur lors de l'import de la ligne avec plaque {$data['license_plate']}: " . $e->getMessage();
                }
            }

            Log::info('Vehicles imported', [
                'imported' => $imported,
                'failed' => $failed,
                'imported_by' => $user->id,
            ]);

            return response()->json([
                'message' => "Import terminé. {$imported} véhicule(s) importé(s), {$failed} échec(s).",
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('Error importing vehicles', [
                'error' => $e->getMessage(),
                'imported_by' => $user->id,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'import des véhicules.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } finally {
            if ($handle) {
                fclose($handle);
            }
        }
    }
}
