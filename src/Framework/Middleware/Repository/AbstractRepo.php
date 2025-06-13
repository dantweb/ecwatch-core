<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Middleware\Repository;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use PDO;

abstract class AbstractRepo implements RepoInterface
{
    public const DUPLICATES_OVERRIDE = 'duplicates_override';
    public const DUPLICATES_REPORT = 'duplicates_report';
    public const ADD_IGNORE_DUPLICATES = 'add_ignore_duplicates';

    protected string $tableName = '_default_table_name';
    protected array $allowedWritingModes = [
        self::DUPLICATES_REPORT,
        self::DUPLICATES_OVERRIDE,
        self::ADD_IGNORE_DUPLICATES
    ];
    protected string $writingMode = self::DUPLICATES_REPORT;

    public function __construct(
        protected EcwModelInterface $ecwModel,
        protected DatabaseConnector $databaseConnector
    ) {
        if ($this->ecwModel->getModelName()) {
            $this->tableName = $this->ecwModel->getModelName();
        }
    }

    public function setWritingMode(string $mode): bool
    {
        if (in_array($mode, $this->allowedWritingModes, true)) {
            $this->writingMode = $mode;
            return true;
        }
        return false;
    }

    public function getWritingMode(): string
    {
        return $this->writingMode;
    }

    public function save(EcwModelInterface $model): void
    {
        // Determine if a "duplicate" already exists based on unique or primary fields
        $existing = $this->findExisting($model);

        if ($existing) {
            switch ($this->writingMode) {
                case self::DUPLICATES_OVERRIDE:
                    // copy primary key to model to trigger update
                    $model->set($existing->getPrimaryKeyName(), $existing->get($existing->getPrimaryKeyName()));
                    $this->update($model);
                    break;

                case self::DUPLICATES_REPORT:
                    // skip any DB write
                    break;

                case self::ADD_IGNORE_DUPLICATES:
                    // ensure primary key not set, force insert
                    $model->set($model->getPrimaryKeyName(), null);
                    $this->insert($model);
                    break;
            }
        } else {
            // no duplicate: always insert
            $this->insert($model);
        }
    }

    protected function findExisting(EcwModelInterface $model): ?EcwModelInterface
    {
        $data = $model->toArray();
        $fields = $this->ecwModel->getFields();
        $conditions = [];

        foreach ($fields as $name => $meta) {
            if (!empty($meta['primary']) || !empty($meta['unique'])) {
                if (isset($data[$name])) {
                    $conditions[$name] = $data[$name];
                }
            }
        }
        if (empty($conditions)) {
            return null;
        }
        $found = $this->findBy($conditions, 1);
        return $found[0] ?? null;
    }

    /**
     * Performs an insert of the model data into the DB and back-fills the primary key value.
     */
    public function insert(EcwModelInterface $model): void
    {
        $data = $model->toArray();
        $pk = $model->getPrimaryKeyName();

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $this->tableName,
            implode(', ', array_map(fn($c) => "`$c`", $columns)),
            implode(', ', $placeholders)
        );

        $stmt = $this->databaseConnector->getDb()->prepare($sql);
        foreach ($data as $c => $v) {
            $stmt->bindValue(':' . $c, $v);
        }
        $stmt->execute();

        $insertId = $this->databaseConnector->getDb()->lastInsertId();
        if ($insertId) {
            $model->set($pk, $insertId);
        }
    }

    /**
     * Updates an existing row. Model must have primary key set.
     */
    public function update(EcwModelInterface $model): void
    {
        $data = $model->toArray();
        $pk = $model->getPrimaryKeyName();
        if (empty($data[$pk])) {
            throw new \RuntimeException('Missing primary key for update.');
        }
        $id = $data[$pk];
        unset($data[$pk]);

        $sets = array_map(fn($c) => "`$c` = :$c", array_keys($data));
        $sql  = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = :pk',
            $this->tableName,
            implode(', ', $sets),
            $pk
        );
        $stmt = $this->databaseConnector->getDb()->prepare($sql);
        foreach ($data as $c => $v) {
            $stmt->bindValue(':' . $c, $v);
        }
        $stmt->bindValue(':pk', $id);
        $stmt->execute();
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getEcwModel(): EcwModelInterface
    {
        return $this->ecwModel;
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM `{$this->tableName}`";
        $stmt = $this->databaseConnector->getDb()->prepare($sql);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert results to model instances
        return array_map(function($row) {
            $model = clone $this->ecwModel;
            foreach ($row as $key => $value) {
                $model->set($key, $value);
            }
            return $model;
        }, $results);
    }

    public function findOne(int $id): ?EcwModelInterface
    {
        $sql = "SELECT * FROM `{$this->tableName}` WHERE id = :id LIMIT 1";
        $stmt = $this->databaseConnector->getDb()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Create a new instance of the model and populate it with data
        $model = clone $this->ecwModel;
        foreach ($row as $key => $value) {
            $model->set($key, $value);
        }

        return $model;

    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM `{$this->tableName}` WHERE id = :id";
        $stmt = $this->databaseConnector->getDb()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM `{$this->tableName}`";
        $stmt = $this->databaseConnector->getDb()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    public function getEcwModelClassName(): string
    {
        return get_class($this->ecwModel);
    }

    /**
     * Returns the first record that matches the condition.
     */
    public function where(string $field, string $operator, mixed $value): ?EcwModelInterface
    {
        // For simplicity, only one condition is supported in this example.
        $sql = "SELECT * FROM `{$this->tableName}` WHERE `$field` $operator :value LIMIT 1";
        $stmt = $this->databaseConnector->getDb()->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Create a new instance of the model and populate it with data
        $model = clone $this->ecwModel;
        foreach ($row as $key => $value) {
            $model->set($key, $value);
        }

        return $model;
    }

    public function findById(int $id): ?EcwModelInterface
    {
        return $this->findOne($id);
    }

    public function findBy(array $conditions, ?int $limit = null, ?int $offset = null): array
    {
        $whereClauses = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // Support for operators like >, <, >=, <=, !=
                if (count($value) === 2 && in_array($value[0], ['>', '<', '>=', '<=', '!=', 'LIKE'])) {
                    $whereClauses[] = "`$field` {$value[0]} :$field";
                    $params[$field] = $value[1];
                }
            } else {
                $whereClauses[] = "`$field` = :$field";
                $params[$field] = $value;
            }
        }

        $whereString = implode(' AND ', $whereClauses);
        $limitClause = $limit !== null ? " LIMIT $limit" : '';
        $offsetClause = $offset !== null ? " OFFSET $offset" : '';

        $sql = "SELECT * FROM `{$this->tableName}` WHERE $whereString $limitClause $offsetClause";

        $stmt = $this->databaseConnector->getDb()->prepare($sql);

        foreach ($params as $field => $value) {
            $stmt->bindValue(":$field", $value);
        }

        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert results to model instances
        return array_map(function($row) {
            $model = clone $this->ecwModel;
            foreach ($row as $key => $value) {
                $model->set($key, $value);
            }
            return $model;
        }, $results);
    }

    /**
     * Finds records within a specified range based on a numeric field.
     *
     * @param int $start The start range value.
     * @param int $end The end range value.
     * @param string $field The field to filter the range on (default is "id").
     * @return EcwModelInterface[] An array of model instances within the range.
     */
    public function findRange(int $start, int $end): array
    {
        $startDatetime = (new \DateTime())->setTimestamp($start)->format('Y-m-d H:i:s');
        $endDatetime = (new \DateTime())->setTimestamp($end)->format('Y-m-d H:i:s');

        $sql = "SELECT * FROM `{$this->tableName}` WHERE `timestamp` BETWEEN :start AND :end";
        $stmt = $this->databaseConnector->getDb()->prepare($sql);
        $stmt->bindValue(':start', $startDatetime);
        $stmt->bindValue(':end', $endDatetime);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert results to model instances
        return array_map(function ($row) {
            $model = clone $this->ecwModel;
            foreach ($row as $key => $value) {
                $model->set($key, $value);
            }
            return $model;
        }, $results);
    }
}