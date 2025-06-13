<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\AtomizerModel;

class Matrix extends AbstractModel
{
    protected array $matrix;

    public function __construct(array $matrix = [])
    {
        $this->matrix = $matrix;
    }

    public function addVector(Vector $row): void
    {
        $this->matrix[] = $row;
    }

    public function getMatrix(): array
    {
        return $this->matrix;
    }
}