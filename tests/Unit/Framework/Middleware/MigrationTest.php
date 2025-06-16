<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Middleware;

use App\Modules\Atomizer\src\EcwModel\AbstractEcwModel;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use PDO;

class MigrationTest extends \PHPUnit\Framework\TestCase
{
    private PDO $pdo;
    private DatabaseConnector $databaseConnector;

    protected function setUp(): void
    {
        // Setup database connection
        $this->databaseConnector = DatabaseConnector::getInstance();
        $this->pdo = $this->databaseConnector->getDb();
    }

    /**
     * Create a mock model for testing
     */
    private function createTestModel(string $tableName, array $fields): AbstractEcwModel
    {
        return new class ($tableName, $fields) extends AbstractEcwModel {
            // Any additional method overrides if needed
        };
    }

    public function testMigrationForNewTable()
    {
        // 1. Create a model for a non-existing table
        $model = $this->createTestModel('test_table', [
            'id' => ['type' => 'int', 'primary' => true, 'auto_increment' => true],
            'name' => ['type' => 'varchar(255)'],
            'email' => ['type' => 'varchar(100)'],
            'created_at' => ['type' => 'timestamp']
        ]);

        // 2. Prove no table exists
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute([':tableName' => $model->getModelName()]);
        $this->assertFalse($stmt->fetch(), "Table should not exist before migration");

        // 3. Create migration SQL
        $migration = new Migration($this->databaseConnector);
        $migrationSql = $migration->createMigration($model);

        if ($migrationSql === null) {
            $this->fail("Migration SQL should not be null");
        }

        // 4. Validate migration SQL
        $this->assertStringContainsString('CREATE TABLE', $migrationSql);
        $this->assertStringContainsString($model->getModelName(), $migrationSql);
        foreach (array_keys($model->getFields()) as $fieldName) {
            $this->assertStringContainsString($fieldName, $migrationSql);
        }

        // 5. Run migration
        $migration->run($migrationSql);

        // 6. Prove table exists with correct structure
        $stmt = $this->pdo->prepare("DESCRIBE `{$model->getModelName()}`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Validate column count
        $this->assertCount(count($model->getFields()), $columns);

        // Validate column names and types
        foreach ($columns as $column) {
            $fieldName = $column['Field'];
            $this->assertArrayHasKey($fieldName, $model->getFields(), "Field $fieldName should exist in model");

            // Basic type validation (simplified)
            $expectedType = $model->getFields()[$fieldName]['type'];
            $this->assertStringContainsString(
                strtolower($expectedType),
                strtolower($column['Type'])
            );
        }
    }

    public function testMigrationForExistingTableWithSchemaChange()
    {
        // 1. Create initial model and migrate
        $initialModel = $this->createTestModel('test_table_v2', [
            'id' => ['type' => 'int', 'primary' => true, 'auto_increment' => true],
            'name' => ['type' => 'varchar(255)'],
            'email' => ['type' => 'varchar(100)']
        ]);

        $migration = new Migration($this->databaseConnector);
        $initialMigrationSql = $migration->createMigration($initialModel);
        $migration->run($initialMigrationSql);

        // 2. Create new model with an additional field
        $updatedModel = $this->createTestModel('test_table_v2', [
            'id' => ['type' => 'int', 'primary' => true, 'auto_increment' => true],
            'name' => ['type' => 'varchar(255)'],
            'email' => ['type' => 'varchar(100)'],
            'phone' => ['type' => 'varchar(20)'] // New field
        ]);

        // 3. Create migration SQL for updated model
        $migrationSql = $migration->createMigration($updatedModel);

        // 4. Validate migration SQL
        $this->assertStringContainsString('ALTER TABLE', $migrationSql);
        $this->assertStringContainsString('ADD COLUMN', $migrationSql);
        $this->assertStringContainsString('phone', $migrationSql);

        // 5. Run migration
        $migration->run($migrationSql);

        // 6. Prove table structure updated
        $stmt = $this->pdo->prepare("DESCRIBE `{$updatedModel->getModelName()}`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Validate column count
        $this->assertCount(count($updatedModel->getFields()), $columns);

        // Validate new column exists
        $phoneColumn = array_filter($columns, fn($col) => $col['Field'] === 'phone');
        $this->assertNotEmpty($phoneColumn, "Phone column should exist");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->pdo->exec("DROP TABLE IF EXISTS test_table");
        $this->pdo->exec("DROP TABLE IF EXISTS test_table_v2");
    }
}
