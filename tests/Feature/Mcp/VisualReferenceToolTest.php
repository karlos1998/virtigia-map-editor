<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Responses\ImageResponse;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\DynamicModel;
use App\Models\Map as GameMap;
use App\Services\Mcp\McpWorldService;
use App\Services\Mcp\VisualReferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisualReferenceToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_bounded_map_item_and_npc_image_previews(): void
    {
        Storage::fake('s3');
        app(McpWorldService::class)->use('test');

        try {
            $map = GameMap::query()->create([
                'name' => 'Mapa referencyjna MCP',
                'src' => 'test/reference-map.png',
                'x' => 20,
                'y' => 20,
                'col' => str_repeat('0', 400),
                'pvp' => 0,
                'water' => '',
                'battleground' => '',
            ]);
            $item = BaseItem::query()->create([
                'name' => 'Item referencyjny MCP',
                'src' => 'items/test/reference-item.png',
                'stats' => '',
                'cl' => 0,
                'pr' => 0,
                'edited_manually' => true,
                'attributes' => ['needLevel' => 10],
                'rarity' => 'heroic',
                'category' => 'oneHanded',
                'price' => 100,
                'currency' => 'gold',
            ]);
            $npc = BaseNpc::query()->create([
                'name' => 'NPC referencyjny MCP',
                'src' => 'test/reference-npc.gif',
                'lvl' => 200,
                'category' => 'MOB',
                'rank' => 'HERO',
                'profession' => 'm',
            ]);

            Storage::disk('s3')->put('img/locations/test/reference-map.png', $this->png(1200, 600));
            Storage::disk('s3')->put('img/items/test/reference-item.png', $this->png(32, 32));
            Storage::disk('s3')->put('img/npc/test/reference-npc.gif', $this->png(64, 96));

            $service = app(VisualReferenceService::class);
            $mapResult = $service->browse('test', 'maps', null, [$map->id], 6);
            $itemResult = $service->browse('test', 'items', null, [$item->id], 12);
            $npcResult = $service->browse('test', 'npcs', null, [$npc->id], 12);

            $this->assertSame([1024, 512], $this->imageDimensions($mapResult['images'][0]['binary']));
            $this->assertSame([256, 256], $this->imageDimensions($itemResult['images'][0]['binary']));
            $this->assertSame([320, 480], $this->imageDimensions($npcResult['images'][0]['binary']));
            $this->assertSame(1, $mapResult['metadata']['references'][0]['image_index']);
            $this->assertSame('heroic', $itemResult['metadata']['references'][0]['rarity']);
            $this->assertSame('HERO', $npcResult['metadata']['references'][0]['rank']);
        } finally {
            app(McpWorldService::class)->use('test');
            GameMap::query()->where('name', 'Mapa referencyjna MCP')->delete();
            BaseItem::withTrashed()->where('name', 'Item referencyjny MCP')->forceDelete();
            BaseNpc::query()->where('name', 'NPC referencyjny MCP')->delete();
            DynamicModel::clearGlobalConnection();
        }
    }

    public function test_image_response_serializes_a_standard_mcp_image_content_block(): void
    {
        $binary = $this->png(8, 8);
        $content = ImageResponse::fromBinary($binary)->withMeta(['name' => 'Test'])->content()->toArray();

        $this->assertSame('image', $content['type']);
        $this->assertSame('image/png', $content['mimeType']);
        $this->assertSame($binary, base64_decode($content['data'], true));
        $this->assertSame('Test', $content['_meta']['name']);
    }

    /** @return array{0: int, 1: int} */
    private function imageDimensions(string $binary): array
    {
        $dimensions = getimagesizefromstring($binary);

        $this->assertIsArray($dimensions);

        return [$dimensions[0], $dimensions[1]];
    }

    private function png(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 34, 48, 64);
        imagefill($image, 0, 0, $background);
        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }
}
