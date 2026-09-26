<?php

namespace Tests\Unit\Services;

use App\Services\MapImageOptimizer;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MapImageOptimizerTest extends TestCase
{
    public function test_it_optimizes_an_uploaded_map_image_without_changing_its_dimensions(): void
    {
        $file = UploadedFile::fake()->image('mapa.jpg', 96, 64);

        $result = (new MapImageOptimizer)->optimize($file);
        $imageInfo = getimagesizefromstring($result['contents']);

        $this->assertIsArray($imageInfo);
        $this->assertSame(96, $result['width']);
        $this->assertSame(64, $result['height']);
        $this->assertSame(96, $imageInfo[0]);
        $this->assertSame(64, $imageInfo[1]);
        $this->assertLessThanOrEqual($result['original_size'], $result['optimized_size']);
    }
}
