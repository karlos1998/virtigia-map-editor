<?php

namespace App\Services;

use App\Http\Resources\BookListResource;
use App\Models\Book;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class BookService extends BaseService
{
    public function __construct(
        private readonly Book $bookModel,
    ) {}

    public function getAll()
    {
        return $this->fetchData(
            BookListResource::class,
            $this->bookModel->newQuery()->orderByDesc('id'),
            new TableService(
                columns: [
                    'id' => new TableTextColumn(sortable: true),
                    'title' => new TableTextColumn(sortable: true),
                ],
                globalFilterColumns: ['title'],
            )
        );
    }

    public function searchByTitle(string $query, int $limit = 30)
    {
        return $this->bookModel->newQuery()
            ->where('title', 'like', '%'.$query.'%')
            ->orderBy('title')
            ->limit($limit)
            ->get(['id', 'title']);
    }

    public function create(array $validated): Book
    {
        return $this->bookModel->newQuery()->create($validated);
    }

    public function update(Book $book, array $validated): void
    {
        $book->update($validated);
    }
}
