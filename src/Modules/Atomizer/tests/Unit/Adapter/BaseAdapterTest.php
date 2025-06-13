<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\Tests\Modules\Atomizer\Adapter;

use Dantweb\Atomizer\Adapter\BaseAdapter;
use PHPUnit\Framework\TestCase;
use Dantweb\Atomizer\Map\MapInterface;
use Dantweb\Atomizer\AtomizerModel\Vector;
use Dantweb\Atomizer\AtomizerModel\Matrix as AtomizerMatrix;

/**
 * Dummy implementation of MapInterface used for testing.
 */
class DummyMap implements MapInterface
{
    public function getTargetFieldName(string $sourceField): string
    {
        return 'field_' . $sourceField;
    }

    public function getTargetType(string $sourceField): string
    {
        return 'string';
    }
}

class BaseAdapterTest extends TestCase
{
    private BaseAdapter $adapter;

    protected function setUp(): void
    {
        $dummyMap = new DummyMap();
        $this->adapter = new BaseAdapter($dummyMap, new \Psr\Log\NullLogger());
    }

    /**
     * @throws \Exception
     */
    public function testConvertRowReturnsVector(): void
    {
        $tableName = 'sample_table';
        $dataRow = ['id' => '1', 'name' => 'Test'];

        $vector = $this->adapter->getVectorFromArray($tableName, $dataRow);

        $this->assertInstanceOf(Vector::class, $vector, 'convertRow() should return an instance of Vector');
        $this->assertCount(2, $vector, 'Vector should be empty as no items were added in the iteration');
    }

    public function testGetAtomizedDataMatrixReturnsMatrixWithVectors(): void
    {
        $tableName = 'sample_table';
        $srcData = [
            ['id' => '1', 'name' => 'Test 1'],
            ['id' => '2', 'name' => 'Test 2'],
        ];

        $matrix = $this->adapter->getAtomizedDataMatrix($tableName, $srcData);
        $this->assertInstanceOf(AtomizerMatrix::class, $matrix, 'getAtomizedDataMatrix() should return an instance of AtomizerMatrix');

        $vectors = $matrix->getMatrix();

        $this->assertCount(count($srcData), $vectors, 'Matrix should contain one vector per source data row');
        foreach ($vectors as $vector) {
            $this->assertInstanceOf(Vector::class, $vector, 'Each entry in the matrix should be an instance of Vector');
        }
    }
}