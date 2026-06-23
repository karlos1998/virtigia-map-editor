<?php

namespace App\Services;

use App\Models\WorldTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class WorldTemplateConnectionResolver
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function visibleOptions(): array
    {
        return $this->templates()
            ->filter(fn (WorldTemplate $template): bool => $template->is_active && $template->is_visible)
            ->map(fn (WorldTemplate $template): array => $template->toOption())
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function visibleLabels(): array
    {
        return $this->templates()
            ->filter(fn (WorldTemplate $template): bool => $template->is_active && $template->is_visible)
            ->mapWithKeys(fn (WorldTemplate $template): array => [$template->slug => $template->name])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function visibleSlugs(): array
    {
        return array_keys($this->visibleLabels());
    }

    /**
     * @return array<int, string>
     */
    public function activeSlugs(): array
    {
        return $this->templates()
            ->filter(fn (WorldTemplate $template): bool => $template->is_active)
            ->pluck('slug')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function remoteDatabaseServerOptions(): array
    {
        return collect(config('world_templates.remote_database_servers', []))
            ->map(fn (array $server, string $key): array => [
                'value' => $key,
                'label' => $server['label'] ?? $key,
            ])
            ->values()
            ->all();
    }

    public function defaultWorldSlug(): string
    {
        return (string) config('world_templates.default', 'retro');
    }

    public function resolve(string $slug): ?WorldTemplate
    {
        return $this->templates()
            ->first(fn (WorldTemplate $template): bool => $template->slug === $slug && $template->is_active);
    }

    public function connectionNameFor(string $slug): ?string
    {
        return $this->resolve($slug)?->connection_name;
    }

    public function registerConfiguredConnections(): void
    {
        $this->templates()
            ->filter(fn (WorldTemplate $template): bool => $template->is_active)
            ->each(fn (WorldTemplate $template): bool => $this->registerTemplateConnection($template));
    }

    public function registerTemplateConnection(WorldTemplate $template): bool
    {
        /** @var array<string, mixed>|null $configuredConnection */
        $configuredConnection = config("database.connections.{$template->connection_name}");

        if (is_array($configuredConnection) && ($configuredConnection['world_template'] ?? false) !== true) {
            return true;
        }

        $server = $this->remoteDatabaseServer($template->remote_database_server);

        if ($server === null) {
            return false;
        }

        config()->set("database.connections.{$template->connection_name}", [
            ...$server,
            'database' => $template->database_name,
            'world_template' => true,
        ]);

        DB::purge($template->connection_name);

        return true;
    }

    /**
     * @return array{status: 'ok'|'error', message: string|null}
     */
    public function databaseStatus(WorldTemplate $template): array
    {
        try {
            if (! $this->registerTemplateConnection($template)) {
                return [
                    'status' => 'error',
                    'message' => "Nie znaleziono konfiguracji zdalnej bazy [{$template->remote_database_server}].",
                ];
            }

            DB::connection($template->connection_name)->getPdo();

            return [
                'status' => 'ok',
                'message' => null,
            ];
        } catch (Throwable $throwable) {
            return [
                'status' => 'error',
                'message' => $throwable->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function remoteDatabaseServer(string $serverKey): ?array
    {
        /** @var array<string, mixed>|null $server */
        $server = config("world_templates.remote_database_servers.{$serverKey}");

        if ($server === null) {
            return null;
        }

        return collect($server)
            ->except('label')
            ->all();
    }

    /**
     * @return Collection<int, WorldTemplate>
     */
    public function templates(): Collection
    {
        return $this->databaseTemplates();
    }

    /**
     * @return Collection<int, WorldTemplate>
     */
    private function databaseTemplates(): Collection
    {
        try {
            if (! Schema::hasTable('world_templates')) {
                return collect();
            }

            return WorldTemplate::query()
                ->orderBy('id')
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }
}
