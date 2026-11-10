<?php

namespace Tests\Feature;

use App\Models\BaseItem;
use App\Models\DynamicModel;
use App\Models\User;
use App\Models\WorldTemplate;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class ProblemAssetsStackPriceTest extends TestCase
{
    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    public function test_report_lists_expensive_gold_stacks_from_the_selected_world(): void
    {
        $world = new WorldTemplate(['connection_name' => config('database.default')]);
        $this->mock(WorldTemplateConnectionResolver::class, function (MockInterface $mock) use ($world): void {
            $mock->shouldReceive('resolve')->with('retro')->andReturn($world);
            $mock->shouldReceive('registerTemplateConnection')->with($world)->andReturnTrue();
            $mock->shouldReceive('visibleOptions')->andReturn([]);
        });
        config(['cache.stores.redis' => ['driver' => 'array']]);
        $this->withoutVite();

        Schema::create('base_items', function (Blueprint $table): void {
            $table->temporary();
            $table->id();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->string('currency');
            $table->json('attributes')->nullable();
            $table->softDeletes();
        });

        BaseItem::query()->insert([
            ['id' => 28741, 'name' => 'Złociste kulki dziurawienia', 'price' => 152200, 'currency' => 'gold', 'attributes' => '{"quantity":800}', 'deleted_at' => null],
            ['id' => 2, 'name' => 'Na progu', 'price' => 5000000, 'currency' => 'gold', 'attributes' => '{"quantity":"2"}', 'deleted_at' => null],
            ['id' => 3, 'name' => 'Poprawnie wycenione strzały', 'price' => 190, 'currency' => 'gold', 'attributes' => '{"quantity":800}', 'deleted_at' => null],
            ['id' => 4, 'name' => 'Pojedynczy przedmiot', 'price' => 20000000, 'currency' => 'gold', 'attributes' => null, 'deleted_at' => null],
            ['id' => 5, 'name' => 'Smocze łzy', 'price' => 152200, 'currency' => 'dragonTear', 'attributes' => '{"quantity":800}', 'deleted_at' => null],
            ['id' => 6, 'name' => 'Usunięty przedmiot', 'price' => 152200, 'currency' => 'gold', 'attributes' => '{"quantity":800}', 'deleted_at' => now()],
            ['id' => 7, 'name' => 'Ujemna ilość', 'price' => 152200, 'currency' => 'gold', 'attributes' => '{"quantity":-800}', 'deleted_at' => null],
        ]);

        $this->actingAs(new User(['roles' => ['admin'], 'src' => 'retro/avatar.png']))
            ->withSession(['world' => 'retro'])
            ->get(route('problem-assets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('ProblemAssets/Index')
                ->where('world', 'retro')
                ->where('suspiciousStackThreshold', 10_000_000)
                ->has('suspiciousStacks.data', 2)
                ->where('suspiciousStacks.data.0.id', 28741)
                ->where('suspiciousStacks.data.0.name', 'Złociste kulki dziurawienia')
                ->where('suspiciousStacks.data.0.price', 152200)
                ->where('suspiciousStacks.data.0.quantity', 800)
                ->where('suspiciousStacks.data.0.stack_value', 121760000)
                ->where('suspiciousStacks.data.1.stack_value', 10000000));
    }
}
