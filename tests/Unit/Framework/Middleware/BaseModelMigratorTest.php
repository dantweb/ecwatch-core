<?php
declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Middleware;

use App\Modules\Atomizer\src\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Middleware\BaseModelMigrator;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

class BaseModelMigratorTest extends TestCase
{
    protected static string $testDir = "/../../../_data/model_migration_test/";
    protected string $modelDir = '';

    // List the tables that the migration creates.
    protected static array $tablesToCleanup = [
        'BaseOrderModel',
        'CustomerModel',
        'ShipmentModel',
    ];

    protected function setUp(): void
    {
        // The model directory should contain your YAML files:
        // BaseOrderModel.yaml, CustomerModel.yaml, ShipmentModel.yaml
        $this->modelDir = Path::join(__DIR__, self::$testDir);
    }

    /**
     * The tearDown ensures that any tables created by the migration are removed.
     */
    protected function tearDown(): void
    {
        $databaseConnector = DatabaseConnector::getInstance();
        $pdo = $databaseConnector->getDb();

        foreach (self::$tablesToCleanup as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
    }

    public function testGetMigrationPlanReturnsModels(): void
    {
        $databaseConnector = DatabaseConnector::getInstance();
        $migrationService = new Migration($databaseConnector);

        $migrator = new BaseModelMigrator($migrationService, $this->modelDir);
        $migrationPlan = $migrator->getEcwModels();

        $this->assertIsArray($migrationPlan, 'Migration plan should be an array.');
        $this->assertNotEmpty($migrationPlan, 'Migration plan should not be empty.');

        foreach ($migrationPlan as $model) {
            $this->assertInstanceOf(
                EcwModelInterface::class,
                $model,
                'Each model should implement the EcwModelInterface.'
            );
        }
    }

    /**
     * Tests that after running the migration the tables exist with the correct structure.
     */
    public function testMigrationCreatesTablesWithCorrectStructure(): void
    {
        $databaseConnector = DatabaseConnector::getInstance();
        $pdo = $databaseConnector->getDb();

        $migrationService = new Migration($databaseConnector);
        $migrator = new BaseModelMigrator($migrationService, $this->modelDir);

        // Run migration to create/alter the tables based on the models.
        $migrator->migrate();

        // Define expected table structure for each model.
        $expectedTables = [
            'BaseOrderModel' => [
                'order_number' => ['type' => 'varchar(255)', 'null' => 'NO', 'key' => '', 'extra' => '', 'unique' => 'YES'],
                'order_brutto' => ['type' => 'float', 'null' => 'YES', 'key' => '', 'extra' => ''],
                'order_items_count' => ['type' => 'int', 'null' => 'YES', 'key' => '', 'extra' => ''],
                'order_payment_method_id' => ['type' => 'varchar(255)', 'null' => 'YES', 'key' => '', 'extra' => ''],
                'timestamp' => ['type' => 'timestamp', 'null' => 'YES', 'key' => '', 'extra' => '']
            ],
            'CustomerModel' => [
                'customer_id' => ['type' => 'int', 'null' => 'NO', 'key' => 'PRI', 'extra' => 'auto_increment'],
                'first_name' => ['type' => 'varchar(100)', 'null' => 'NO', 'key' => '', 'extra' => ''],
                'last_name' => ['type' => 'varchar(100)', 'null' => 'NO', 'key' => '', 'extra' => ''],
                'email' => ['type' => 'varchar(255)', 'null' => 'NO', 'key' => '', 'extra' => ''],
                'joined_date' => ['type' => 'datetime', 'null' => 'YES', 'key' => '', 'extra' => '']
            ],
            'ShipmentModel' => [
                'shipment_id' => ['type' => 'int', 'null' => 'NO', 'key' => 'PRI', 'extra' => 'auto_increment'],
                'order_number' => ['type' => 'varchar(255)', 'null' => 'NO', 'key' => '', 'extra' => ''],
                'carrier' => ['type' => 'varchar(100)', 'null' => 'YES', 'key' => '', 'extra' => ''],
                'shipped_date' => ['type' => 'datetime', 'null' => 'YES', 'key' => '', 'extra' => ''],
                'delivery_date' => ['type' => 'datetime', 'null' => 'YES', 'key' => '', 'extra' => '']
            ]
        ];

        foreach ($expectedTables as $tableName => $columnsExpectations) {
            // Verify that the table exists.
            $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
            $result = $stmt->fetch();
            $this->assertNotFalse($result, "Table '$tableName' should exist.");

            // Retrieve the columns definitions from the database.
            $stmt = $pdo->query("DESCRIBE `$tableName`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->assertNotEmpty($columns, "Table '$tableName' should have columns.");

            // Index the columns by their names.
            $dbColumns = [];
            foreach ($columns as $col) {
                $dbColumns[$col['Field']] = $col;
            }

            foreach ($columnsExpectations as $colName => $expectation) {
                $this->assertArrayHasKey($colName, $dbColumns, "Table '$tableName' must contain column '$colName'.");

                $dbCol = $dbColumns[$colName];
                // Verify that the expected type substring is in the actual type.
                $expectedType = $expectation['type'];
                $this->assertStringContainsStringIgnoringCase(
                    $expectedType,
                    $dbCol['Type'],
                    "Column '$colName' in table '$tableName' should be of type containing '$expectedType'."
                );

                // Verify nullability (DESCRIBE returns 'NO' for NOT NULL columns).
                $expectedNull = $expectation['null'];
                $this->assertEquals(
                    $expectedNull,
                    $dbCol['Null'],
                    "Column '$colName' in table '$tableName' nullability should be '$expectedNull'."
                );

                // Verify key type.
                $expectedKey = $expectation['key'];
                $this->assertEquals(
                    $expectedKey,
                    $dbCol['Key'],
                    "Column '$colName' in table '$tableName' key should be '$expectedKey'."
                );

                // Verify extra attributes like auto_increment.
                $expectedExtra = $expectation['extra'];
                if ($expectedExtra === 'auto_increment') {
                    $this->assertStringContainsStringIgnoringCase(
                        'auto_increment',
                        $dbCol['Extra'],
                        "Column '$colName' in table '$tableName' should have auto_increment set."
                    );
                }
            }
        }
    }
}