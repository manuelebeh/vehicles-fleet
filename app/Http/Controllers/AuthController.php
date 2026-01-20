<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    #[OA\Post(
        path: "/auth/login",
        summary: "Connexion utilisateur",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Connexion réussie",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "user", type: "object"),
                        new OA\Property(property: "token", type: "string", example: "1|xxxxxxxxxxxx"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Identifiants invalides"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login(
            $request->email,
            $request->password
        );

        if (!$user) {
            Log::warning('Failed login attempt', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Identifiants invalides.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->authService->createToken($user, 'login-token');
        $user->load('roles');

        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    #[OA\Post(
        path: "/auth/logout",
        summary: "Déconnexion utilisateur",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Déconnexion réussie",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Déconnexion réussie."),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->authService->revokeCurrentToken($user);

            Log::info('User logged out', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Déconnexion réussie.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error during logout', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la déconnexion.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/auth/me",
        summary: "Récupérer les informations de l'utilisateur connecté",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Informations utilisateur",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles');

        return (new UserResource($user))->response();
    }
}
