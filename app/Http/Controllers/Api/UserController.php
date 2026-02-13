<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Post(
        path: "/api/v1/register",
        summary: "Inscription d'un utilisateur",
        tags: ["Auth"],
        parameters: [
            new OA\Parameter(
                name: "Accept",
                in: "header",
                required: true,
                description: "Format de réponse attendu",
                schema: new OA\Schema(type: "string", example: "application/json")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password"],
                properties: [
                    new OA\Property(property: "name", type: "string", maxLength: 255, example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", maxLength: 255, example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", minLength: 8, example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Utilisateur créé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "user",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "John Doe"),
                                new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-02-10T10:15:30Z"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2024-02-10T10:15:30Z")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Erreur de validation",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            example: [
                                "email" => ["The email has already been taken."],
                                "password" => ["The password must be at least 8 characters."]
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'user' => $user,
        ], 201);
    }

    #[OA\Post(
        path: "/api/v1/login",
        summary: "Connexion utilisateur et génération d'un token",
        tags: ["Auth"],
        parameters: [
            new OA\Parameter(
                name: "Accept",
                in: "header",
                required: true,
                description: "Format de réponse attendu",
                schema: new OA\Schema(type: "string", example: "application/json")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Connexion réussie",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "user",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "John Doe"),
                                new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-02-10T10:15:30Z"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2024-02-10T10:15:30Z")
                            ]
                        ),
                        new OA\Property(
                            property: "token",
                            type: "string",
                            example: "1|XyZabc123456789"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Identifiants invalides",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Invalid credentials"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Erreur de validation",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            example: [
                                "email" => ["The email field is required."],
                                "password" => ["The password field is required."]
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    #[OA\Post(
        path: "/api/v1/logout",
        summary: "Déconnexion de l'utilisateur (suppression du token courant)",
        tags: ["Auth"],
        parameters: [
            new OA\Parameter(
                name: "Accept",
                in: "header",
                required: true,
                description: "Format de réponse attendu",
                schema: new OA\Schema(type: "string", example: "application/json")
            ),
            new OA\Parameter(
                name: "Authorization",
                in: "header",
                required: true,
                description: "Token d'authentification",
                schema: new OA\Schema(
                    type: "string",
                    example: "Bearer 1|XyZabc123456..."
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Déconnexion réussie",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Logged out")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
                    ]
                )
            )
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
