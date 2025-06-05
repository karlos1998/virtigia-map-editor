<?php

namespace App\Services;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\Profession;
use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use App\Services\Traits\UpdateImage;
use Illuminate\Support\Collection;
use Karlos3098\LaravelPrimevueTableService\Enum\TableColumnDataType;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOptionTag;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableSliderColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class BaseItemService extends BaseService
{

    use UpdateImage;

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
            $this->baseItemModel->with(['shops', 'baseNpcs']),
            new TableService(
                columns: [

                    'category_name' => new TableDropdownColumn(
                        placeholder: 'Kategoria',
                        options: array_map(function($category){
                            return new TableDropdownOption($category->description(), fn($query) => $query->where('category', $category->value));
                        }, BaseItemCategory::cases())
                    ),

                    'currency_name' => new TableDropdownColumn(
                        placeholder: 'Waluta',
                        options: array_map(function($currency){
                            return new TableDropdownOption($currency->description(), fn($query) => $query->where('currency', $currency->value));
                        }, BaseItemCurrency::cases())
                    ),

                    'need_professions' => new TableDropdownColumn(
                        placeholder: 'Profesja',
                        options: array_map(function($profession){
                            return new TableDropdownOption($profession->description(), fn($query) => $query->whereJsonContains('attributes->needProfessions', $profession->value));
                        }, Profession::cases())
                    ),

                    'need_level' => new TableSliderColumn(
                        placeholder: 'Level',
                        min: 0,
                        max: 300,
                        step: 1,
                        sortable: true,
                        sortPath: 'attributes->needLevel',
                        sortDataType: TableColumnDataType::NUMERIC,
                    )
                ],
                globalFilterColumns: ['name', 'src'],
                rowsPerPage: [100, 300, 500]
            )
        );
    }

    public function search(string $search = '', Collection $ids = null)
    {
        $idsResults = $this->baseItemModel->whereIn('id', $ids->toArray())->get();

//        $searchItems = $this->baseItemModel->search($search)->take(30)->get();
        $searchItems = $this->baseItemModel->where('name', 'like', '%' . $search . '%')->limit(30)->get();

        return $idsResults->merge($searchItems);
    }

//    public function search(string $search = '', Collection $ids = null)
//    {
//        $query = $this->baseItemModel->search($search);
//
//        // Jeśli $ids nie jest null, wyszukaj rekordy o pasujących ID na pierwszym miejscu
//        if ($ids && $ids->isNotEmpty()) {
//            $idsResults = $query->whereIn('id', $ids->toArray())->get();
//            // Wykonaj zapytanie dla pozostałych rekordów
//            $otherResults = $query->whereNotIn('id', $ids->toArray())->take(30)->get();
//
//            // Połącz oba zestawy wyników, tak aby rekordy z listy ID były na samej górze
//            return $idsResults->merge($otherResults);
//        }
//
//        return $query->take(30)->get();
//    }
    public function updateAttributes(BaseItem $baseItem, mixed $attributes)
    {
        $baseItem->update(['attributes' => $attributes, 'edited_manually' => true]);
    }

    public function delete(BaseItem $baseItem)
    {
        abort_if($baseItem->isInUse(), 422, 'Nie możesz usunąć używanego przedmiotu');
        $baseItem->delete();
    }

    public function copy(BaseItem $baseItem)
    {
        $newBaseItem = $baseItem->replicate();
        $newBaseItem->save();
        return $newBaseItem;
    }

    public function update(BaseItem $baseItem, array $validated)
    {
        $baseItem->update($validated);
    }
}
