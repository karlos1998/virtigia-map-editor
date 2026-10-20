<?php

namespace Tests\Feature;

use App\Jobs\RunAssetOperation;
use App\Models\User;
use App\Services\AssetManagerService;
use App\Services\AssetOperationService;
use Aws\Result;
use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class AssetManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        Storage::fake('local');
        Queue::fake();
        $this->actingAs(User::factory()->create(['roles' => ['administrator']]));
    }

    public function test_administrator_can_open_the_page_without_selecting_a_world(): void
    {
        $this->get(route('administration.assets.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Administration/Assets/Index')->where('initialPath', '')->has('maxUploadBytes'));
    }

    #[DataProvider('protectedEndpoints')]
    public function test_other_roles_cannot_access_any_file_manager_endpoint(string $method, string $route): void
    {
        $this->actingAs(User::factory()->create(['roles' => ['game_master']]));
        $this->json($method, route('administration.assets.'.$route, $route === 'operations.download' ? ['operation' => 'bf43b825-23ca-4fc6-a1ee-af199da59f5e'] : []))->assertForbidden();
    }

    public static function protectedEndpoints(): iterable
    {
        foreach (['index', 'listing', 'preview', 'thumbnail', 'download', 'operations.index', 'operations.download'] as $route) {
            yield $route => ['GET', $route];
        }
        foreach (['directories.store', 'uploads.store', 'operations.store'] as $route) {
            yield $route => ['POST', $route];
        }
    }

    public function test_listing_is_shallow_and_search_is_recursive_with_pagination(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('img/npc/wolf.gif', 'wolf');
        $disk->put('img/item/WOLF-sword.gif', 'sword');
        $disk->put('img/cat.gif', 'cat');
        $disk->put('other/wolf.gif', 'outside');
        $disk->makeDirectory('img/empty');
        $this->getJson(route('administration.assets.listing', ['path' => 'img']))
            ->assertOk()->assertJsonPath('total', 4)->assertJsonPath('entries.0.type', 'directory');
        $this->getJson(route('administration.assets.listing', ['path' => 'img', 'directories_only' => 1]))
            ->assertJsonPath('total', 3)->assertJsonCount(3, 'entries');
        config()->set('asset_manager.per_page', 1);
        $this->getJson(route('administration.assets.listing', ['path' => 'img', 'search' => 'wolf', 'page' => 2]))
            ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'entries')
            ->assertJsonPath('entries.0.path', 'img/npc/wolf.gif')
            ->assertJsonPath('entries.0.is_image', true);
        $this->getJson(route('administration.assets.listing', ['path' => 'missing']))->assertNotFound();
    }

    public function test_creating_empty_folders_and_uploading_files_preserves_names_and_bytes(): void
    {
        $this->postJson(route('administration.assets.directories.store'), ['path' => '', 'name' => 'Grafiki'])
            ->assertCreated();
        $this->assertTrue(Storage::disk('s3')->directoryExists('Grafiki'));
        $file = UploadedFile::fake()->createWithContent('żółw.gif', 'GIF89a original bytes');
        $this->postJson(route('administration.assets.uploads.store'), ['path' => 'Grafiki', 'file' => $file])
            ->assertCreated();
        $this->assertSame('GIF89a original bytes', Storage::disk('s3')->get('Grafiki/żółw.gif'));
        $this->postJson(route('administration.assets.uploads.store'), ['path' => 'Grafiki', 'file' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors('destination');
        $this->postJson(route('administration.assets.directories.store'), ['path' => '', 'name' => 'Grafiki'])
            ->assertUnprocessable();
        $this->postJson(route('administration.assets.directories.store'), ['path' => '', 'name' => '../outside'])
            ->assertUnprocessable();
    }

    #[DataProvider('invalidPaths')]
    public function test_invalid_paths_are_rejected(string $path): void
    {
        $this->getJson(route('administration.assets.listing', ['path' => $path]))->assertUnprocessable();
        $this->postJson(route('administration.assets.operations.store'), [
            'path' => $path, 'type' => 'directory', 'operation' => 'delete',
        ])->assertUnprocessable();
    }

    public static function invalidPaths(): iterable
    {
        foreach (['../secret', '/absolute', 'img/../secret', 'img//npc', 'img\\npc', "img/\0secret", 'img/.'] as $path) {
            yield [$path];
        }
    }

    public function test_root_mutations_oversized_uploads_and_missing_files_are_rejected(): void
    {
        $this->postJson(route('administration.assets.operations.store'), ['path' => '', 'type' => 'directory', 'operation' => 'delete'])->assertUnprocessable();
        $this->postJson(route('administration.assets.uploads.store'), ['file' => UploadedFile::fake()->create('large.png', 51201)])->assertUnprocessable();
        $this->getJson(route('administration.assets.download', ['path' => 'absent.gif']))->assertNotFound();
    }

    public function test_file_downloads_preserve_bytes_and_only_raster_images_can_be_previewed(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('readme.html', '<script>alert(1)</script>');
        $response = $this->get(route('administration.assets.download', ['path' => 'readme.html']));
        $response->assertOk()->assertDownload('readme.html')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('<script>alert(1)</script>', $response->streamedContent());
        $this->get(route('administration.assets.preview', ['path' => 'readme.html']))->assertStatus(415);
        $file = UploadedFile::fake()->image('image.png');
        $disk->put('image.png', file_get_contents($file->getRealPath()));
        $this->get(route('administration.assets.preview', ['path' => 'image.png']))->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_directory_rename_copies_subtrees_and_empty_directories_before_removing_source(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('source/nested/file.gif', 'bytes');
        $disk->makeDirectory('source/empty');
        $id = $this->start('move', 'source', 'directory', 'renamed');
        Queue::assertPushed(RunAssetOperation::class, fn ($job) => $job->operationId === $id && $job->connection === 'asset-manager');
        app(AssetOperationService::class)->run($id);
        $this->assertFalse($disk->directoryExists('source'));
        $this->assertSame('bytes', $disk->get('renamed/nested/file.gif'));
        $this->assertTrue($disk->directoryExists('renamed/empty'));
        $this->getJson(route('administration.assets.operations.index'))->assertJsonPath('0.status', 'completed');
    }

    public function test_thumbnails_are_resized_cached_and_refreshed_when_original_changes(): void
    {
        $disk = Storage::disk('s3');
        $file = UploadedFile::fake()->image('map.png', 1280, 640);
        $disk->put('map.png', file_get_contents($file->getRealPath()));
        $url = route('administration.assets.thumbnail', ['path' => 'map.png']);
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $image = getimagesize($response->baseResponse->getFile()->getPathname());
        $this->assertSame([320, 160], [$image[0], $image[1]]);
        $this->get($url)->assertOk();
        $this->assertCount(1, Storage::disk('local')->files('asset-thumbnails'));
        $replacement = UploadedFile::fake()->image('map.png', 640, 1280);
        $disk->put('map.png', file_get_contents($replacement->getRealPath()));
        $response = $this->get($url)->assertOk();
        $image = getimagesize($response->baseResponse->getFile()->getPathname());
        $this->assertSame([160, 320], [$image[0], $image[1]]);
        $this->assertCount(2, Storage::disk('local')->files('asset-thumbnails'));
        $disk->put('invalid.png', 'not an image');
        $this->get(route('administration.assets.thumbnail', ['path' => 'invalid.png']))->assertStatus(415);
    }

    public function test_copy_keeps_source_and_delete_removes_only_selected_prefix(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('source/file.gif', 'original');
        $disk->put('source-other/file.gif', 'other');
        $id = $this->start('copy', 'source/file.gif', 'file', 'copied.gif');
        app(AssetOperationService::class)->run($id);
        $this->assertSame('original', $disk->get('copied.gif'));
        $disk->assertExists('source/file.gif');
        $id = $this->start('delete', 'source', 'directory');
        app(AssetOperationService::class)->run($id);
        $disk->assertMissing('source/file.gif');
        $disk->assertExists('source-other/file.gif');
    }

    public function test_rename_rejects_collisions_and_descendant_destinations(): void
    {
        Storage::disk('s3')->put('source/a.gif', 'a');
        Storage::disk('s3')->put('taken', 'b');
        foreach (['taken', 'source', 'source/child'] as $destination) {
            $this->postJson(route('administration.assets.operations.store'), [
                'operation' => 'move', 'path' => 'source', 'type' => 'directory', 'destination' => $destination,
            ])->assertUnprocessable();
        }
        Queue::assertNothingPushed();
    }

    public function test_failed_directory_copy_retains_originals_and_partial_destination(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('source/a.gif', 'a');
        $disk->put('source/b.gif', 'b');
        $id = $this->start('move', 'source', 'directory', 'target');
        $failingDisk = Mockery::mock($disk);
        $copies = 0;
        $failingDisk->shouldReceive('copy')->andReturnUsing(function (string $source, string $target) use ($disk, &$copies): bool {
            return ++$copies === 2 ? false : $disk->copy($source, $target);
        });
        $manager = Mockery::mock(AssetManagerService::class)->makePartial();
        $manager->shouldReceive('disk')->andReturn($failingDisk);
        $this->app->instance(AssetManagerService::class, $manager);
        try {
            app(AssetOperationService::class)->run($id);
            $this->fail('Expected failed copy.');
        } catch (RuntimeException) {
            $disk->assertExists(['source/a.gif', 'source/b.gif']);
            $this->assertCount(1, $disk->files('target'));
            $this->getJson(route('administration.assets.operations.index'))->assertJsonPath('0.status', 'failed');
        }
    }

    public function test_archive_contains_original_bytes_nested_paths_and_empty_folders_and_is_private(): void
    {
        Storage::disk('s3')->put('images/nested/żółw.gif', 'original');
        Storage::disk('s3')->makeDirectory('images/empty');
        $id = $this->start('archive', 'images', 'directory');
        app(AssetOperationService::class)->run($id);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path('asset-manager/'.$id.'/archive.zip')));
        $this->assertSame('original', $zip->getFromName('images/nested/żółw.gif'));
        $this->assertNotFalse($zip->locateName('images/empty/'));
        $zip->close();
        $this->assertFalse(Storage::disk('local')->directoryExists('asset-manager/'.$id.'/parts'));
        $url = route('administration.assets.operations.download', ['operation' => $id]);
        $this->get($url)->assertOk()->assertDownload('images.zip');
        $this->actingAs(User::factory()->create(['roles' => ['administrator']]));
        $this->get($url)->assertNotFound();
        $this->getJson(route('administration.assets.operations.index'))->assertExactJson([]);
    }

    public function test_moving_a_directory_preserves_files_added_during_the_copy(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('source/a.gif', 'a');
        $id = $this->start('move', 'source', 'directory', 'target');
        $concurrentDisk = Mockery::mock($disk);
        $concurrentDisk->shouldReceive('copy')->with('source/a.gif', 'target/a.gif')->andReturnUsing(function () use ($disk): bool {
            $disk->put('source/new.gif', 'new upload');

            return $disk->copy('source/a.gif', 'target/a.gif');
        });
        $manager = Mockery::mock(AssetManagerService::class)->makePartial();
        $manager->shouldReceive('disk')->andReturn($concurrentDisk);
        $this->app->instance(AssetManagerService::class, $manager);
        try {
            app(AssetOperationService::class)->run($id);
            $this->fail('Expected a report about the preserved new file.');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertSame('new upload', $disk->get('source/new.gif'));
            $this->assertSame('a', $disk->get('target/a.gif'));
        }
    }

    public function test_changed_files_are_not_deleted_after_copying(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('a.gif', 'original');
        $id = $this->start('move', 'a.gif', 'file', 'b.gif');
        $concurrentDisk = Mockery::mock($disk);
        $concurrentDisk->shouldReceive('copy')->with('a.gif', 'b.gif')->andReturnUsing(function () use ($disk): bool {
            $copied = $disk->copy('a.gif', 'b.gif');
            $disk->put('a.gif', 'replacement');

            return $copied;
        });
        $manager = Mockery::mock(AssetManagerService::class)->makePartial();
        $manager->shouldReceive('disk')->andReturn($concurrentDisk);
        $this->app->instance(AssetManagerService::class, $manager);
        try {
            app(AssetOperationService::class)->run($id);
            $this->fail('Expected changed content to be preserved.');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertSame('replacement', $disk->get('a.gif'));
            $this->assertSame('original', $disk->get('b.gif'));
        }
    }

    public function test_whitespace_in_keys_is_preserved_without_targeting_adjacent_files(): void
    {
        Storage::disk('s3')->put('wolf.gif', 'unspaced');
        $this->postJson(route('administration.assets.uploads.store'), [
            'file' => UploadedFile::fake()->createWithContent('wolf.gif ', 'spaced'),
        ])->assertCreated();
        $response = $this->get(route('administration.assets.download', ['path' => 'wolf.gif ']));
        $response->assertOk();
        $this->assertSame('spaced', $response->streamedContent());
        $id = $this->start('move', 'wolf.gif ', 'file', 'renamed.gif ');
        app(AssetOperationService::class)->run($id);
        $this->assertSame('unspaced', Storage::disk('s3')->get('wolf.gif'));
        $this->assertSame('spaced', Storage::disk('s3')->get('renamed.gif '));
    }

    public function test_s3_moves_delete_only_copied_objects_and_directory_markers(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('source/nested/a.gif', 'a');
        $id = $this->start('move', 'source', 'directory', 'target');
        $s3 = Mockery::mock(AwsS3V3Adapter::class);
        foreach (['fileExists', 'directoryExists', 'getDriver', 'makeDirectory', 'copy', 'checksum', 'delete'] as $method) {
            $s3->shouldReceive($method)->andReturnUsing(fn (...$args) => $disk->{$method}(...$args));
        }
        $s3->shouldReceive('deleteDirectory')->never();
        $s3->shouldReceive('getConfig')->andReturn(['bucket' => 'assets', 'root' => 'prefix']);
        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('deleteObject')->twice()->andReturnUsing(function (array $params) use ($disk): Result {
            $this->assertSame('assets', $params['Bucket']);
            $this->assertContains($params['Key'], ['prefix/source/nested/', 'prefix/source/']);
            rmdir($disk->path(substr(rtrim($params['Key'], '/'), strlen('prefix/'))));

            return new Result;
        });
        $s3->shouldReceive('getClient')->andReturn($client);
        $manager = Mockery::mock(AssetManagerService::class)->makePartial();
        $manager->shouldReceive('disk')->andReturn($s3);
        $this->app->instance(AssetManagerService::class, $manager);
        app(AssetOperationService::class)->run($id);
        $disk->assertExists('target/nested/a.gif');
        $this->assertFalse($disk->directoryExists('source'));
    }

    public function test_copy_may_have_a_different_multipart_etag_without_blocking_a_move(): void
    {
        $disk = Storage::disk('s3');
        $disk->put('original.gif', 'bytes');
        $id = $this->start('move', 'original.gif', 'file', 'copy.gif');
        $multipartDisk = Mockery::mock($disk);
        $multipartDisk->shouldReceive('checksum')->with('original.gif')->andReturnUsing(fn () => $disk->checksum('original.gif'));
        $multipartDisk->shouldReceive('checksum')->with('copy.gif')->andReturn('different-multipart-etag-2');
        $manager = Mockery::mock(AssetManagerService::class)->makePartial();
        $manager->shouldReceive('disk')->andReturn($multipartDisk);
        $this->app->instance(AssetManagerService::class, $manager);
        app(AssetOperationService::class)->run($id);
        $disk->assertMissing('original.gif');
        $this->assertSame('bytes', $disk->get('copy.gif'));
    }

    public function test_expired_archives_are_pruned_without_touching_recent_archives(): void
    {
        Storage::disk('local')->put('asset-manager/expired/archive.zip', 'old');
        Storage::disk('local')->put('asset-manager/recent/archive.zip', 'new');
        touch(Storage::disk('local')->path('asset-manager/expired/archive.zip'), now()->subDays(2)->timestamp);
        $this->artisan('assets:prune-operations')->assertSuccessful();
        Storage::disk('local')->assertMissing('asset-manager/expired/archive.zip');
        Storage::disk('local')->assertExists('asset-manager/recent/archive.zip');
    }

    public function test_worker_timeout_is_lower_than_supervisor_and_retry_after(): void
    {
        $job = new RunAssetOperation('test');
        $this->assertLessThan(config('horizon.defaults.asset-manager-supervisor.timeout'), $job->timeout);
        $this->assertLessThan(config('queue.connections.asset-manager.retry_after'), config('horizon.defaults.asset-manager-supervisor.timeout'));
    }

    private function start(string $operation, string $path, string $type, ?string $destination = null): string
    {
        return $this->postJson(route('administration.assets.operations.store'), compact('operation', 'path', 'type', 'destination'))
            ->assertAccepted()->assertJsonPath('status', 'queued')->json('id');
    }
}
