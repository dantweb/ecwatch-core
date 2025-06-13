<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\AtomizerModel;

use Countable;

class Vector extends AbstractModel implements ModelInterface, Countable
{
    protected array $vector = [];

    public function getVector(): array
    {
        return $this->vector;
    }

    public function setVector(array $atoms): void
    {
        $this->vector = $atoms;
    }

    public function count(): int
    {
        return count($this->vector);
    }

    public function addItem(Atom $cell): void
    {
        $this->vector[$cell->getName()] = $cell;
    }
}