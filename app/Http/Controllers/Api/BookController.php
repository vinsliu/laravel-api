<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Resources\BookResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    #[OA\Get(
        path: "/api/v1/books",
        summary: "Liste paginée des livres",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Numéro de page",
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "Accept",
                in: "header",
                required: true,
                description: "Format de réponse attendu",
                schema: new OA\Schema(type: "string", example: "application/json")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste paginée des livres",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "title", type: "string", example: "1984"),
                                    new OA\Property(property: "author", type: "string", example: "GEORGE ORWELL"),
                                    new OA\Property(property: "summary", type: "string", example: "Roman dystopique décrivant une société totalitaire."),
                                    new OA\Property(property: "isbn", type: "string", example: "9780451524935")
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "links",
                            type: "object",
                            example: [
                                "first" => "http://localhost:8000/api/v1/books?page=1",
                                "last" => "http://localhost:8000/api/v1/books?page=5",
                                "prev" => null,
                                "next" => "http://localhost:8000/api/v1/books?page=2"
                            ]
                        ),
                        new OA\Property(
                            property: "meta",
                            type: "object",
                            example: [
                                "current_page" => 1,
                                "from" => 1,
                                "last_page" => 5,
                                "per_page" => 2,
                                "to" => 2,
                                "total" => 10
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $books = Book::paginate(2);

        return BookResource::collection($books);
    }

    #[OA\Post(
        path: "/api/v1/books",
        summary: "Créer un nouveau livre",
        tags: ["Books"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "Accept",
                in: "header",
                required: true,
                schema: new OA\Schema(type: "string", example: "application/json"),
                description: "Le type de contenu attendu par l’API"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "author", "summary", "isbn"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Le Petit Prince"),
                    new OA\Property(property: "author", type: "string", example: "ANTOINE DE SAINT-EXUPÉRY"),
                    new OA\Property(property: "summary", type: "string", example: "Conte poétique et philosophique racontant l'histoire d'un petit prince voyageant de planète en planète."),
                    new OA\Property(property: "isbn", type: "string", example: "9782070612758")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Livre créé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "Le Petit Prince"),
                        new OA\Property(property: "author", type: "string", example: "ANTOINE DE SAINT-EXUPÉRY"),
                        new OA\Property(property: "summary", type: "string", example: "Conte poétique et philosophique racontant l'histoire d'un petit prince voyageant de planète en planète."),
                        new OA\Property(property: "isbn", type: "string", example: "9782070612758")
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
                                "title" => ["The title field is required."],
                                "isbn" => ["The isbn has already been taken."]
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'author' => 'required|string|min:3|max:100',
            'summary' => 'required|string|min:10|max:500',
            'isbn' => 'required|string|size:13|unique:books,isbn',
        ]);

        $book = Book::create($validated);

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: "/api/v1/books/{id}",
        summary: "Afficher le détail d’un livre",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du livre",
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "Accept",
                in: "header",
                required: true,
                description: "Format de réponse attendu",
                schema: new OA\Schema(type: "string", example: "application/json")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails du livre",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "1984"),
                        new OA\Property(property: "author", type: "string", example: "GEORGE ORWELL"),
                        new OA\Property(property: "summary", type: "string", example: "Roman dystopique décrivant une société totalitaire contrôlée par Big Brother."),
                        new OA\Property(property: "isbn", type: "string", example: "9780451524935"),
                        new OA\Property(
                            property: "_links",
                            type: "object",
                            properties: [
                                new OA\Property(property: "self", type: "string", example: "http://localhost:8000/api/v1/books/1"),
                                new OA\Property(property: "update", type: "string", example: "http://localhost:8000/api/v1/books/1"),
                                new OA\Property(property: "delete", type: "string", example: "http://localhost:8000/api/v1/books/1"),
                                new OA\Property(property: "all", type: "string", example: "http://localhost:8000/api/v1/books")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Livre non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "No query results for model [App\\Models\\Book] 999")
                    ]
                )
            )
        ]
    )]
    public function show(Book $book)
    {
        $cachedBook = Cache::remember(
            'book_' . $book->id,
            now()->addMinutes(60),
            function () use ($book) {
                return $book;
            }
        );

        return new BookResource($cachedBook);
    }

    #[OA\Put(
        path: "/api/v1/books/{id}",
        summary: "Mettre à jour un livre",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du livre",
                schema: new OA\Schema(type: "integer", example: 1)
            ),
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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "author", "summary", "isbn"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "1984"),
                    new OA\Property(property: "author", type: "string", example: "GEORGE ORWELL"),
                    new OA\Property(property: "summary", type: "string", example: "Roman dystopique mis à jour."),
                    new OA\Property(property: "isbn", type: "string", example: "9780451524935")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Livre mis à jour",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "1984"),
                        new OA\Property(property: "author", type: "string", example: "GEORGE ORWELL"),
                        new OA\Property(property: "summary", type: "string", example: "Roman dystopique mis à jour."),
                        new OA\Property(property: "isbn", type: "string", example: "9780451524935"),
                        new OA\Property(
                            property: "_links",
                            type: "object",
                            properties: [
                                new OA\Property(property: "self", type: "string", example: "http://localhost:8000/api/v1/books/1"),
                                new OA\Property(property: "update", type: "string", example: "http://localhost:8000/api/v1/books/1"),
                                new OA\Property(property: "delete", type: "string", example: "http://localhost:8000/api/v1/books/1"),
                                new OA\Property(property: "all", type: "string", example: "http://localhost:8000/api/v1/books")
                            ]
                        )
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
            ),
            new OA\Response(
                response: 404,
                description: "Livre non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "No query results for model [App\\Models\\Book] 999")
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
                                "title" => ["The title field is required."],
                                "isbn" => ["The isbn has already been taken."]
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'author' => 'required|string|min:3|max:100',
            'summary' => 'required|string|min:10|max:500',
            'isbn' => [
                'required',
                'string',
                'size:13',
                Rule::unique('books', 'isbn')->ignore($book->id),
            ],
        ]);

        $book->update($validated);

        return new BookResource($book);
    }

    #[OA\Delete(
        path: "/api/v1/books/{id}",
        summary: "Supprimer un livre",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du livre",
                schema: new OA\Schema(type: "integer", example: 1)
            ),
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
                response: 204,
                description: "Livre supprimé (aucun contenu)"
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
            ),
            new OA\Response(
                response: 404,
                description: "Livre non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "No query results for model [App\\Models\\Book] 999")
                    ]
                )
            )
        ]
    )]
    public function destroy(Book $book)
    {
        $book->delete();

        return response()->noContent();
    }
}
