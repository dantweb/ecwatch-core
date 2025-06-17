<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Middleware;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Exception\EcwException;
use Dantweb\Ecommwatch\Framework\Helper\Logger;
use Exception;
use PDO;

class Migration
{
    private PDO $pdo;

    public function __construct(DatabaseConnector $databaseConnector)
    {
        $this->pdo = $databaseConnector->getDb();
    }

    public function createMigration(EcwModelInterface $model): string
    {
        $fields = $model->getFields();
        $tableName = $model->getModelName();

        $tableExists = $this->isTableExists($tableName);

        if (!$tableExists) {
            return $this->generateCreateTableSql($tableName, $fields);
        }

        try {
            return $this->generateAlterTableSql($tableName, $fields);
        } catch (EcwException $e) {
            Logger::warn($e->getMessage());
            throw new EcwException("Migration failed: " . $e->getMessage());
        }
    }

    private function generateCreateTableSql(string $tableName, array $fields): string
    {
        $columnDefinitions = [];

        foreach ($fields as $fieldName => $fieldDetails) {
            $columnSql = "`$fieldName` " . $this->mapFieldType($fieldDetails);

            // Handle primary key
            if (isset($fieldDetails['primary']) && $fieldDetails['primary']) {
                $columnSql .= " PRIMARY KEY";
            }

            // Handle auto increment
            if (isset($fieldDetails['auto_increment']) && $fieldDetails['auto_increment']) {
                $columnSql .= " AUTO_INCREMENT";
            }

            $columnDefinitions[] = $columnSql;
        }

        $sql = "CREATE TABLE `$tableName` (\n";
        $sql .= "  " . implode(",\n  ", $columnDefinitions);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        return $sql;
    }

    private function generateAlterTableSql(string $tableName, array $fields): string
    {
        // Get existing table columns
        $stmt = $this->pdo->prepare("DESCRIBE `$tableName`");
        $stmt->execute();
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        $alterStatements = [];

        // Check for new columns
        foreach ($fields as $fieldName => $fieldDetails) {
            if (!in_array($fieldName, $existingColumns)) {
                $columnSql = $this->mapFieldType($fieldDetails);
                $alterStatements[] = "ADD COLUMN `$fieldName` $columnSql";
            }
        }

        if (empty($alterStatements)) {
            Logger::warn("No changes detected for table '$tableName'");
            return '';
        }

        $sql = "ALTER TABLE `$tableName`\n";
        $sql .= implode(",\n", $alterStatements) . ";";

        return $sql;
    }

    private function mapFieldType(array $fieldDetails): string
    {
        $type = strtolower($fieldDetails['type'] ?? '');

        // Normalize type mappings
        $typeMap = [
            'int' => 'INT',
            'varchar' => 'VARCHAR',
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'timestamp' => 'TIMESTAMP',
            'datetime' => 'DATETIME',
            'boolean' => 'BOOLEAN',
            'float' => 'FLOAT',
            'double' => 'DOUBLE',
        ];

        // Extract base type and optional length
        preg_match('/(\w+)(?:\((\d+)\))?/', $type, $matches);
        $baseType = $matches[1] ?? $type;
        $length = $matches[2] ?? null;

        // Map base type
        $sqlType = $typeMap[$baseType] ?? strtoupper($baseType);

        // Add length if specified
        if ($length) {
            $sqlType .= "($length)";
        }

        // Handle primary key and nullability
        $isPrimaryKey = isset($fieldDetails['primary']) && $fieldDetails['primary'];

        // Primary key must be NOT NULL
        if ($isPrimaryKey) {
            $sqlType .= " NOT NULL";
        } else {
            // For non-primary key, respect nullable flag
            $sqlType .= isset($fieldDetails['nullable']) && !$fieldDetails['nullable']
                ? " NOT NULL"
                : " NULL";
        }

        // Add auto increment for primary key if specified
        if ($isPrimaryKey && isset($fieldDetails['auto_increment']) && $fieldDetails['auto_increment']) {
            $sqlType .= " AUTO_INCREMENT";
        }

        // Add default value if specified (and not a primary key)
        if (!$isPrimaryKey && isset($fieldDetails['default'])) {
            $sqlType .= " DEFAULT '{$fieldDetails['default']}'";
        }

        return $sqlType;
    }

    public function run(string $migrationSql): bool
    {
        try {
            // Execute migration SQL without explicit transaction
            $executionResult = $this->pdo->exec($migrationSql);

            if ($executionResult === false) {
                throw new EcwException("Migration SQL execution failed");
            }

            return true;
        } catch (Exception $e) {
            // Log and rethrow
            error_log("Migration execution error: " . $e->getMessage());
            throw new Exception("Migration failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function isTableExists(string $tableName): bool
    {
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute([':tableName' => $tableName]);
        return $stmt->fetch() !== false;
    }
}
