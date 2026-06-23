<?php

namespace Tests\Feature;

use App\Http\Requests\BulkUpdateBaseItemDescriptionRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurrentWorldRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.retro', config('database.connections.sqlite'));

        Schema::connection('retro')->dropIfExists('base_items');
        Schema::connection('retro')->create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Route::post('/testing/current-world-request', function (BulkUpdateBaseItemDescriptionRequest $request) {
            return response()->json($request->validated());
        })->middleware('web');
    }

    public function test_current_world_request_uses_current_world_connection_for_exists_rules(): void
    {
        DB::connection('retro')->table('base_items')->insert([
            'id' => 123,
            'name' => 'Test item',
        ]);

        $response = $this
            ->withSession(['world' => 'retro'])
            ->post('/testing/current-world-request', [
                'item_ids' => [123],
                'search_phrase' => 'old',
                'replacement_phrase' => 'new',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('item_ids.0', 123);
    }

    public function test_current_world_request_rejects_missing_ids_on_current_world_connection(): void
    {
        $response = $this
            ->from('/previous')
            ->withSession(['world' => 'retro'])
            ->post('/testing/current-world-request', [
                'item_ids' => [999],
                'search_phrase' => 'old',
                'replacement_phrase' => 'new',
            ]);

        $response
            ->assertRedirect('/previous')
            ->assertSessionHasErrors('item_ids.0');
    }
}
