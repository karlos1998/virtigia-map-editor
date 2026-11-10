<?php

namespace Tests\Feature;

use App\Enums\BaseItemCurrency;
use App\Http\Resources\BaseItemBookPreviewResource;
use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use App\Models\BaseItemUsageView;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BaseItemStackValueTest extends TestCase
{
    #[DataProvider('stackValues')]
    public function test_resources_expose_unit_price_and_stack_value(BaseItemCurrency $currency, array $attributes, int $stackValue): void
    {
        $item = new BaseItem([
            'price' => 190,
            'currency' => $currency,
            'attributes' => $attributes,
        ]);
        $item->setRelation('usageView', new BaseItemUsageView(['is_in_use' => false]));

        $request = Request::create('/');

        foreach ([new BaseItemResource($item), new BaseItemBookPreviewResource($item)] as $resource) {
            $data = $resource->resolve($request);

            $this->assertSame(190, $data['price']);
            $this->assertSame($stackValue, $data['stack_value']);
        }
    }

    public static function stackValues(): array
    {
        return [
            '800 arrows' => [BaseItemCurrency::GOLD, ['quantity' => 800], 152000],
            'single item' => [BaseItemCurrency::GOLD, [], 190],
            'zero quantity' => [BaseItemCurrency::GOLD, ['quantity' => 0], 190],
            'dragon tear charges' => [BaseItemCurrency::DRAGON_TEAR, ['quantity' => 800], 190],
        ];
    }
}
