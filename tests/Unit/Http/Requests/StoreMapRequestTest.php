<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreMapRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreMapRequestTest extends TestCase
{
    public function test_it_accepts_a_png_aligned_to_map_tiles(): void
    {
        $validator = Validator::make([
            'name' => 'Mapa testowa',
            'image' => UploadedFile::fake()->image('mapa_testowa.png', 96, 64),
            'fileName' => 'mapa_testowa.png',
        ], (new StoreMapRequest)->rules());

        $this->assertTrue($validator->passes(), implode(' ', $validator->errors()->all()));
    }

    public function test_it_rejects_an_image_that_is_not_aligned_to_map_tiles(): void
    {
        $validator = Validator::make([
            'name' => 'Mapa testowa',
            'image' => UploadedFile::fake()->image('mapa_testowa.png', 97, 64),
            'fileName' => 'mapa_testowa.png',
        ], (new StoreMapRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('podzielną przez 32', $validator->errors()->first('image'));
    }

    public function test_it_rejects_a_map_larger_than_the_editor_limit(): void
    {
        $validator = Validator::make([
            'name' => 'Mapa testowa',
            'image' => UploadedFile::fake()->image('mapa_testowa.png', 4128, 32),
            'fileName' => 'mapa_testowa.png',
        ], (new StoreMapRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('128 × 128 pól', $validator->errors()->first('image'));
    }
}
