<?php

namespace Tests\Feature;

use App\Facades\AssetUrl;
use App\Http\Controllers\PlayingCardImageController;
use App\Http\Requests\UploadPlayingCardImageRequest;
use App\Services\PlayingCardThumbnailService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PlayingCardThumbnailTest extends TestCase
{
    public function test_every_card_thumbnail_is_a_transparent_32_by_32_png(): void
    {
        $service = app(PlayingCardThumbnailService::class);
        foreach ([['close'], ['ranged'], ['siege'], ['close', 'ranged'], []] as $rows) {
            $png = $service->render(['hero' => true, 'rows' => $rows, 'kind' => $rows === [] ? 'weather' : 'unit']);
            [$width, $height, $type] = getimagesizefromstring($png);
            $this->assertSame([32, 32, IMAGETYPE_PNG], [$width, $height, $type]);
            $image = imagecreatefromstring($png);
            $this->assertSame(127, (imagecolorat($image, 0, 0) >> 24) & 0x7F);
            imagedestroy($image);
        }
    }

    public function test_hero_and_regular_cards_have_different_frames(): void
    {
        $service = app(PlayingCardThumbnailService::class);
        $card = ['hero' => false, 'rows' => ['close'], 'kind' => 'unit'];
        $common = $service->render($card);
        $hero = $service->render(array_replace($card, ['hero' => true]));
        $this->assertNotSame($common, $hero);
    }

    public function test_uploaded_illustration_is_validated_and_stored_under_card_assets(): void
    {
        Storage::fake('s3');
        $image = UploadedFile::fake()->image('portrait.png', 128, 192);
        $request = UploadPlayingCardImageRequest::create('/', 'POST', [], [], ['image' => $image]);
        $this->assertFalse(Validator::make(['image' => $image], $request->rules())->fails());
        AssetUrl::swap(new class
        {
            public function fromPath(string $path): string
            {
                return 'https://assets.test/'.$path;
            }
        });

        $response = (new PlayingCardImageController)($request)->getData(true);

        $this->assertStringStartsWith('img/playing-cards/uploads/', $response['path']);
        Storage::disk('s3')->assertExists($response['path']);
        $invalid = UploadedFile::fake()->create('portrait.svg', 1, 'image/svg+xml');
        $this->assertTrue(Validator::make(['image' => $invalid], $request->rules())->fails());
    }
}
