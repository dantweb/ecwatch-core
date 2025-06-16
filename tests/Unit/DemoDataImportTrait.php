<?php

namespace Dantweb\Ecommwatch\Tests\Unit;

use Dantweb\Atomizer\Adapter\BaseAdapter;
use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Atomizer\Map\MapFactory;
use Dantweb\Ecommwatch\Framework\Exception\EcwTableNotFoundException;
use Dantweb\Ecommwatch\Framework\Middleware\BaseModelMigrator;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use Dantweb\Ecommwatch\Framework\Middleware\RepoFactory\RepoFactory;
use Dantweb\Ecommwatch\Framework\Middleware\Repository\AbstractRepo;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Path;

trait DemoDataImportTrait
{
    protected static string $testDir = "/../_data/model_migration_test/";
    protected string $importDataDir;
    protected RepoFactory $repoFactory;
    protected DatabaseConnector $dbConnect;
    protected string $modelDir;

    protected EcwModelInterface $ecwModel;

    protected function init(): void
    {
        $this->dbConnect = DatabaseConnector::getInstance();
        $this->repoFactory = new RepoFactory($this->dbConnect);
        $this->modelDir = Path::join(__DIR__, self::$testDir);
        $this->importDataDir = Path::join($this->modelDir, 'import_data');
        $this->ecwModel = $this->getEcwModel();
    }

    protected function getEcwModel(): EcwModelInterface
    {
        return (new EcwModelFactory())->createModelFromAbsPath(
            Path::join($this->modelDir, 'BaseOrderModel.yaml')
        );
    }

    protected function doMigrations(): void
    {
        // Run migration so that tables are created.
        $migrationService = new Migration($this->dbConnect);
        $migrator = new BaseModelMigrator($migrationService, $this->modelDir);

        try {
            $migrator->migrate();
        } catch (\Exception $e) {
        }
    }

    protected function cleanUp(): void
    {
        // Drop the table
        $pdo = $this->dbConnect->getDb();
        $pdo->exec('DROP TABLE IF EXISTS BaseOrderModel');
        $pdo->exec('DROP TABLE IF EXISTS ShipmentModel');
        $pdo->exec('DROP TABLE IF EXISTS CustomerModel');
    }

    /**
     * @throws EcwTableNotFoundException
     */
    protected function importDemoData(): EcwModelInterface
    {
        $importRawCsvPath = Path::join($this->modelDir, 'import_data', 'BaseOrder_raw_input.csv');

        $repo = $this->repoFactory->getRepo($this->ecwModel);
        $repo->setWritingMode(AbstractRepo::DUPLICATES_REPORT);
        $mapYamlPath = Path::join($this->modelDir, 'ecw_maps', 'BaseOrderImportDataMap_demo.yaml');
        $mapYaml = file_get_contents($mapYamlPath);
        $mapFactory = new MapFactory();
        $map = $mapFactory->create(yaml_parse($mapYaml));

        $adapter = new BaseAdapter($map, new NullLogger());
        $importedModels = $adapter->getModelArrayFromCsv($this->ecwModel, $importRawCsvPath);

        foreach ($importedModels as $importedModel) {
            $repo->save($importedModel);
        }

        return $this->ecwModel;
    }
}
