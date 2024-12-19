<?php

namespace App\Console\Commands;

use App\Models\DynamicModel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class CompareSchemas extends Command
{
    protected $signature = 'schema:compare {world} {--fix}';
    protected $description = 'Compare and optionally fix the schema for models extending DynamicModel';

    public function handle()
    {
        $world = $this->argument('world');
        $fix = $this->option('fix');

        $this->setDynamicConnection($world);

        $models = $this->getDynamicModels();
        foreach ($models as $model) {
            $this->info("Checking table for model: " . get_class($model));

            $localSchema = $this->getModelSchema($model);
            $remoteSchema = $this->getRemoteSchema($model->getTable());

            if ($remoteSchema === null) {
                $this->warn("Table '{$model->getTable()}' does not exist.");
                if ($fix) {
                    $this->createTable($model);
                }
                continue;
            }

            $differences = $this->compareSchemas($localSchema, $remoteSchema);

            if (empty($differences)) {
                $this->info("No differences for table '{$model->getTable()}'.");
            } else {
                $this->warn("Differences found for table '{$model->getTable()}':");
                foreach ($differences as $diff) {
                    $this->line($diff);
                }

                if ($fix) {
                    $this->info("Fixing differences...");
                    $this->fixSchema($model->getTable(), $differences);
                }
            }
        }
    }

    private function setDynamicConnection(string $connection): void
    {
        config(['database.default' => $connection]);
        DB::purge($connection);
    }

    private function getDynamicModels(): array
    {
        $models = [];
        $path = app_path('Models');
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $namespace = "App\\Models\\";
            $class = $namespace . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (class_exists($class)) {
                $reflection = new ReflectionClass($class);
                if ($reflection->isSubclassOf(DynamicModel::class) && !$reflection->isAbstract()) {
                    $models[] = new $class();
                }
            }
        }
        return $models;
    }

    private function getModelSchema(Model $model): array
    {
        $columns = Schema::getColumnListing($model->getTable());
        $schema = [];

        foreach ($columns as $column) {
            $schema[$column] = Schema::getColumnType($model->getTable(), $column);
        }

        if (empty($schema)) {
            $this->warn("Model '{$model->getTable()}' does not define any columns.");
        }

        return $schema;
    }

    private function getRemoteSchema(string $table): ?array
    {
        try {
            $schema = [];
            $columns = DB::select("DESCRIBE {$table}");
            foreach ($columns as $column) {
                $schema[$column->Field] = $column->Type;
            }
            return $schema;
        } catch (\Exception $e) {
            return null; // Tabela nie istnieje
        }
    }

    private function compareSchemas(array $local, array $remote): array
    {
        $differences = [];

        foreach ($local as $column => $type) {
            if (!isset($remote[$column])) {
                $differences[] = "Missing column: {$column}";
            } elseif ($remote[$column] !== $type) {
                $differences[] = "Type mismatch for column '{$column}': '{$remote[$column]}' vs '{$type}'";
            }
        }

        foreach ($remote as $column => $type) {
            if (!isset($local[$column])) {
                $differences[] = "Extra column: {$column}";
            }
        }

        return $differences;
    }

    private function createTable(Model $model): void
    {
        $table = $model->getTable();
        $schema = $this->getModelSchema($model);

        if (empty($schema)) {
            $this->warn("Skipping table '{$table}' because no columns are defined in the model schema.");
            return;
        }

        $columnsSQL = [];
        foreach ($schema as $column => $type) {
            $columnsSQL[] = "`{$column}` " . $this->convertLaravelTypeToSQL($type);
        }
        $columnsSQL[] = "PRIMARY KEY (`id`)";

        $createSQL = "CREATE TABLE `{$table}` (" . implode(', ', $columnsSQL) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            DB::statement($createSQL);
            $this->info("Table '{$table}' created successfully.");
        } catch (\Exception $e) {
            $this->error("Failed to create table '{$table}': " . $e->getMessage());
        }
    }

    private function fixSchema(string $table, array $differences): void
    {
        foreach ($differences as $diff) {
            if (preg_match("/Missing column: (.+)/", $diff, $matches)) {
                $column = $matches[1];
                try {
                    DB::statement("ALTER TABLE {$table} ADD `{$column}` VARCHAR(255) NULL");
                    $this->info("Added missing column: {$column}");
                } catch (\Exception $e) {
                    $this->error("Failed to add column '{$column}' in table '{$table}': " . $e->getMessage());
                }
            }

            if (preg_match("/Type mismatch for column '(.*?)'/", $diff, $matches)) {
                $column = $matches[1];

                if ($column === 'stats') {
                    try {
                        DB::statement("UPDATE {$table} SET `{$column}` = LEFT(`{$column}`, 255) WHERE LENGTH(`{$column}`) > 255");
                        $this->info("Truncated data in column '{$column}' to fit VARCHAR(255). ");
                    } catch (\Exception $e) {
                        $this->error("Failed to truncate data in column '{$column}' in table '{$table}': " . $e->getMessage());
                        continue;
                    }
                }

                try {
                    DB::statement("ALTER TABLE {$table} MODIFY `{$column}` VARCHAR(255) NULL");
                    $this->info("Fixed type for column: {$column}");
                } catch (\Exception $e) {
                    $this->error("Failed to modify column '{$column}' in table '{$table}': " . $e->getMessage());
                }
            }
        }
    }

    private function convertLaravelTypeToSQL(string $type): string
    {
        switch ($type) {
            case 'string': return 'VARCHAR(255)';
            case 'integer': return 'INT(11)';
            case 'text': return 'TEXT';
            case 'datetime': return 'DATETIME';
            case 'boolean': return 'TINYINT(1)';
            default: return strtoupper($type);
        }
    }
}
