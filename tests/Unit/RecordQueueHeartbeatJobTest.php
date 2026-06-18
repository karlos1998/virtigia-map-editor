<?php

namespace Tests\Unit;

use App\Jobs\RecordQueueHeartbeatJob;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class RecordQueueHeartbeatJobTest extends TestCase
{
    public function test_it_records_queue_heartbeat_in_redis_cache(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-18 12:00:00'));

        $cacheRepository = Mockery::mock(Repository::class);

        Cache::shouldReceive('store')
            ->once()
            ->with('redis')
            ->andReturn($cacheRepository);

        $cacheRepository
            ->shouldReceive('forever')
            ->once()
            ->with(
                RecordQueueHeartbeatJob::CACHE_KEY,
                Mockery::on(fn (string $value): bool => CarbonImmutable::parse($value)->equalTo(now()))
            );

        (new RecordQueueHeartbeatJob)->handle();
    }
}
