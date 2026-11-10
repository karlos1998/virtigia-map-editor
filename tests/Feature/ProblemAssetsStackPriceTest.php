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
    private const string WORLD_CONNECTION = 'stack_report_world';

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connection = self::WORLD_CONNECTION;
        config(["database.connections.{$connection}" => config('database.connections.'.config('database.default')),
        ]);
        $world = new WorldTemplate(['connection_name' => $connection]);
        $this->mock(WorldTemplateConnectionResolver::class, function (MockInterface $mock) use ($world): void {
            $mock->shouldReceive('resolve')->with('retro')->andReturn($world);
            $mock->shouldReceive('registerTemplateConnection')->with($world)->andReturnTrue();
            $mock->shouldReceive('visibleOptions')->andReturn([]);
        });
        config(['cache.stores.redis' => ['driver' => 'array'], 'activitylog.enabled' => false]);
        $this->withoutVite();

        Schema::connection($connection)->create('base_items', function (Blueprint $table): void {
            $table->temporary();
            $table->id();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->string('currency');
            $table->json('attributes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        BaseItem::on($connection)->insert([
            ['id' => 28741, 'name' => 'Złociste kulki dziurawienia', 'price' => 152200, 'currency' => 'gold', 'attributes' => '{"quantity":800}', 'deleted_at' => null],
            ['id' => 2, 'name' => 'Na progu', 'price' => 5000000, 'currency' => 'gold', 'attributes' => '{"quantity":"2"}', 'deleted_at' => null],
            ['id' => 3, 'name' => 'Poprawnie wycenione strzały', 'price' => 190, 'currency' => 'gold', 'attributes' => '{"quantity":800}', 'deleted_at' => null],
            ['id' => 4, 'name' => 'Pojedynczy przedmiot', 'price' => 20000000, 'currency' => 'gold', 'attributes' => null, 'deleted_at' => null],
            ['id' => 5, 'name' => 'Smocze łzy', 'price' => 152200, 'currency' => 'dragonTear', 'attributes' => '{"quantity":800}', 'deleted_at' => null],
            ['id' => 6, 'name' => 'Usunięty przedmiot', 'price' => 152200, 'currency' => 'gold', 'attributes' => '{"quantity":800}', 'deleted_at' => now()],
            ['id' => 7, 'name' => 'Ujemna ilość', 'price' => 152200, 'currency' => 'gold', 'attributes' => '{"quantity":-800}', 'deleted_at' => null],
        ]);

        $this->actingAs(new User(['roles' => ['admin'], 'src' => 'retro/avatar.png']))
            ->withSession(['world' => 'retro']);
    }

    public function test_report_lists_expensive_gold_stacks_from_the_selected_world(): void
    {
        $this->get(route('problem-assets.index'))
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
                ->where('suspiciousStacks.data.0.suggested_price', 190)
                ->where('suspiciousStacks.data.1.stack_value', 10000000));
    }

    public function test_applying_the_suggestion_only_updates_the_price_in_the_selected_world(): void
    {
        $this->from(route('problem-assets.index'))
            ->patch(route('problem-assets.apply-suggested-price', 28741), [
                'world' => 'retro',
                'expected_price' => 152200,
                'expected_quantity' => 800,
                'price' => 1,
            ])
            ->assertRedirect(route('problem-assets.index'))
            ->assertSessionHasNoErrors();

        $item = BaseItem::on(self::WORLD_CONNECTION)->findOrFail(28741);
        $this->assertSame(190, (int) $item->price);
        $this->assertSame(['quantity' => 800], $item->attributes);
        $this->assertSame('Złociste kulki dziurawienia', $item->name);
    }

    public function test_stale_report_cannot_overwrite_a_price(): void
    {
        $url = route('problem-assets.apply-suggested-price', 28741);
        $payload = ['world' => 'retro', 'expected_price' => 152200, 'expected_quantity' => 800];

        $this->patchJson($url, [...$payload, 'world' => 'classic'])->assertUnprocessable()->assertJsonValidationErrors('world');
        $this->patchJson($url, [...$payload, 'expected_price' => 152201])->assertUnprocessable()->assertJsonValidationErrors('expected_price');
        $this->patchJson($url, [...$payload, 'expected_quantity' => 801])->assertUnprocessable()->assertJsonValidationErrors('expected_price');

        $this->assertSame(152200, (int) BaseItem::on(self::WORLD_CONNECTION)->findOrFail(28741)->price);
    }
}
