<?php
declare(strict_types=1);

namespace Dantweb\Atomizer\Tests\Unit\Core\Adapter;

use Dantweb\Atomizer\Adapter\BaseAdapter;
use Dantweb\Atomizer\AtomizerModel\Atom;
use Dantweb\Atomizer\AtomizerModel\Matrix as AtomizerMatrix;
use Dantweb\Atomizer\AtomizerModel\Vector;
use Dantweb\Atomizer\Map\MapFactory;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class BaseAdapterRealMapTest extends TestCase
{
    private BaseAdapter $adapter;
    private string $modelConfigPath = __DIR__ . '/../../_test_data/TestMapConfig.yaml';


    protected function setUp(): void
    {
        // Create the configuration array that mimics your YAML.
        $yamlData = file_get_contents($this->modelConfigPath);
        $yaml = yaml_parse($yamlData);

        // Create the real map using the factory.
        $factory = new MapFactory();
        $realMap = $factory->create($yaml);
        $this->adapter = new BaseAdapter($realMap, new \Psr\Log\NullLogger());
    }

    public function testConvertRowUsingRealMap(): void
    {
        $tableName = 'orders';

        // Data row with keys matching the mapping.
        $dataRow = [
            'order_nr'  => '12345',
            'datetime'  => '2020-12-31 23:59:59',
            'order_sum' => 250.75,
        ];

        // Convert the data row into a vector.
        $vector = $this->adapter->getVectorFromArray($tableName, $dataRow);

        $this->assertInstanceOf(Vector::class, $vector, 'convertRow() should return an instance of Vector');
        $this->assertCount(
            count($dataRow),
            $vector,
            'Vector should have one atom per field in the data row'
        );

        // Validate each atom.
        foreach ($vector as $atom) {
            $this->assertInstanceOf(Atom::class, $atom, 'Each item in the vector should be an Atom');
            $this->assertEquals($tableName, $atom->getTableName(), 'Atom table name does not match');

            $sourceField = $atom->getSourceField();
            switch ($sourceField) {
                case 'order_nr':
                    $this->assertEquals('order.order_oxnumber', $atom->getFieldName());
                    break;
                case 'datetime':
                    $this->assertEquals('order.order_timestamp', $atom->getFieldName());
                    break;
                case 'order_sum':
                    $this->assertEquals('order.order', $atom->getFieldName());
                    break;
                default:
                    $this->fail("Unexpected source field encountered: {$sourceField}");
            }
        }
    }

    public function testGetAtomizedDataMatrixUsingRealMap(): void
    {
        $tableName = 'orders';
        $srcData = [
            [
                'order_nr'  => '12345',
                'datetime'  => '2020-12-31 23:59:59',
                'order_sum' => 250.75,
            ],
            [
                'order_nr'  => '67890',
                'datetime'  => '2021-01-01 10:00:00',
                'order_sum' => 100.50,
            ],
        ];

        $matrix = $this->adapter->getAtomizedDataMatrix($tableName, $srcData);

        $this->assertInstanceOf(
            AtomizerMatrix::class,
            $matrix,
            'getAtomizedDataMatrix() should return an instance of AtomizerMatrix'
        );

        $vectors = $matrix->getMatrix();
        $this->assertCount(
            count($srcData),
            $vectors,
            'Matrix should contain one vector per source data row'
        );

        foreach ($vectors as $vector) {
            $this->assertInstanceOf(
                Vector::class,
                $vector,
                'Each entry in the matrix should be an instance of Vector'
            );
        }
    }
}