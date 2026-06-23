<?php

namespace App\Jobs;

use App\Services\DatabaseDumpService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Throwable;

class BuildDatabaseDumpJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;

    public int $tries = 1;

    public function __construct(
        public readonly string $world,
        public readonly string $dumpId,
    ) {}

    public function handle(DatabaseDumpService $databaseDumpService): void
    {
        $databaseDumpService->updateStatus($this->world, $this->dumpId, [
            'status' => 'estimating',
            'progress' => null,
            'message' => 'Szacuję rozmiar bazy',
            'started_at' => now()->toIso8601String(),
        ]);

        $estimatedBytes = $databaseDumpService->estimateWorldSize($this->world);

        $path = storage_path('app/database-dumps/'.$this->dumpId.'.sql');

        $databaseDumpService->updateStatus($this->world, $this->dumpId, [
            'status' => 'dumping',
            'progress' => $estimatedBytes === null || $estimatedBytes <= 0 ? null : 1,
            'message' => 'Tworzę dump bazy danych',
            'estimated_bytes' => $estimatedBytes,
        ]);

        $processPayload = $databaseDumpService->createDumpProcess($this->world, $path);
        $process = $processPayload['process'];
        $credentialHandle = $processPayload['credentialHandle'] ?? null;

        try {
            $process->start();

            while ($process->isRunning()) {
                clearstatcache(true, $path);

                $dumpedBytes = File::exists($path) ? File::size($path) : 0;

                $databaseDumpService->updateStatus($this->world, $this->dumpId, [
                    'status' => 'dumping',
                    'progress' => $databaseDumpService->progressFromBytes($dumpedBytes, $estimatedBytes),
                    'message' => 'Tworzę dump bazy danych',
                    'dumped_bytes' => $dumpedBytes,
                    'estimated_bytes' => $estimatedBytes,
                    'file_size' => $dumpedBytes,
                ]);

                sleep(1);
            }

            $databaseDumpService->ensureDumpProcessSucceeded($process, $path);

            $fileSize = File::size($path);

            $databaseDumpService->markReady($this->world, $this->dumpId, $fileSize);
        } catch (Throwable $throwable) {
            $databaseDumpService->failStatus($this->world, $this->dumpId, $throwable);

            throw $throwable;
        } finally {
            if (is_resource($credentialHandle)) {
                fclose($credentialHandle);
            }
        }
    }

    public function failed(Throwable $throwable): void
    {
        app(DatabaseDumpService::class)->failStatus($this->world, $this->dumpId, $throwable);
    }
}
