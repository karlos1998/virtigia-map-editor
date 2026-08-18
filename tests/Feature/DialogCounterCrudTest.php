<?php

namespace Tests\Feature;

use App\Enums\DialogCounterScope;
use App\Http\Requests\StoreDialogCounterRequest;
use App\Http\Requests\UpdateDialogCounterRequest;
use App\Models\DialogCounter;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DialogCounterCrudTest extends TestCase
{
    public function test_store_and_update_requests_accept_all_supported_scopes(): void
    {
        foreach (DialogCounterScope::cases() as $scope) {
            $payload = ['name' => 'Rozmowa', 'scope' => $scope->value];

            $storeValidator = Validator::make($payload, (new StoreDialogCounterRequest)->rules());
            $updateValidator = Validator::make(
                ['scope' => $scope->value],
                ['scope' => (new UpdateDialogCounterRequest)->rules()['scope']],
            );

            $this->assertFalse($storeValidator->fails());
            $this->assertFalse($updateValidator->fails());
        }
    }

    public function test_requests_reject_unknown_scope(): void
    {
        $payload = ['name' => 'Rozmowa', 'scope' => 'party'];

        $validator = Validator::make($payload, (new StoreDialogCounterRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('scope', $validator->errors()->toArray());
    }

    public function test_model_casts_scope_to_enum(): void
    {
        $counter = new DialogCounter([
            'name' => 'Rozmowa',
            'scope' => 'user',
        ]);

        $this->assertSame(DialogCounterScope::USER, $counter->scope);
    }
}
