<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Middleware\RepoFactory;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Exception\EcwTableNotFoundException;
use Dantweb\Ecommwatch\Framework\Middleware\Repository\AbstractRepo;
use Dantweb\Ecommwatch\Framework\Middleware\Repository\RepoInterface;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Exception;
use PDO;

class RepoFactory implements RepoFactoryInterface
{
    private DatabaseConnector $databaseConnector;

    public function __construct(DatabaseConnector $databaseConnector)
    {
        $this->databaseConnector = $databaseConnector;
    }

    public function getRepo(EcwModelInterface $ecwModel): RepoInterface
    {
        $pdo = $this->getDbConnection();

        $tableName = $ecwModel->getModelName();

        // Check if the table exists.
        $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute([':tableName' => $tableName]);
        $result = $stmt->fetchColumn();
        if ($result === false) {
            throw new EcwTableNotFoundException("The table '$tableName' does not exist in the database.");
        }

        // Retrieve the table structure.
        $stmt = $pdo->prepare("DESCRIBE `$tableName`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Assume the model provides a getFields() method returning an associative array of expected field definitions.
        // Example: ['id' => ['type' => 'int'], 'name' => ['type' => 'varchar'], ...]
        $modelFields = $ecwModel->getFields();

        // Validate that the number of fields in the model matches the columns in the table.
        if (count($columns) !== count($modelFields)) {
            throw new Exception(
                "Table structure for '$tableName' does not match the expected model structure."
            );
        }

        // Validate each field exists and matches in type.
        foreach ($columns as $column) {
            $fieldName = $column['Field'];
            if (!isset($modelFields[$fieldName])) {
                throw new Exception(
                    "Field '$fieldName' found in the table but missing in the model definition."
                );
            }

            // Perform a simple type check.
            $dbType = strtolower($column['Type']);
            $modelType = strtolower($modelFields[$fieldName]['type'] ?? '');
            if ($dbType !== $modelType) {
                throw new Exception(
                    "Type mismatch for field '$fieldName': database "
                    . "reports '$dbType' but model expects '$modelType'.");
            }
        }

        return new class(
            $ecwModel,
            $this->databaseConnector
        ) extends AbstractRepo {};
    }

    private function getDbConnection(): ?\PDO
    {
        $pdo = $this->databaseConnector->getDb();
        if (!$pdo) {
            throw new Exception("Database connection is not established.");
        }
        return $pdo;
    }
}