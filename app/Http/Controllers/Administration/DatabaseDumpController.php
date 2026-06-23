<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Jobs\BuildDatabaseDumpJob;
use App\Services\DatabaseDumpService;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseDumpController extends Controller
{
    public function __construct(private readonly DatabaseDumpService $databaseDumpService) {}

    public function index(): Response
    {
        return Inertia::render('Administration/DatabaseDumps', [
            'worlds' => collect(app(WorldTemplateConnectionResolver::class)->visibleLabels())
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values(),
            'statuses' => $this->databaseDumpService->publicStatuses(),
        ]);
    }

    public function start(Request $request, string $world): JsonResponse
    {
        $this->ensureWorldExists($world);

        $result = $this->databaseDumpService->createRequest($world, (int) $request->user()->id);

        if ($result['created']) {
            Bus::batch([
                new BuildDatabaseDumpJob($world, (string) $result['status']['id']),
            ])
                ->name(sprintf('Database dump: %s', ucfirst($world)))
                ->dispatch();
        }

        return response()->json($this->databaseDumpService->publicStatus($world));
    }

    public function status(string $world): JsonResponse
    {
        $this->ensureWorldExists($world);

        return response()->json($this->databaseDumpService->publicStatus($world));
    }

    public function download(string $world, string $dump): StreamedResponse
    {
        $this->ensureWorldExists($world);

        try {
            $databaseDump = $this->databaseDumpService->readyDump($world, $dump);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return response()->streamDownload(function () use ($world, $dump, $databaseDump): void {
            $stream = fopen($databaseDump['path'], 'rb');

            if ($stream === false) {
                @unlink($databaseDump['path']);

                throw new RuntimeException('Nie udało się otworzyć przygotowanego dumpa bazy danych.');
            }

            try {
                while (! feof($stream)) {
                    $chunk = fread($stream, 1024 * 1024);

                    if ($chunk === false) {
                        break;
                    }

                    echo $chunk;

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }

                    flush();
                }
            } finally {
                fclose($stream);
                @unlink($databaseDump['path']);
                $this->databaseDumpService->markDownloaded($world, $dump);
            }
        }, $databaseDump['filename'], [
            'Cache-Control' => 'no-store, private',
            'Content-Length' => (string) $databaseDump['size'],
            'Content-Type' => 'application/sql; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureWorldExists(string $world): void
    {
        abort_unless(in_array($world, app(WorldTemplateConnectionResolver::class)->visibleSlugs(), true), 404);
    }
}
