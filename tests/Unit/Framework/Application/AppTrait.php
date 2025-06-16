<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Application;

use Dantweb\Ecommwatch\Framework\Middleware\BaseModelMigrator;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use Symfony\Component\Filesystem\Path;

trait AppTrait
{
    protected static string $testDir = "/../../../_data";
    protected string $modelDir = '';

    // List the tables that the migration creates.
    protected static array $tablesToCleanup = [
        'BaseOrderModel',
        'CustomerModel',
        'ShipmentModel',
    ];
    private DatabaseConnector $databaseConnector;

    protected function setUp(): void
    {
        $this->modelDir = Path::join(__DIR__, self::$testDir, 'model_migration_test');
        $this->databaseConnector = DatabaseConnector::getInstance();

        // Run migrations for BaseOrderModel
        $migration = new Migration($this->databaseConnector);
        $migrator = new BaseModelMigrator($migration, $this->modelDir);

        // migration could take place in another test
        try {
            $migrator->migrate();
        } catch (\Exception $e) {
        }

        parent::setUp();
    }

//    protected function tearDown(): void
//    {
//        // Drop the table
//        $pdo = $this->databaseConnector->getDb();
//        $pdo->exec('DROP TABLE IF EXISTS BaseOrderModel');
//        $pdo->exec('DROP TABLE IF EXISTS ShipmentModel');
//        $pdo->exec('DROP TABLE IF EXISTS CustomerModel');
//    }
}
