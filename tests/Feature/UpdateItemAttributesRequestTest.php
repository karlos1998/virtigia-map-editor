<?php

namespace Tests\Feature;

use App\Http\Requests\UpdateItemAttributesRequest;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateItemAttributesRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::patch('/testing/item-attributes', fn (UpdateItemAttributesRequest $request) => response()->json([
            'attributes' => $request->input('attributes'),
        ]));
    }

    #[DataProvider('emptyHealingValues')]
    public function test_resaving_removes_empty_healing_without_changing_other_attributes(mixed $healing): void
    {
        $this->patchJson('/testing/item-attributes', ['attributes' => [
            'healChanceAfterFight' => $healing,
            'needLevel' => 100,
            'fasterRevivalRecovery' => 0,
            'combatHealthRestoration' => 352,
            'description' => 'Halloween #YEAR r.',
        ]])->assertOk()->assertExactJson(['attributes' => [
            'needLevel' => 100,
            'fasterRevivalRecovery' => 0,
            'combatHealthRestoration' => 352,
            'description' => 'Halloween #YEAR r.',
        ]]);
    }

    public static function emptyHealingValues(): array
    {
        return [[null], [''], [[]], [[null, null]], [['', '']]];
    }

    public function test_can_remove_the_last_optional_attribute(): void
    {
        $this->patchJson('/testing/item-attributes', ['attributes' => ['healChanceAfterFight' => [null, null]]])
            ->assertOk()->assertExactJson(['attributes' => []]);
    }

    public function test_preserves_complete_healing_and_accepts_an_absent_attribute(): void
    {
        foreach ([[1, 1], [99, 1000]] as $healing) {
            $this->patchJson('/testing/item-attributes', ['attributes' => ['healChanceAfterFight' => $healing]])
                ->assertOk()->assertJsonPath('attributes.healChanceAfterFight', $healing);
        }

        $this->patchJson('/testing/item-attributes', ['attributes' => ['needLevel' => 100]])
            ->assertOk()->assertExactJson(['attributes' => ['needLevel' => 100]]);
    }

    #[DataProvider('invalidHealingValues')]
    public function test_rejects_incomplete_or_invalid_healing(mixed $healing): void
    {
        $this->patchJson('/testing/item-attributes', ['attributes' => ['healChanceAfterFight' => $healing]])
            ->assertUnprocessable()->assertJsonStructure(['errors']);
    }

    public static function invalidHealingValues(): array
    {
        return [
            [[null, 100]], [[10, null]], [[10]], [[10, 100, 200]],
            [[0, 0]], [[100, 100]], [[-1, 100]], [[10, 0]], [[10, -1]],
            [[1.5, 100]], [[10, 1.5]], [['abc', 100]], [100],
            [['chance' => 10, 'healing' => 100]],
        ];
    }

    public function test_shop_scroll_and_combat_healing_attributes(): void
    {
        $attributes = ['openShop' => ['id' => 17, 'name' => 'Sklep'], 'combatSelfHeal' => 10];
        $this->patchJson('/testing/item-attributes', ['attributes' => $attributes])
            ->assertOk()->assertExactJson(['attributes' => $attributes]);
        $this->patchJson('/testing/item-attributes', ['attributes' => ['openShop' => ['id' => 17], 'combatSelfHeal' => 101]])
            ->assertUnprocessable()->assertJsonValidationErrors(['attributes.openShop.name', 'attributes.combatSelfHeal']);
    }

    public function test_attributes_must_still_be_present_and_an_array(): void
    {
        $this->patchJson('/testing/item-attributes', [])->assertUnprocessable()->assertJsonValidationErrors('attributes');
        $this->patchJson('/testing/item-attributes', ['attributes' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('attributes');
    }
}
