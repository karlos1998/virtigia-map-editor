<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Inertia\Inertia;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
    ) {}

    public function index()
    {
        return Inertia::render('Book/Index', [
            'books' => $this->bookService->getAll(),
        ]);
    }

    public function search(SearchBookRequest $request)
    {
        $query = trim((string) $request->get('query', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $books = $this->bookService->searchByTitle($query, 30);

        return response()->json(BookResource::collection($books));
    }

    public function show(Book $book)
    {
        return Inertia::render('Book/Show', [
            'book' => BookResource::make($book),
        ]);
    }

    public function store(StoreBookRequest $request)
    {
        $book = $this->bookService->create($request->validated());

        return to_route('books.show', $book->id);
    }

    public function update(Book $book, UpdateBookRequest $request)
    {
        $this->bookService->update($book, $request->validated());

        return back();
    }
}
