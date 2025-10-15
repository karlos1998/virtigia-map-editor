<?php

namespace App\Services;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use App\Enums\Profession;
use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use App\Services\Traits\UpdateImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Karlos3098\LaravelPrimevueTableService\Enum\TableColumnDataType;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableSliderColumn;
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
//        dd(array_map(function($rarity){
//            return new TableDropdownOption($rarity->description(), fn($query) => $query->whereJsonContains('attributes->rarity', $rarity->value));
//        }, BaseItemRarity::cases()), array_map(function($category){
//            return new TableDropdownOption($category->description(), fn($query) => $query->where('category', $category->value));
//        }, BaseItemCategory::cases()));
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
                        queryPaths: ['attributes->needLevel'],
                        sortPath: 'attributes->needLevel',
                        sortDataType: TableColumnDataType::NUMERIC,
                    ),
                    'rarity' => new TableDropdownColumn(
                        placeholder: 'Rzadkość',
                        options: array_map(function($rarity){
                            return new TableDropdownOption($rarity->description(), fn($query) => $query->where('rarity', $rarity->value));
                        }, BaseItemRarity::cases())
                    ),

                    'in_use' => new TableDropdownColumn(
                        placeholder: 'W Użyciu',
                        options: [
                            new TableDropdownOption('W użyciu', function($query) {
                                return $query->where(function($q) {
                                    $q->whereHas('shops')
                                      ->orWhereHas('baseNpcs')
                                      ->orWhereHas('dialogs');
                                });
                            }),
                            new TableDropdownOption('Nie używany', function($query) {
                                return $query->whereDoesntHave('shops')
                                    ->whereDoesntHave('baseNpcs')
                                    ->whereDoesntHave('dialogs');
                            }),
                        ]
                    ),
                ],
                globalFilterColumns: ['name', 'src'],
                rowsPerPage: [100, 300, 500]
            )
        );
    }

    public function search(string $search = '', Collection $ids = null, ?string $category = null)
    {
        $idsArray = $ids?->toArray() ?? [];

        // If ids are provided and search is empty, return only those ids (respecting category if set)
        if (!empty($idsArray) && $search === '') {
            $query = $this->baseItemModel->newQuery()->whereIn('id', $idsArray);
            if ($category) {
                $query->where('category', $category);
            }

            return $query->get();
        }

        // Otherwise, return idsResults (items for given ids) on top of regular search results
        $idsQuery = $this->baseItemModel->newQuery();
        if (!empty($idsArray)) {
            $idsQuery->whereIn('id', $idsArray);
            if ($category) {
                $idsQuery->where('category', $category);
            }
            $idsResults = $idsQuery->get();
        } else {
            $idsResults = collect();
        }

        $searchQuery = $this->baseItemModel->newQuery();
        if ($category) {
            $searchQuery->where('category', $category);
        }
        $searchItems = $searchQuery->where('name', 'like', '%' . $search . '%')->limit(30)->get();

        return $idsResults->merge($searchItems)->unique('id')->values();
    }

    /**
     * Update item attributes with proper merging and save separate point allocations
     *
     * @param BaseItem $baseItem The item to update
     * @param mixed $newAttributes New attributes from scale calculation
     * @param array $attributePoints Regular attribute points allocation
     * @param array $manualAttributePoints Manual attribute points allocation
     * @return void
     */
    public function updateAttributes(BaseItem $baseItem, mixed $newAttributes, array $attributePoints = [], array $manualAttributePoints = []): void
    {
        // Get current attributes to preserve existing data
        $oldAttributes = $baseItem->attributes ?? [];

        // Merge attributes: new attributes override old ones, but unique old attributes are preserved
        // This preserves things like legendary bonuses, owner binding, etc.
//        $mergedAttributes = array_merge($oldAttributes, $newAttributes ?? []);
        $mergedAttributes = $newAttributes;

        // Update the item with all three fields, converting empty arrays to null
        $baseItem->update([
            'attributes' => empty($mergedAttributes) ? null : $mergedAttributes,
            'attribute_points' => empty($attributePoints) ? null : $attributePoints,
            'manual_attribute_points' => empty($manualAttributePoints) ? null : $manualAttributePoints,
            'edited_manually' => true
        ]);

        // Log the update for auditing
        Log::info('Item attributes updated', [
            'item_id' => $baseItem->id,
            'old_attributes_count' => count($oldAttributes),
            'new_attributes_count' => count($newAttributes ?? []),
            'merged_attributes_count' => count($mergedAttributes),
            'attribute_points_count' => count($attributePoints),
            'manual_attribute_points_count' => count($manualAttributePoints)
        ]);
    }

    public function delete(BaseItem $baseItem)
    {
        abort_if($baseItem->isInUse(), 422, 'Nie możesz usunąć używanego przedmiotu');
        $baseItem->delete();
    }

    public function copy(BaseItem $baseItem)
    {
        $newBaseItem = $baseItem->replicate();
        $newBaseItem->stats = '';
        $newBaseItem->save();
        return $newBaseItem;
    }

    public function update(BaseItem $baseItem, array $validated)
    {
        $baseItem->update([
            ...$validated,
            'edited_manually' => true,
        ]);
    }

    public function create(array $validated)
    {
        // Extract image data before creating the item
        $imageData = $validated['image'] ?? null;
        unset($validated['image']);

        return \DB::transaction(function () use ($validated, $imageData) {
            $baseItem = $this->baseItemModel->create([
                ...$validated,
                'edited_manually' => true,
                'src' => '',
                'stats' => '',
            ]);

            // If an image was provided, update it
            if (!empty($imageData)) {
                $this->updateImageFromBase64($baseItem, Str::of($imageData), Str::of($baseItem->name), 'img');
            }

            return $baseItem;
        });
    }
}
