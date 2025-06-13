<?php

namespace Dantweb\Atomizer\Adapter;

use Dantweb\Atomizer\AtomizerModel\Matrix;
use Dantweb\Atomizer\AtomizerModel\Vector;

interface AdapterInterface
{
    public function getVectorFromArray(string $tableName, array $dataRow): ?Vector;
    public function getAtomizedDataMatrix(string $tableName, array $srcData): ?Matrix;
}