<?php

namespace Tests\Feature;

use App\Services\WorldTemplateSnapshotService;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorldTemplateSchemaTest extends TestCase
{
    public static function columnTypes(): array
    {
        return [
            'legacy integer display width' => ['int(11)', 'int'],
            'unsigned bigint display width' => ['bigint(20) unsigned', 'bigint unsigned'],
            'boolean display width' => ['tinyint(1)', 'tinyint'],
            'zerofill preserves formatting' => ['int(11) unsigned zerofill', 'int(11) unsigned zerofill'],
            'decimal precision preserved' => ['decimal(10,2)', 'decimal(10,2)'],
            'enum values preserved' => ["enum('int(11)','INT(3)')", "enum('int(11)','INT(3)')"],
        ];
    }

    #[DataProvider('columnTypes')]
    public function test_mysql_schema_ignores_only_integer_display_width(string $type, string $expected): void
    {
        config()->set('world_templates.update_tables', ['items']);
        $connection = \Mockery::mock(Connection::class);
        $builder = \Mockery::mock(Builder::class);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($builder);
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $builder->shouldReceive('getTables')->once()->andReturn([['name' => 'items', 'engine' => 'InnoDB']]);
        $builder->shouldReceive('getIndexes')->with('items')->once()->andReturn([['primary' => true, 'columns' => ['id']]]);
        $builder->shouldReceive('getColumns')->with('items')->once()->andReturn([
            ['name' => 'id', 'type' => $type, 'nullable' => false, 'generation' => null],
        ]);
        $schema = app(WorldTemplateSnapshotService::class)->schema($connection);
        $this->assertSame($expected, $schema['items']['columns'][0]['type']);
        $this->assertFalse($schema['items']['columns'][0]['nullable']);
    }
}
