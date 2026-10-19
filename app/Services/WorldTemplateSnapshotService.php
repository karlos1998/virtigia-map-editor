<?php

namespace App\Services;

use App\Models\WorldTemplateUpdate;
use Generator;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorldTemplateSnapshotService
{
    public function __construct(private readonly WorldTemplateConnectionResolver $resolver) {}

    /** @return array{Connection, Connection} */
    public function connections(WorldTemplateUpdate $update): array
    {
        $target = $update->targetTemplate()->firstOrFail();
        $source = $update->sourceTemplate()->firstOrFail();

        if (! $target->is_production || ! $target->is_active || $source->is_production || ! $source->is_active || $target->base_template_id !== $source->id) {
            throw new RuntimeException('Zmieniono konfigurację template’ów. Przygotuj nowe porównanie.');
        }

        foreach ([$source, $target] as $template) {
            if (! $this->resolver->registerTemplateConnection($template)) {
                throw new RuntimeException('Nie można połączyć się z bazą template’u.');
            }
        }

        $sourceConnection = DB::connection($source->connection_name);
        $targetConnection = DB::connection($target->connection_name);
        $sourceIdentity = array_intersect_key($sourceConnection->getConfig(), array_flip(['driver', 'host', 'port', 'database', 'unix_socket']));
        $targetIdentity = array_intersect_key($targetConnection->getConfig(), array_flip(['driver', 'host', 'port', 'database', 'unix_socket']));

        if ($sourceIdentity === $targetIdentity) {
            throw new RuntimeException('Źródło i produkcja wskazują tę samą bazę.');
        }

        return [$sourceConnection, $targetConnection];
    }

    /** @return array<string, array{keys: array, columns: array}> */
    public function schema(Connection $connection): array
    {
        $schema = $connection->getSchemaBuilder();
        $tables = collect($schema->getTables())->keyBy('name');
        $manifest = [];

        foreach (config('world_templates.update_tables', []) as $table) {
            if (! $tables->has($table)) {
                throw new RuntimeException("Brakuje tabeli [{$table}]. Ujednolić migracje template’ów przed aktualizacją.");
            }

            if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true) && strtolower($tables[$table]['engine'] ?? '') !== 'innodb') {
                throw new RuntimeException("Tabela [{$table}] musi korzystać z InnoDB.");
            }

            $keys = collect($schema->getIndexes($table))->firstWhere('primary', true)['columns'] ?? [];
            if ($keys === []) {
                throw new RuntimeException("Tabela [{$table}] nie ma klucza głównego.");
            }

            $columns = collect($schema->getColumns($table))
                ->map(fn (array $column): array => $this->columnDefinition($connection, $column))
                ->sortBy('name')->values()->all();
            $manifest[$table] = ['keys' => $keys, 'columns' => $columns];
        }

        if ($manifest === []) {
            throw new RuntimeException('Nie skonfigurowano tabel do aktualizacji.');
        }

        return $manifest;
    }

    private function columnDefinition(Connection $connection, array $column): array
    {
        $definition = array_intersect_key($column, array_flip(['name', 'type', 'nullable', 'generation']));
        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true) && ! str_contains(strtolower($definition['type']), 'zerofill')) {
            $definition['type'] = preg_replace('/^(tinyint|smallint|mediumint|int|bigint)\\(\\d+\\)/i', '$1', $definition['type']);
        }

        return $definition;
    }

    /** @return list<array{table: string, column: string, source: mixed, target: mixed}> */
    private function schemaDifferences(array $source, array $target): array
    {
        $differences = [];
        foreach ($source as $table => $definition) {
            if ($definition['keys'] !== $target[$table]['keys']) {
                $differences[] = ['table' => $table, 'column' => 'PRIMARY KEY', 'source' => $definition['keys'], 'target' => $target[$table]['keys']];
            }
            $sourceColumns = array_column($definition['columns'], null, 'name');
            $targetColumns = array_column($target[$table]['columns'], null, 'name');
            foreach (array_unique([...array_keys($sourceColumns), ...array_keys($targetColumns)]) as $name) {
                $before = $sourceColumns[$name] ?? null;
                $after = $targetColumns[$name] ?? null;
                if ($before !== $after) {
                    $differences[] = ['table' => $table, 'column' => $name, 'source' => $before, 'target' => $after];
                }
            }
        }

        return $differences;
    }

    /** @return Generator<int, array<string, mixed>> */
    public function rows(Connection $connection, string $table, array $keys, bool $lock = false): Generator
    {
        $query = $connection->table($table);
        foreach ($keys as $key) {
            $query->orderBy($key);
        }
        if ($lock) {
            $query->lockForUpdate();
        }
        foreach ($query->lazy(50) as $row) {
            $row = (array) $row;
            ksort($row);
            yield $row;
        }
    }

    public function encode(array $row): string
    {
        return json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function digest(Connection $connection, string $table, array $keys, bool $lock = false): string
    {
        $hash = hash_init('sha256');
        foreach ($this->rows($connection, $table, $keys, $lock) as $row) {
            hash_update($hash, $this->encode($row)."\n");
        }

        return hash_final($hash);
    }

    public function prepare(WorldTemplateUpdate $update): void
    {
        [$source, $target] = $this->connections($update);
        $schema = $this->schema($source);
        $differences = $this->schemaDifferences($schema, $this->schema($target));
        if ($differences !== []) {
            $update->update(['manifest' => ['schema_differences' => $differences]]);
            $message = $update->migration_steps !== null
                ? 'Sprawdzono migracje, ale definicje tabel nadal się różnią. Szczegóły poniżej. Ponowne uruchomienie tych samych migracji nie naprawi tych różnic.'
                : 'Schematy baz różnią się. Sprawdź migracje oraz szczegóły różnic poniżej.';
            throw new RuntimeException($message);
        }

        $summary = [];
        $this->beginSnapshot($source);
        try {
            $this->beginSnapshot($target);
            try {
                foreach ($schema as $table => &$metadata) {
                    $label = config('world_templates.update_table_labels.'.$table, $table);
                    $summary[$table] = $this->compareTable($update, $source, $target, $table, $metadata);
                    $update->update([
                        'progress' => (int) floor(count($summary) / count($schema) * 95),
                        'message' => "Porównano: {$label}",
                    ]);
                }
                unset($metadata);
            } finally {
                $target->rollBack();
            }
        } finally {
            $source->rollBack();
        }

        $update->update([
            'status' => 'ready', 'progress' => 100,
            'message' => 'Porównanie gotowe. Przejrzyj zmiany przed zatwierdzeniem.',
            'manifest' => $schema, 'summary' => $summary, 'expires_at' => now()->addDay(),
        ]);
    }

    private function beginSnapshot(Connection $connection): void
    {
        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }
        $connection->beginTransaction();
    }

    private function compareTable(WorldTemplateUpdate $update, Connection $source, Connection $target, string $table, array &$metadata): array
    {
        $label = config('world_templates.update_table_labels.'.$table, $table);
        $before = $this->rows($target, $table, $metadata['keys']);
        $after = $this->rows($source, $table, $metadata['keys']);
        $before->rewind();
        $after->rewind();
        $beforeHash = hash_init('sha256');
        $afterHash = hash_init('sha256');
        $counts = ['added' => 0, 'updated' => 0, 'deleted' => 0, 'unchanged' => 0];
        $buffer = [];

        while ($before->valid() || $after->valid()) {
            $old = $before->valid() ? $before->current() : null;
            $new = $after->valid() ? $after->current() : null;
            $comparison = $old === null ? 1 : ($new === null ? -1 : $this->compareKeys($old, $new, $metadata['keys']));
            $old = $comparison > 0 ? null : $old;
            $new = $comparison < 0 ? null : $new;
            $oldJson = $old === null ? null : $this->encode($old);
            $newJson = $new === null ? null : $this->encode($new);
            $type = $old === null ? 'added' : ($new === null ? 'deleted' : ($oldJson === $newJson ? 'unchanged' : 'updated'));
            $counts[$type]++;
            $changed = [];
            foreach (array_keys($new ?? $old) as $column) {
                if (($old[$column] ?? null) !== ($new[$column] ?? null)) {
                    $changed[] = $column;
                }
            }
            $row = $new ?? $old;
            $identity = implode(', ', array_map(fn (string $key): string => $key.'='.$row[$key], $metadata['keys']));
            $rowLabel = $row['name'] ?? $row['label'] ?? $row['title'] ?? $identity;
            $buffer[] = [
                'world_template_update_id' => $update->id, 'table_name' => $table,
                'row_label' => mb_substr((string) $rowLabel.' ('.$identity.')', 0, 255),
                'change_type' => $type === 'unchanged' ? null : $type,
                'changed_columns' => $this->encode($changed),
                'before_data' => $type === 'unchanged' ? null : $oldJson,
                'after_data' => $newJson,
            ];
            if (count($buffer) >= 25) {
                $update->snapshotRows()->insert($buffer);
                $buffer = [];
                $update->update(['message' => 'Porównuję '.$label.': '.array_sum($counts).' rekordów']);
            }
            if ($old !== null) {
                hash_update($beforeHash, $oldJson."\n");
                $before->next();
            }
            if ($new !== null) {
                hash_update($afterHash, $newJson."\n");
                $after->next();
            }
        }
        if ($buffer !== []) {
            $update->snapshotRows()->insert($buffer);
        }
        $metadata['before_hash'] = hash_final($beforeHash);
        $metadata['after_hash'] = hash_final($afterHash);

        return $counts;
    }

    private function compareKeys(array $before, array $after, array $keys): int
    {
        foreach ($keys as $key) {
            $comparison = $before[$key] <=> $after[$key];
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    public function apply(WorldTemplateUpdate $update): void
    {
        [, $target] = $this->connections($update);
        $manifest = $update->manifest;
        $expectedSchema = array_map(fn (array $table): array => array_intersect_key($table, array_flip(['keys', 'columns'])), $manifest);
        if ($this->schema($target) !== $expectedSchema) {
            throw new RuntimeException('Zmieniono schemat produkcji. Przygotuj nowe porównanie.');
        }

        $schema = $target->getSchemaBuilder();
        $schema->disableForeignKeyConstraints();
        try {
            $target->transaction(function () use ($update, $target, $manifest): void {
                foreach ($manifest as $table => $metadata) {
                    $label = config('world_templates.update_table_labels.'.$table, $table);
                    $update->update(['message' => "Sprawdzam stan produkcji: {$label}"]);
                    if ($this->digest($target, $table, $metadata['keys'], true) !== $metadata['before_hash']) {
                        throw new RuntimeException('Produkcja zmieniła się od porównania. Przygotuj nowy diff.');
                    }
                }
                foreach (array_keys($manifest) as $table) {
                    $target->table($table)->delete();
                }
                $copiedRows = 0;
                $totalRows = max(1, $update->snapshotRows()->whereNotNull('after_data')->count());
                foreach ($manifest as $table => $metadata) {
                    $label = config('world_templates.update_table_labels.'.$table, $table);
                    $update->snapshotRows()->where('table_name', $table)->whereNotNull('after_data')
                        ->orderBy('id')->chunkById(25, function ($rows) use ($target, $table, $label, $update, &$copiedRows, $totalRows): void {
                            $target->table($table)->insert($rows->map(fn ($row): array => json_decode($row->after_data, true, flags: JSON_THROW_ON_ERROR))->all());
                            $copiedRows += $rows->count();
                            $update->update(['progress' => 5 + (int) floor($copiedRows / $totalRows * 85), 'message' => "Kopiuję {$label}: {$copiedRows} / {$totalRows} rekordów"]);
                        });
                    if ($this->digest($target, $table, $metadata['keys']) !== $metadata['after_hash']) {
                        throw new RuntimeException("Weryfikacja kopii tabeli [{$table}] nie powiodła się. Wycofuję aktualizację.");
                    }
                    $update->update(['message' => "Skopiowano: {$label}"]);
                }
                $update->update(['progress' => 95, 'message' => 'Sprawdzam spójność powiązań przed zatwierdzeniem transakcji.']);
                $this->assertForeignKeys($target, array_keys($manifest));
            });
        } finally {
            $schema->enableForeignKeyConstraints();
        }

        $update->update(['status' => 'completed', 'progress' => 100, 'message' => 'Aktualizacja zakończona. Wszystkie tabele zapisano w jednej transakcji.', 'completed_at' => now()]);
    }

    private function assertForeignKeys(Connection $connection, array $copiedTables): void
    {
        $schema = $connection->getSchemaBuilder();
        $relations = [];
        foreach ($schema->getTableListing() as $table) {
            foreach ($schema->getForeignKeys($table) as $foreignKey) {
                if (! in_array($table, $copiedTables, true) && ! in_array($foreignKey['foreign_table'], $copiedTables, true)) {
                    continue;
                }
                $parent = empty($foreignKey['foreign_schema']) ? $foreignKey['foreign_table'] : $foreignKey['foreign_schema'].'.'.$foreignKey['foreign_table'];
                $relations[] = ['table' => $table, ...$foreignKey, 'parent' => $parent];
            }
        }
        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $incoming = $connection->table('information_schema.key_column_usage')
                ->where('referenced_table_schema', $connection->getDatabaseName())
                ->where('table_schema', '!=', $connection->getDatabaseName())
                ->whereIn('referenced_table_name', $copiedTables)
                ->orderBy('ordinal_position')
                ->get(['table_schema as child_schema', 'table_name as child_table', 'constraint_name as constraint_key', 'column_name as child_column', 'referenced_table_name as parent_table', 'referenced_column_name as parent_column'])
                ->groupBy(fn ($key): string => $key->child_schema.'.'.$key->child_table.'.'.$key->constraint_key);
            foreach ($incoming as $keys) {
                $first = $keys->first();
                $relations[] = [
                    'table' => $first->child_schema.'.'.$first->child_table,
                    'parent' => $connection->getDatabaseName().'.'.$first->parent_table,
                    'foreign_table' => $first->parent_table,
                    'columns' => $keys->pluck('child_column')->all(),
                    'foreign_columns' => $keys->pluck('parent_column')->all(),
                ];
            }
        }
        foreach ($relations as $foreignKey) {
            $table = $foreignKey['table'];
            $query = $connection->table($table.' as child')
                ->leftJoin($foreignKey['parent'].' as parent', function ($join) use ($foreignKey): void {
                    foreach ($foreignKey['columns'] as $index => $column) {
                        $join->on('child.'.$column, '=', 'parent.'.$foreignKey['foreign_columns'][$index]);
                    }
                });
            foreach ($foreignKey['columns'] as $column) {
                $query->whereNotNull('child.'.$column);
            }
            if ($query->whereNull('parent.'.$foreignKey['foreign_columns'][0])->exists()) {
                throw new RuntimeException("Aktualizacja narusza powiązania tabel [{$table}] → [{$foreignKey['foreign_table']}]. Wycofano zapis.");
            }
        }
    }
}
