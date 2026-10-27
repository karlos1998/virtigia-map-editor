<?php

namespace Tests\Feature;

use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use App\Models\BaseItemUsageView;
use App\Models\DynamicModel;
use App\Models\User;
use App\Models\WorldTemplate;
use App\Services\BaseItemAttributeReverseService;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BaseItemAttributeReverseTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private BaseItem $item;

    private array $reversePayload;

    private array $scaledPayload;

    private int $engineStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $template = new WorldTemplate(['slug' => 'reverse_testing', 'connection_name' => $connection]);
        $this->partialMock(WorldTemplateConnectionResolver::class, function ($mock) use ($template): void {
            $mock->shouldReceive('resolve')->with('reverse_testing')->andReturn($template);
            $mock->shouldReceive('registerTemplateConnection')->with($template)->andReturn(true);
        });
        DynamicModel::setGlobalConnection($connection);
        Schema::create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->default('');
            $table->json('attributes')->nullable();
            $table->json('attribute_points')->nullable();
            $table->json('manual_attribute_points')->nullable();
            $table->json('reverse_attributes')->nullable();
            $table->string('category');
            $table->string('rarity');
            $table->boolean('edited_manually')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        config()->set('services.margatron_api.base_url', 'https://engine.test/public');
        Http::preventStrayRequests();
        $this->reversePayload = $this->reverseResponse();
        $this->scaledPayload = $this->scaledResponse();
        Http::fake(fn ($request) => Http::response(
            str_ends_with($request->url(), '/reverse-scale-attributes') ? $this->reversePayload : $this->scaledPayload,
            $this->engineStatus,
        ));
        $this->editor = User::factory()->create();
        $this->actingAs($this->editor)->withSession(['world' => 'reverse_testing']);
        $this->item = BaseItem::query()->create([
            'name' => 'Stary miecz',
            'category' => 'oneHanded',
            'rarity' => 'unique',
            'attributes' => [
                'needLevel' => 20, 'needProfessions' => ['w'], 'strength' => 39,
                'physicalDamage' => [50, 60], 'deepWoundChance' => [15, 12],
                'description' => 'Zachowaj opis', 'legendaryBon' => ['pushBack', 8], 'isBoundToOwner' => true,
            ],
            'reverse_attributes' => ['attributePoints' => ['strength' => 999]],
        ]);
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();
        parent::tearDown();
    }

    public function test_preview_reverses_then_scales_current_data_without_saving(): void
    {
        $before = $this->item->fresh()->getAttributes();
        $response = $this->postJson($this->previewUrl());

        $response->assertOk()
            ->assertJsonPath('original_attributes.strength', 39)
            ->assertJsonPath('recalculated_attributes.strength', 37)
            ->assertJsonPath('recalculated_attributes.description', 'Zachowaj opis')
            ->assertJsonPath('recalculated_attributes.legendaryBon', ['pushBack', 8])
            ->assertJsonPath('recalculated_attributes.isBoundToOwner', true)
            ->assertJsonPath('attribute_points.strength', 3)
            ->assertJsonPath('manual_attribute_points.deepWoundChance', 15);
        $this->assertSame($before, $this->item->fresh()->getAttributes());
        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request['scaledAttributes']->strength === 39 && $request['itemProfessions'] === ['w']);
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return ($query['strength'] ?? null) === '3' && ($query['deepWoundChance'] ?? null) === '15'
                && ($query['attackElements'] ?? null) === 'PHYSICAL' && ($query['itemProfessions'] ?? null) === 'w';
        });
    }

    public function test_confirmation_saves_exact_preview_and_original_snapshot_ignoring_client_attributes(): void
    {
        $preview = $this->postJson($this->previewUrl())->assertOk()->json();
        Http::fake();

        $this->patch($this->confirmUrl(), [
            'preview_token' => $preview['preview_token'],
            'attributes' => ['strength' => 99999], 'attribute_points' => ['strength' => 99999],
        ])->assertRedirect(route('base-items.show', $this->item->id));

        $item = $this->item->fresh();
        $this->assertSame($preview['recalculated_attributes'], $item->attributes);
        $this->assertSame(['strength' => 3], $item->attribute_points);
        $this->assertSame(['deepWoundChance' => 15], $item->manual_attribute_points);
        $this->assertSame($preview['original_attributes'], $item->reverse_attributes['originalAttributes']);
        $this->assertNotEmpty($item->reverse_attributes['approved_at']);
        $this->assertTrue($item->edited_manually);
        $this->assertFalse($item->hasMissingAttributePoints());
        Http::assertNothingSent();
    }

    public function test_stale_preview_is_rejected_without_overwriting_changes(): void
    {
        $token = $this->postJson($this->previewUrl())->json('preview_token');
        $this->item->update(['attributes' => [...$this->item->attributes, 'strength' => 72]]);

        $this->patchJson($this->confirmUrl(), ['preview_token' => $token])->assertUnprocessable()->assertJsonValidationErrors('reverse');

        $this->assertSame(72, $this->item->fresh()->attributes['strength']);
        $this->assertNull($this->item->fresh()->attribute_points);
    }

    public function test_existing_points_block_both_preview_and_confirmation(): void
    {
        $token = $this->postJson($this->previewUrl())->json('preview_token');
        $this->item->update(['attribute_points' => ['strength' => 6]]);
        Http::fake();

        $this->postJson($this->previewUrl())->assertUnprocessable()->assertJsonValidationErrors('reverse');
        $this->patchJson($this->confirmUrl(), ['preview_token' => $token])->assertUnprocessable()->assertJsonValidationErrors('reverse');
        $this->assertSame(['strength' => 6], $this->item->fresh()->attribute_points);
        Http::assertNothingSent();
    }

    public function test_preview_token_is_bound_to_user_item_and_connection_and_expires(): void
    {
        $token = $this->postJson($this->previewUrl())->json('preview_token');
        $this->actingAs(User::factory()->create());
        $this->patchJson($this->confirmUrl(), ['preview_token' => $token])->assertUnprocessable();
        $this->actingAs($this->editor);
        $other = $this->item->replicate();
        $other->save();
        $this->patchJson(route('base-items.attribute-reverse.confirm', $other->id), ['preview_token' => $token])->assertUnprocessable();

        try {
            app(BaseItemAttributeReverseService::class)->confirm((clone $this->item)->setConnection('another_world'), $this->editor->id, $token);
            $this->fail('A preview from another connection was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reverse', $exception->errors());
        }

        $this->travel(16)->minutes();
        $this->patchJson($this->confirmUrl(), ['preview_token' => $token])->assertUnprocessable()->assertJsonValidationErrors('reverse');
        $this->assertNull($this->item->fresh()->attribute_points);
    }

    public function test_confirmation_requires_a_valid_preview_token(): void
    {
        $this->patchJson($this->confirmUrl(), [])->assertUnprocessable()->assertJsonValidationErrors('preview_token');
        $this->patchJson($this->confirmUrl(), ['preview_token' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('preview_token');
        $this->assertNull($this->item->fresh()->attribute_points);
    }

    public function test_engine_failure_does_not_modify_item(): void
    {
        $this->engineStatus = 503;
        $this->postJson($this->previewUrl())->assertUnprocessable()->assertJsonValidationErrors('reverse');
        $this->assertNull($this->item->fresh()->attribute_points);
        $this->assertSame(39, $this->item->fresh()->attributes['strength']);
    }

    public function test_invalid_reverse_response_does_not_produce_a_preview(): void
    {
        $this->reversePayload = ['attributePoints' => ['strength' => 'invalid']];
        $this->postJson($this->previewUrl())->assertUnprocessable()->assertJsonValidationErrors('reverse');
        $this->assertNull($this->item->fresh()->attribute_points);
    }

    public function test_invalid_forward_response_does_not_produce_a_preview(): void
    {
        $this->scaledPayload = ['error' => 'invalid'];
        $this->postJson($this->previewUrl())->assertUnprocessable()->assertJsonValidationErrors('reverse');
        $this->assertSame(39, $this->item->fresh()->attributes['strength']);
    }

    public function test_non_numeric_forward_attributes_are_rejected(): void
    {
        $this->scaledPayload = ['strength' => 'invalid'];
        $this->postJson($this->previewUrl())->assertUnprocessable()->assertJsonValidationErrors('reverse');
        $this->assertSame(39, $this->item->fresh()->attributes['strength']);
    }

    public function test_array_in_place_of_scalar_attribute_is_rejected(): void
    {
        $this->scaledPayload = ['strength' => [1, 2]];
        $this->postJson($this->previewUrl())->assertUnprocessable()->assertJsonValidationErrors('reverse');
        $this->assertSame(39, $this->item->fresh()->attributes['strength']);
    }

    public function test_empty_attributes_are_sent_to_engine_as_a_json_object(): void
    {
        $this->item->update(['attributes' => []]);
        $this->postJson($this->previewUrl())->assertOk();
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && json_decode($request->body())->scaledAttributes instanceof \stdClass);
    }

    public function test_empty_allocations_can_be_approved_and_warning_disappears(): void
    {
        $this->reversePayload = ['attributePoints' => [], 'manualAttributePoints' => [], 'attackElements' => []];
        $this->scaledPayload = [];
        $preview = $this->postJson($this->previewUrl())->assertOk()->json();
        $this->assertArrayNotHasKey('strength', $preview['recalculated_attributes']);
        $this->assertArrayNotHasKey('physicalDamage', $preview['recalculated_attributes']);
        $this->patch($this->confirmUrl(), ['preview_token' => $preview['preview_token']])->assertRedirect();
        $this->assertFalse($this->item->fresh()->hasMissingAttributePoints());
        $this->assertSame('{}', $this->item->fresh()->getRawOriginal('attribute_points'));
        $this->assertSame('{}', $this->item->fresh()->getRawOriginal('manual_attribute_points'));
    }

    public function test_resource_reports_missing_null_and_empty_points_but_accepts_manual_only_items(): void
    {
        $this->item->setRelation('usageView', new BaseItemUsageView(['is_in_use' => false]));
        $this->assertTrue((new BaseItemResource($this->item))->resolve(Request::create('/'))['has_missing_attribute_points']);
        $this->item->attribute_points = [];
        $this->item->manual_attribute_points = [];
        $this->assertTrue($this->item->hasMissingAttributePoints());
        $this->item->manual_attribute_points = ['counter' => 3];
        $this->assertFalse((new BaseItemResource($this->item))->resolve(Request::create('/'))['has_missing_attribute_points']);
    }

    public function test_editor_role_is_required_for_preview_and_confirmation(): void
    {
        $this->actingAs(User::factory()->create(['roles' => []]));
        $this->postJson($this->previewUrl())->assertRedirect(route('locked'));
        $this->patchJson($this->confirmUrl(), [])->assertRedirect(route('locked'));
        Http::assertNothingSent();
    }

    public function test_authentication_is_required(): void
    {
        auth()->forgetGuards();
        $this->postJson($this->previewUrl())->assertUnauthorized();
        $this->patchJson($this->confirmUrl(), [])->assertUnauthorized();
        Http::assertNothingSent();
    }

    private function previewUrl(): string
    {
        return route('base-items.attribute-reverse.preview', $this->item->id);
    }

    private function confirmUrl(): string
    {
        return route('base-items.attribute-reverse.confirm', $this->item->id);
    }

    private function reverseResponse(): array
    {
        return ['attributePoints' => ['strength' => 3], 'manualAttributePoints' => ['deepWoundChance' => 15], 'attackElements' => ['PHYSICAL']];
    }

    private function scaledResponse(): array
    {
        return ['strength' => 37, 'physicalDamage' => [48, 58], 'deepWoundChance' => [15, 11], 'needLevel' => 20];
    }
}
