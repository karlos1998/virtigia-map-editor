<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private const string DEFAULT_TEST_DATABASE = 'testing';

    private static ?string $preparedDatabase = null;

    protected function setUp(): void
    {
        $this->prepareSqliteTestingDatabase();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->restoreDefaultTestingDatabase();
    }

    private function prepareSqliteTestingDatabase(): void
    {
        if ((getenv('DB_CONNECTION') ?: 'sqlite') !== 'sqlite') {
            return;
        }

        $database = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? 'testing');

        if ($database === ':memory:') {
            $this->resetRefreshDatabaseStateWhenDatabaseChanges($database);

            return;
        }

        if (str_starts_with($database, DIRECTORY_SEPARATOR)) {
            $databasePath = $database;
        } else {
            $databaseName = str_ends_with($database, '.sqlite') ? $database : "{$database}.sqlite";
            $databasePath = dirname(__DIR__)."/database/{$databaseName}";
        }

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }

        putenv("DB_DATABASE={$databasePath}");
        $_ENV['DB_DATABASE'] = $databasePath;
        $_SERVER['DB_DATABASE'] = $databasePath;

        $this->resetRefreshDatabaseStateWhenDatabaseChanges($databasePath);
    }

    private function resetRefreshDatabaseStateWhenDatabaseChanges(string $database): void
    {
        if (self::$preparedDatabase === $database) {
            return;
        }

        RefreshDatabaseState::$migrated = false;
        self::$preparedDatabase = $database;
    }

    private function restoreDefaultTestingDatabase(): void
    {
        putenv('DB_DATABASE='.self::DEFAULT_TEST_DATABASE);
        $_ENV['DB_DATABASE'] = self::DEFAULT_TEST_DATABASE;
        $_SERVER['DB_DATABASE'] = self::DEFAULT_TEST_DATABASE;
    }
}
