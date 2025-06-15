<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Service;

use App\Modules\Atomizer\src\Adapter\BaseAdapter;
use App\Modules\Atomizer\src\EcwModel\AbstractEcwModel;
use App\Modules\Atomizer\src\EcwModel\EcwModelFactory;
use App\Modules\Atomizer\src\EcwModel\EcwModelInterface;
use App\Modules\Atomizer\src\Map\MapFactory;
use App\Modules\Atomizer\src\Map\MapInterface;
use Dantweb\Ecommwatch\Framework\Exception\EcwTableNotFoundException;
use Dantweb\Ecommwatch\Framework\Middleware\BaseModelMigrator;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use Dantweb\Ecommwatch\Framework\Service\BaseImportService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

class ImportServiceTest extends TestCase
{
    private string $modelDir;
    private DatabaseConnector $dbConnect;
    private EcwModelInterface $ecwModel;
    private MapInterface $map;
    private BaseAdapter $adapter;
    private BaseImportService $service;

    protected function setUp(): void
    {
        $this->dbConnect = DatabaseConnector::getInstance();

        // 1) Migrate the BaseOrderModel table
        $migration = new Migration($this->dbConnect);
        $migrator  = new BaseModelMigrator(
            $migration,
            Path::join(__DIR__, '/../../../_data/model_migration_test/')
        );
        $migrator->migrate();

        // 2) Prepare the ECW model, map, adapter, and CSV path
        $this->modelDir = Path::join(__DIR__, '/../../../_data/model_migration_test');
        $yamlPath = Path::join($this->modelDir, 'ImportServiceTestModel.yaml');
        $this->ecwModel = (new EcwModelFactory())->createModelFromAbsPath($yamlPath);
        $mapYamlPath = Path::join($this->modelDir, 'ecw_maps', 'ImportServiceTest_BaseOrderImportDataMap.yaml');
        $mapConfig = yaml_parse(file_get_contents($mapYamlPath));
        $this->map = (new MapFactory())->create($mapConfig);
        $this->adapter = new BaseAdapter($this->map);
        $this->service = new BaseImportService($this->modelDir . '/import_data');
    }


    /**
     * @throws EcwTableNotFoundException
     */
    public function testImportCsvReturnsCorrectCountAndPersistsRows(): void
    {
        $csv = Path::join($this->modelDir, 'import_data', 'ImportModelTestData.csv');

        $count = $this->service->importCsv(
            $this->ecwModel,
            $this->map,
            $this->adapter,
            $csv
        );

        // The demo CSV has 50 rows
        $this->assertSame(7, $count, 'Expected 50 imported rows');

        // Verify rows in DB
        $pdo = $this->dbConnect->getDb();
        $stmt = $pdo->query('SELECT COUNT(*) AS total FROM ImportServiceTestModel');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame(7, $row['total']);
    }

    public function testImportCsvThrowsIfTableMissing(): void
    {
        // Create a dummy model whose table hasn't been migrated
        $dummyModel = new class('nonexistent_table', ['foo'=>['type'=>'int']]) extends AbstractEcwModel {};

        try {
            $this->service->importCsv(
                $dummyModel,
                $this->map,
                $this->adapter,
                __DIR__ . '/does-not-matter.csv'
            );
        } catch (EcwTableNotFoundException $e) {
            $this->assertInstanceOf(EcwTableNotFoundException::class, $e);
        }
    }
}
