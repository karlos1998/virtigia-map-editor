<?php

namespace App\Services;

use App\Enums\BaseItemCategory;
use App\Enums\Profession;
use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOptionTag;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class BaseItemService extends BaseService
{
    public function __construct(private readonly BaseItem $baseItemModel)
    {
    }

    /**§
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            BaseItemResource::class,
            $this->baseItemModel,
            new TableService(
                columns: [

                    'category_name' => new TableDropdownColumn(
                        placeholder: 'Kategoria',
                        options: array_map(function($category){
                            return new TableDropdownOption($category->description(), fn($query) => $query->where('category', $category->value));
                        }, BaseItemCategory::cases())
                    ),

                    'need_professions' => new TableDropdownColumn(
                        placeholder: 'Profesja',
                        options: array_map(function($profession){
                            return new TableDropdownOption($profession->description(), fn($query) => $query->whereJsonContains('attributes->needProfessions', $profession->value));
                        }, Profession::cases())
                    ),

                    'need_level' => new TableTextColumn(
                        placeholder: 'Dokładny lvl (TODO)',
                        sortable: true,
                        sortPath: 'attributes->needLevel'
                    )
                ],
                globalFilterColumns: ['name', 'src'],
                rowsPerPage: [100, 300, 500]
            )
        );
    }

    public function search(string $search = '')
    {
        return $this->baseItemModel->where('name', 'like', '%' . $search . '%')->limit(30)->get();
    }

    public function updateAttributes(BaseItem $baseItem, mixed $attributes)
    {
        $baseItem->update(['attributes' => $attributes, 'edited_manually' => true]);
    }
}
