<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Models\Domain;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\RepoFactory\RepoFactory;
use Dantweb\Ecommwatch\Framework\Middleware\Repository\RepoInterface;
use Dantweb\Ecommwatch\Framework\Models\AbstractModel\AbstractModel;

abstract class AbstractDomainModel extends AbstractModel implements DomainModelInterface
{
    protected RepoInterface $repo;

    public function __construct(string $name = '', array $fields = [])
    {
        parent::__construct($name, $fields);
        // Create repository using the factory with the current model instance.
        $this->repo = (new RepoFactory(DatabaseConnector::getInstance()))->getRepo($this);
    }

    /**
     * Factory: wrap an EcwModel in a DomainModel (with data & fields copied).
     */
    public static function fromEcwModel(EcwModelInterface $ecwModel): AbstractDomainModel
    {
        // Create an anonymous subclass with same name & fields
        $domain = new class ($ecwModel->getModelName(), $ecwModel->getFields()) extends AbstractDomainModel {
            // no overrides needed
        };

        // Copy over any existing data
        foreach ($ecwModel->toArray() as $field => $value) {
            $domain->set($field, $value);
        }

        return $domain;
    }

    public function getRepo(): RepoInterface
    {
        return $this->repo;
    }

    public function hasRepo(): bool
    {
        return !empty($this->repo) && $this->repo instanceof RepoInterface;
    }

    /**
     * Helper method to extract values for a given field using the repository data
     * between $start and $end.
     *
     * @param string $field
     * @param int    $start
     * @param int    $end
     * @return float[]
     */
    protected function getFieldValues(string $field, int $start, int $end): array
    {
        // Retrieve data from the repository. It is assumed that the repository provides
        // a `findRange($start, $end)` method which returns the records within this range.
        $records = $this->repo->findRange($start, $end);

        $values = [];
        foreach ($records as $record) {
            if (is_array($record) && isset($record[$field])) {
                $values[] = (float)$record[$field];
            } elseif (is_object($record) && isset($record->{$field})) {
                $values[] = (float)$record->{$field};
            }
        }
        return $values;
    }

    public function total(string $field, int $start, int $end): float
    {
        $values = $this->getFieldValues($field, $start, $end);
        if (empty($values)) {
            return 0.0;
        }

        $value = 0.0;
        foreach ($values as $value) {
            $value += $value;
        }

        return $value;
    }

    public function movavg(string $field, int $start, int $end, string $resolution): float
    {
        // Calculate a simple moving average.
        $values = $this->getFieldValues($field, $start, $end);
        $count  = count($values);
        return $count > 0 ? array_sum($values) / $count : 0.0;
    }

    public function last(string $field, int $start, int $end): float
    {
        $values = $this->getFieldValues($field, $start, $end);
        return !empty($values) ? (float)end($values) : 0.0;
    }

    public function first(string $field, int $start, int $end): float
    {
        $values = $this->getFieldValues($field, $start, $end);
        return !empty($values) ? (float)$values[0] : 0.0;
    }

    public function weightAvg(string $field, int $start, int $end, string $resolution): float
    {
        // For now, calculate a simple average, as if all weights are equal.
        $values = $this->getFieldValues($field, $start, $end);
        $count  = count($values);
        return $count > 0 ? array_sum($values) / $count : 0.0;
    }

    public function disp(string $field, int $start, int $end, string $resolution): float
    {
        // Displacement as the difference between the last and first values.
        $first = $this->first($field, $start, $end);
        $last  = $this->last($field, $start, $end);
        return $last - $first;
    }

    public function relDisp(string $field, int $start, int $end, string $resolution): float
    {
        // Relative displacement computed as (last - first) / first.
        $first = $this->first($field, $start, $end);
        if ($first == 0.0) {
            return 0.0;
        }
        $last = $this->last($field, $start, $end);
        return ($last - $first) / $first;
    }

    public function avgSqrVariation(string $field, int $start, int $end): float
    {
        $values = $this->getFieldValues($field, $start, $end);
        $count  = count($values);
        if ($count === 0) {
            return 0.0;
        }
        $mean = array_sum($values) / $count;
        $sumSquaredDifferences = 0.0;
        foreach ($values as $value) {
            $sumSquaredDifferences += ($value - $mean) ** 2;
        }
        return $sumSquaredDifferences / $count;
    }

    public function count(string $field, int $start, int $end, string $resolution): int
    {
        // It is assumed that the repository offers a method to retrieve records in a range.
        $records = $this->repo->findRange($start, $end);
        return count($records);
    }

    public function max(string $field, int $start, int $end): float
    {
        $values = $this->getFieldValues($field, $start, $end);
        return !empty($values) ? (float)max($values) : 0.0;
    }

    public function min(string $field, int $start, int $end): float
    {
        $values = $this->getFieldValues($field, $start, $end);
        return !empty($values) ? (float)min($values) : 0.0;
    }

    public function avg(string $field, int $start, int $end): float
    {
        $values = $this->getFieldValues($field, $start, $end);
        $count  = count($values);
        return $count > 0 ? array_sum($values) / $count : 0.0;
    }

    public function sum(string $field, int $start, int $end): float
    {
        $values = $this->getFieldValues($field, $start, $end);
        return array_sum($values);
    }
}
