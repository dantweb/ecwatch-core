<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\Tests\Unit\Core\Adapter;

use Dantweb\Atomizer\Adapter\BaseAdapter;
use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Atomizer\Map\MapFactory;
use Dantweb\Atomizer\Map\MapInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BaseAdapterCsvTest extends TestCase
{
    private string $modelYamlPath = __DIR__ . '/../../_test_data/TestModelConfig.yaml';
    private string $mapYamlPath   = __DIR__ . '/../../_test_data/TestMapConfig.yaml';
    private BaseAdapter $adapter;
    private MapInterface $map;
    private EcwModelInterface $ecwModel;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $modelFactory = new EcwModelFactory();
        $this->ecwModel = $modelFactory->createModelFromAbsPath($this->modelYamlPath);
        $this->assertNotNull($this->ecwModel, 'EcwModel should be created from YAML');

        $mapYamlContent = file_get_contents($this->mapYamlPath);
        $mapFactory = new MapFactory();
        $this->map = $mapFactory->create(yaml_parse($mapYamlContent));

        // Create a dummy logger (using a mock here).
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->adapter = new BaseAdapter($this->map, $this->logger);
    }

    public function testGetModelArrayFromCsvFileNotFound(): void
    {
        $nonExistentFile = 'non_existent_file.csv';

        $models = $this->adapter->getModelArrayFromCsv($this->ecwModel, $nonExistentFile);
        $this->assertNull($models, 'Expected null when CSV file is not found');
    }
}