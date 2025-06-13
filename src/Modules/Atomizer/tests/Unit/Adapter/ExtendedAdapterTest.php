<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\Tests\Modules\Atomizer\Adapter;

use Dantweb\Atomizer\Adapter\BaseAdapter;
use Dantweb\Atomizer\AtomizerModel\Atom;
use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Atomizer\Map\MapFactory;
use Dantweb\Atomizer\Map\MapInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ExtendedAdapterTest extends TestCase
{
    private string $modelYamlPath = __DIR__ . '/../../_test_data/TestModelConfig.yaml';
    private string $mapYamlPath = __DIR__ . '/../../_test_data/TestMapConfig.yaml';
    private BaseAdapter $adapter;
    private MapInterface $map;
    private EcwModelInterface $ecwModel;

    protected function setUp(): void
    {


        $ecwModelFactory = new EcwModelFactory();
        $this->ecwModel = $ecwModelFactory->createModelFromAbsPath($this->modelYamlPath);
        $this->assertNotNull($this->ecwModel, 'EcwModel should be created from YAML');

        $mapYamlContent = file_get_contents($this->mapYamlPath);
        $mapFactory = new MapFactory();
        $this->map = $mapFactory->create(yaml_parse($mapYamlContent));

        $mockLogger = $this->createMock(LoggerInterface::class);
        $this->adapter = new BaseAdapter($this->map, $mockLogger);
    }

    public function testConvertToEcwModelsUsesMapCorrectly(): void
    {
        $sourceData = [
            ['order_nr' => 'ORD-001', 'datetime' => '2023-12-31 23:59:59', 'order_sum' => 100.50],
            ['order_nr' => 'ORD-002', 'datetime' => '2024-01-01 00:00:00', 'order_sum' => 200.75],
        ];

        $models = $this->adapter->convertToEcwModels($this->ecwModel, $sourceData);

        $this->assertNotNull($models, 'convertToEcwModels should return an array of models');
        $this->assertCount(2, $models);

        foreach ($models as $index => $model) {
            $this->assertInstanceOf(EcwModelInterface::class, $model);

            foreach ($sourceData[$index] as $fieldName => $value) {
                $field = $this->map->getTargetFieldName($fieldName);
                $this->assertTrue($model->canHaveMappedField($field), "Model should have field {$fieldName}");
                $this->assertEquals($value, $model->getMappedField($field), "Model's {$fieldName} should match source data");
            }
        }
    }

    public function testGetAtomizedDataMatrixUsesMapCorrectly(): void
    {
        $tableName = 'order_table';
        $srcData = [
            ['order_nr' => 'ORD-001', 'datetime' => '2023-12-31 23:59:59', 'order_sum' => 100.50],
            ['order_nr' => 'ORD-002', 'datetime' => '2024-01-01 00:00:00', 'order_sum' => 200.75],
        ];

        $matrix = $this->adapter->getAtomizedDataMatrix($tableName, $srcData);
        $this->assertNotNull($matrix, 'Matrix should be created');
        $this->assertCount(2, $matrix->getMatrix());
        $mapYamlContent = file_get_contents($this->mapYamlPath);
        $mapFactory = new MapFactory();
        $this->map = $mapFactory->create(yaml_parse($mapYamlContent));

        foreach ($matrix->getMatrix() as $index => $vector) {
            /** @var Atom $atom */
            foreach ($vector->getVector() as $atom) {
                $srcFieldName = $this->getSrcFieldNameFromAtom($atom);
                $expectedValue = $srcData[$index][$srcFieldName];
                $this->assertEquals($expectedValue, $atom->getValue(), "Atom value should match source data for {$srcFieldName}");
            }
        }

    }

    public function testConvertToEcwModelsHandlesEmptyData(): void
    {
        $models = $this->adapter->convertToEcwModels($this->ecwModel, []);
        $this->assertNull($models, 'convertToEcwModels should return null for empty data');
    }

    protected function getSrcFieldNameFromAtom(Atom $atom): ?string
    {
        // Get the target field name from the atom (in your case, it is "OutputModel.order_number")
        $targetName = $atom->getName();

        // Iterate through the map fields to look for the matching target field name.
        foreach ($this->map->getMap() as $srcField => $fieldData) {
            if (isset($fieldData['target_name']) && $fieldData['target_name'] === $targetName) {
                return $srcField;
            }
        }

        // Return null if not found or you can throw an exception if that suits your needs
        return null;
    }
}
