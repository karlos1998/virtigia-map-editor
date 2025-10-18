<?php
namespace App\Services;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Enums\Profession;
use App\Http\Resources\BaseNpcResource;
use App\Http\Resources\PureNpcWithOnlyLocationsResource;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Services\Traits\UpdateImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class BaseNpcService extends BaseService
{
    use UpdateImage;

    public function __construct(private readonly BaseNpc $baseNpcModel)
    {
    }

    /**
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            BaseNpcResource::class,
            $this->baseNpcModel,
            new TableService(
                columns: [
                  'id' => new TableTextColumn(sortable: true),
                  'name' => new TableTextColumn(sortable: true),
                  'src' => new TableTextColumn(sortable: true),
                  'lvl' => new TableTextColumn(sortable: true),

                  'rank' => new TableDropdownColumn(
                      placeholder: 'Rank',
                      options: array_map(function($rank) {
                          return new TableDropdownOption($rank->description(), fn($query) => $query->where('rank', $rank->value));
                      }, BaseNpcRank::cases())
                  ),

                  'category' => new TableDropdownColumn(
                      placeholder: 'Category',
                      options: array_map(function($category) {
                          return new TableDropdownOption($category->description(), fn($query) => $query->where('category', $category->value));
                      }, BaseNpcCategory::cases())
                  ),

                  'profession' => new TableDropdownColumn(
                      placeholder: 'Profession',
                      options: array_map(function($profession) {
                          return new TableDropdownOption($profession->description(), fn($query) => $query->where('profession', $profession->value));
                      }, Profession::cases())
                  ),

                  'type' => new TableTextColumn(sortable: true),

                  'loot_counts.total' => new TableTextColumn(
                      placeholder: 'Ilość lootów',
                      sortable: true,
                      sortPath: 'loots_count'
                  ),

                  'is_aggressive' => new TableDropdownColumn(
                      placeholder: 'Agresywny',
                      sortable: true,
                      options: [
                          new TableDropdownOption('Tak', fn($query) => $query->where('is_aggressive', true)),
                          new TableDropdownOption('Nie', fn($query) => $query->where('is_aggressive', false)),
                      ]
                  ),
                ],
                globalFilterColumns: ['name', 'rank', 'category', 'profession', 'type', 'is_aggressive'],
//                withCount: ['loots'],
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function getLocations(BaseNpc $baseNpc): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            PureNpcWithOnlyLocationsResource::class,
            $baseNpc->locations(),
            new TableService(
                globalFilterColumns: [] //todo - szukanie po relacji. moja libka chyba tego nie obsluguje
            )
        );
    }

    public function search(string $search)
    {
        return BaseNpcResource::collection($this->baseNpcModel->where('name', 'like', '%' . $search . '%')->limit(25)->get());
    }

    public function searchHero(string $search)
    {
        return BaseNpcResource::collection(
            $this->baseNpcModel
                ->where('name', 'like', '%' . $search . '%')
                ->where('rank', BaseNpcRank::HERO->value)
                ->limit(25)
                ->get()
        );
    }

    public function store(array $validated): BaseNpc
    {
        return BaseNpc::create($validated);
    }

    public function destroy(BaseNpc $baseNpc)
    {
        $baseNpc->delete();
    }

    public function update(BaseNpc $baseNpc, mixed $validated)
    {
        $baseNpc->update($validated);
    }

    public function setGuaranteedLoot(BaseNpc $baseNpc, bool $guaranteed)
    {
        $baseNpc->update(['guaranteed_loot' => $guaranteed]);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($baseNpc)
            ->event('update-base-npc-guaranteed-loot')
            ->withProperty('guaranteed', $guaranteed)
            ->log('update-base-npc-guaranteed-loot');
    }

    public function attachLoot(BaseNpc $baseNpc, int $baseItemId)
    {
        $baseItem = BaseItem::findOrFail($baseItemId);
        $baseNpc->loots()->attach($baseItem);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($baseItem)
            ->event('attach-to-base-npc-loots')
            ->withProperty('base_npc', $baseNpc)
            ->log('attach-to-base-npc-loots');

        activity()
            ->causedBy(Auth::user())
            ->performedOn($baseNpc)
            ->event('attach-base-npc-loots')
            ->withProperty('base_item', $baseItem)
            ->log('attach-base-npc-loots');
    }

    public function detachLoot(BaseNpc $baseNpc, int $loot)
    {
        $baseItem = $baseNpc->loots()->findOrFail($loot);
        $baseNpc->loots()->detach($loot);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($baseItem)
            ->event('detach-from-base-npc-loots')
            ->withProperty('base_npc', $baseNpc)
            ->log('detach-from-base-npc-loots');

        activity()
            ->causedBy(Auth::user())
            ->performedOn($baseNpc)
            ->event('detach-base-npc-loots')
            ->withProperty('base_item', $baseItem)
            ->log('detach-base-npc-loots');
    }

    public function attachLootsFromBaseNpc(BaseNpc $targetBaseNpc, int $sourceBaseNpcId)
    {
        $sourceBaseNpc = BaseNpc::findOrFail($sourceBaseNpcId);
        $sourceLoots = $sourceBaseNpc->loots;

        foreach ($sourceLoots as $loot) {
            // Check if the loot is already attached to the target base NPC
            if (!$targetBaseNpc->loots()->where('base_item_id', $loot->id)->exists()) {
                $targetBaseNpc->loots()->attach($loot);

                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($loot)
                    ->event('attach-to-base-npc-loots')
                    ->withProperty('base_npc', $targetBaseNpc)
                    ->log('attach-to-base-npc-loots');

                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($targetBaseNpc)
                    ->event('attach-base-npc-loots')
                    ->withProperty('base_item', $loot)
                    ->log('attach-base-npc-loots');
            }
        }
    }

    public function storeSimple(mixed $validated)
    {
        // Extract image data
        $base64 = $validated['image'];
        preg_match('/^data:image\/(png|gif);base64,/', $base64, $matches);
        $extension = $matches[1] ?? 'png';

        $imageData = substr($base64, strpos($base64, ',') + 1);
        $decodedImage = base64_decode($imageData);

        // Generate filename from name
        $fileName = Str::slug(pathinfo($validated['name'], PATHINFO_FILENAME));
        if (empty($fileName)) {
            $fileName = Str::uuid();
        }

        // Set the storage path to retro/new
        $storagePath = 'img/npc/' . session("world"). '/new/';
        $filePath = "{$storagePath}{$fileName}.{$extension}";

        // Check if file exists and generate a unique name if needed
        if (Storage::disk('s3')->exists($filePath)) {
            $fileName = Str::uuid() . "-{$fileName}";
            $filePath = "{$storagePath}{$fileName}.{$extension}";
        }

        // Upload the file
        Storage::disk('s3')->put($filePath, $decodedImage);

        // Create and save the BaseNpc with the correct src
        $baseNpc = new BaseNpc();
        $baseNpc->name = $validated['name'];
        $baseNpc->lvl = $validated['lvl'];
        $baseNpc->rank = $validated['rank'];
        $baseNpc->category = $validated['category'];
        $baseNpc->src = session("world") . "/new/{$fileName}.{$extension}";
        $baseNpc->save();

        return $baseNpc;
    }

    /**
     * Find similar base NPCs with the exact same name
     */
    public function findSimilarBaseNpcs(BaseNpc $baseNpc)
    {
        return BaseNpcResource::collection(
            $this->baseNpcModel
                ->where('name', $baseNpc->name)
                ->where('id', '!=', $baseNpc->id)
                ->get()
        );
    }

    /**
     * Transfer NPCs from one base NPC to another
     */
    public function transferNpcs(BaseNpc $sourceBaseNpc, BaseNpc $targetBaseNpc)
    {
        // Get all NPCs that belong to the source base NPC
        $npcs = $sourceBaseNpc->locations()->get();
        $count = $npcs->count();

        // Update their base_npc_id to point to the target base NPC
        foreach ($npcs as $npc) {
            $npc->base_npc_id = $targetBaseNpc->id;
            $npc->save();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($npc)
                ->event('transfer-npc')
                ->withProperties([
                    'source_base_npc_id' => $sourceBaseNpc->id,
                    'target_base_npc_id' => $targetBaseNpc->id
                ])
                ->log('transfer-npc');
        }

    }
}
