<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\Adapter;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Atomizer\Map\MapInterface;
use Dantweb\Atomizer\AtomizerModel\Atom;
use Dantweb\Atomizer\AtomizerModel\Matrix as AtomizerMatrix;
use Dantweb\Atomizer\AtomizerModel\Vector;

abstract class AbstractAdapter implements AdapterInterface
{
    public function __construct(
        protected MapInterface $dataMap,
        protected ?\Psr\Log\LoggerInterface $logger = null
    ) {
    }

    /**
     * @throws \Exception
     */
    public function getVectorFromArray(string $tableName, array $dataRow): ?Vector
    {
        $vector = new Vector();
        foreach ($dataRow as $key => $value) {
            $atom = new Atom(
                $tableName,
                $this->dataMap->getTargetFieldName($key),
                $this->dataMap->getTargetType($key)
            );
            $atom->setValue($value);
            $vector->addItem($atom);
        }
        return $vector;
    }

    /**
     * @throws \Exception
     */
    public function getAtomizedDataMatrix(string $tableName, array $srcData): ?AtomizerMatrix
    {
        $matrix = new AtomizerMatrix();
        foreach ($srcData as $value) {
            $row = $this->getVectorFromArray($tableName, $value);
            $matrix->addVector($row);
        }
        return $matrix;
    }

    public function getModelFromVector(EcwModelInterface $ecwModel, Vector $vector): ?EcwModelInterface
    {
        try {
            $model = clone $ecwModel;
            foreach ($vector->getVector() as $atom) {
                $field = $atom->getPassport()->getSrcFieldName();
                if ($model->canHaveMappedField($field)) {
                    $model->setMappedField($field, $atom->getValue());
                }
            }

            return $model;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function getEcwArrFromMatrix(EcwModelInterface $ecwModel, AtomizerMatrix $matrix): ?array
    {
        try {
            $models = array_filter(
                array_map(
                    fn($vector) => $this->getModelFromVector($ecwModel, $vector),
                    $matrix->getMatrix()
                ),
                fn($model) => $model !== null
            );

            return $models ?: null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to store models from matrix: ' . $e->getMessage());
            return null;
        }
    }

    public function convertToEcwModels(
        EcwModelInterface $ecwModel,
        array $sourceData
    ): ?array {
        try {
            $matrix = $this->getAtomizedDataMatrix($ecwModel->getModelName(), $sourceData);

            if ($matrix === null) {
                return null;
            }

            return $this->getEcwArrFromMatrix($ecwModel, $matrix);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    protected function getTargetFieldName($srcField): ?string
    {
        return $this->dataMap->getTargetFieldName($srcField);
    }
}