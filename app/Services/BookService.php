<?php

namespace App\Services;

use App\Http\Resources\BookListResource;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;
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

    public function findBaseNpcsForPreview(array $filters): Collection
    {
        $query = (new BaseNpc)->newQuery();

        $ids = collect($filters['ids'] ?? [])->map(static fn ($value) => (int) $value)->filter()->values();
        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids);
        }

        if (! empty($filters['rank'])) {
            $query->where('rank', $filters['rank']);
        }

        if (! empty($filters['lvl_from'])) {
            $query->where('lvl', '>=', (int) $filters['lvl_from']);
        }

        if (! empty($filters['lvl_to'])) {
            $query->where('lvl', '<=', (int) $filters['lvl_to']);
        }

        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 30)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        return $query
            ->orderBy('lvl')
            ->orderBy('name')
            ->forPage($page, $perPage)
            ->get();
    }

    public function countBaseNpcsForPreview(array $filters): int
    {
        $query = (new BaseNpc)->newQuery();

        $ids = collect($filters['ids'] ?? [])->map(static fn ($value) => (int) $value)->filter()->values();
        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids);
        }

        if (! empty($filters['rank'])) {
            $query->where('rank', $filters['rank']);
        }

        if (! empty($filters['lvl_from'])) {
            $query->where('lvl', '>=', (int) $filters['lvl_from']);
        }

        if (! empty($filters['lvl_to'])) {
            $query->where('lvl', '<=', (int) $filters['lvl_to']);
        }

        return (int) $query->count();
    }

    public function findBaseItemsForPreview(array $filters): Collection
    {
        $query = (new BaseItem)->newQuery();

        if (! empty($filters['base_npc_ids'])) {
            $baseNpcIds = collect($filters['base_npc_ids'])->map(static fn ($value) => (int) $value)->filter()->values();
            if ($baseNpcIds->isNotEmpty()) {
                $query->whereHas('baseNpcs', fn ($relation) => $relation->whereIn('base_npcs.id', $baseNpcIds));
            }
        }

        if (! empty($filters['rarity'])) {
            $query->where('rarity', $filters['rarity']);
        }

        if (! empty($filters['lvl_from'])) {
            $query->whereRaw('CAST(JSON_UNQUOTE(JSON_EXTRACT(attributes, "$.needLevel")) AS UNSIGNED) >= ?', [(int) $filters['lvl_from']]);
        }

        if (! empty($filters['lvl_to'])) {
            $query->whereRaw('CAST(JSON_UNQUOTE(JSON_EXTRACT(attributes, "$.needLevel")) AS UNSIGNED) <= ?', [(int) $filters['lvl_to']]);
        }

        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 30)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        return $query
            ->distinct('base_items.id')
            ->orderBy('id')
            ->forPage($page, $perPage)
            ->get();
    }
}
