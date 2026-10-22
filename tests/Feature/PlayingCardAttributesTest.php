<?php

namespace Tests\Feature;

use App\Enums\BaseItemCategory;
use App\Http\Requests\UpdateItemAttributesRequest;
use App\Models\BaseItem;
use App\Rules\PlayingCardAttributes;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PlayingCardAttributesTest extends TestCase
{
    private function card(array $overrides = []): array
    {
        return array_replace([
            'definitionId' => 'retro-spy', 'deck' => 'retro', 'kind' => 'unit',
            'strength' => 0, 'hero' => false, 'rows' => ['close', 'ranged'], 'abilities' => ['spy'],
        ], $overrides);
    }

    public function test_zero_strength_spies_and_agile_units_are_valid(): void
    {
        $validator = Validator::make(['card' => $this->card()], ['card' => new PlayingCardAttributes]);

        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
        $this->assertSame('playingCards', BaseItemCategory::PLAYING_CARDS->value);
    }

    public function test_invalid_card_combinations_are_rejected(): void
    {
        foreach ([
            ['strength' => -1], ['strength' => 1.5], ['rows' => []], ['rows' => ['invalid']],
            ['rows' => ['close', 'close']], ['abilities' => ['unknown']],
            ['kind' => 'special', 'hero' => true, 'strength' => null],
            ['kind' => 'leader', 'strength' => 5], ['abilities' => ['bond']],
            ['abilities' => ['muster']], ['abilities' => ['avenger']],
            ['artwork' => ['outfitSrc' => 'javascript:alert(1)']],
            ['artwork' => ['frameX' => 4]],
            ['kind' => 'weather', 'strength' => null, 'rows' => [], 'abilities' => ['spy']],
            ['abilities' => ['frost']],
            ['kind' => 'special', 'strength' => null, 'rows' => [], 'abilities' => ['scorch', 'horn']],
        ] as $overrides) {
            $validator = Validator::make(['card' => $this->card($overrides)], ['card' => new PlayingCardAttributes]);
            $this->assertTrue($validator->fails(), json_encode($overrides));
        }
    }

    public function test_special_and_hero_medic_are_valid(): void
    {
        foreach ([
            ['kind' => 'special', 'strength' => null, 'rows' => [], 'abilities' => ['decoy']],
            ['hero' => true, 'strength' => 15, 'abilities' => ['medic']],
            ['abilities' => ['bond'], 'bondGroup' => 'retro-infantry'],
            ['abilities' => ['muster'], 'musterTargets' => ['retro-shadows']],
        ] as $overrides) {
            $validator = Validator::make(['card' => $this->card($overrides)], ['card' => new PlayingCardAttributes]);
            $this->assertFalse($validator->fails(), $validator->errors()->toJson());
        }
    }

    public function test_every_retro_catalog_definition_is_valid_and_every_effect_target_exists(): void
    {
        $catalog = json_decode(file_get_contents(resource_path('retro-playing-cards.json')), true, flags: JSON_THROW_ON_ERROR);
        $ids = array_column(array_column($catalog, 'card'), 'definitionId');
        $this->assertCount(54, $catalog);
        $this->assertCount(count($ids), array_unique($ids));

        foreach ($catalog as $entry) {
            $card = $entry['card'];
            $validator = Validator::make(['card' => $card], ['card' => new PlayingCardAttributes]);
            $this->assertFalse($validator->fails(), $entry['label'].': '.$validator->errors()->toJson());
            foreach ($card['musterTargets'] as $target) {
                $this->assertContains($target, $ids);
            }
            if ($card['avengerTarget'] !== null) {
                $this->assertContains($card['avengerTarget'], $ids);
            }
        }
    }

    public function test_item_attribute_request_requires_a_definition_for_card_items(): void
    {
        $item = new BaseItem;
        $item->category = BaseItemCategory::PLAYING_CARDS;
        $request = \Mockery::mock(UpdateItemAttributesRequest::class)->makePartial();
        $request->shouldReceive('route')->with('baseItem')->andReturn($item);

        $invalid = Validator::make(['attributes' => ['description' => 'Opis']], $request->rules());
        $this->assertTrue($invalid->fails());
        $this->assertTrue($invalid->errors()->has('attributes.playingCard'));

        $valid = Validator::make(['attributes' => ['playingCard' => $this->card()]], $request->rules());
        $this->assertFalse($valid->fails(), $valid->errors()->toJson());
    }

    public function test_validated_attributes_preserve_descriptions_and_existing_rpg_attributes(): void
    {
        $item = new BaseItem;
        $item->category = BaseItemCategory::NEUTRALS;
        $request = \Mockery::mock(UpdateItemAttributesRequest::class)->makePartial();
        $request->shouldReceive('route')->with('baseItem')->andReturn($item);
        $attributes = [
            'description' => 'Własny opis',
            'needLevel' => 10,
            'teleportTo' => ['mapId' => 1, 'x' => 2, 'y' => 3],
        ];
        $validator = Validator::make(['attributes' => $attributes], $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertSame($attributes, $validator->validated()['attributes']);

        $item->category = BaseItemCategory::PLAYING_CARDS;
        $attributes['playingCard'] = $this->card();
        $validator = Validator::make(['attributes' => $attributes], $request->rules());
        $this->assertFalse($validator->fails());
        $validated = $validator->validated()['attributes'];
        $this->assertCount(count($attributes), $validated);
        foreach ($attributes as $key => $value) {
            $this->assertSame($value, $validated[$key]);
        }
    }
}
