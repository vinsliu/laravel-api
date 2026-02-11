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
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Succès")
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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "author", "summary", "isbn"],
                properties: [
                    new OA\Property(property: "title", type: "string", minLength: 3, maxLength: 255, example: "1984"),
                    new OA\Property(property: "author", type: "string", minLength: 3, maxLength: 100, example: "George Orwell"),
                    new OA\Property(property: "summary", type: "string", minLength: 10, maxLength: 500, example: "Roman dystopique décrivant une société totalitaire."),
                    new OA\Property(property: "isbn", type: "string", minLength: 13, maxLength: 13, example: "9780451524935")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Livre créé"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 422, description: "Erreur de validation")
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
        summary: "Afficher le détail drun livre",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du livre",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Détails du livre"),
            new OA\Response(response: 404, description: "Livre non trouvé")
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
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du livre",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "author", "summary", "isbn"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "1984"),
                    new OA\Property(property: "author", type: "string", example: "George Orwell"),
                    new OA\Property(property: "summary", type: "string", example: "Roman dystopique mis à jour."),
                    new OA\Property(property: "isbn", type: "string", example: "9780451524935")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Livre mis à jour"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Livre non trouvé"),
            new OA\Response(response: 422, description: "Erreur de validation")
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
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du livre",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Livre supprimé"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Livre non trouvé")
        ]
    )]
    public function destroy(Book $book)
    {
        $book->delete();

        return response()->noContent();
    }
}
