<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\App\Command;

use Dantweb\Ecommwatch\App\Command\ImportModelCommand;
use Dantweb\Ecommwatch\App\EcwWatchKernel;
use Dantweb\Ecommwatch\Framework\Middleware\BaseModelMigrator;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Path;

class ImportModelCommandTest extends TestCase
{
    private ConsoleApplication $application;
    private DatabaseConnector $db;
    private string $dataDir;

    protected function setUp(): void
    {
        // Kernel & ConsoleApplication
        $kernel = new EcwWatchKernel('test', true);
        $kernel->boot();
        $this->application = new ConsoleApplication($kernel);
        $this->application->add(new ImportModelCommand());

        $this->db = DatabaseConnector::getInstance();
        // Ensure tables are migrated
        $migration = new Migration($this->db);
        $migrator  = new BaseModelMigrator(
            $migration,
            Path::join(__DIR__, '/../../../_data/model_migration_test')
        );
        $migrator->migrate();

        $this->dataDir = Path::join(__DIR__, '/../../../_data/model_migration_test');
    }

    protected function tearDown(): void
    {
        // Drop migrated tables
        $pdo = $this->db->getDb();
        $pdo->exec('DROP TABLE IF EXISTS CommandTestModel');
    }

    public function testCsvNotFound(): void
    {
        $cmd = $this->application->find('ecw:import-model');
        $tester = new CommandTester($cmd);

        $status = $tester->execute([
            'modelName' => 'CommandTestModel',
            'mapName'   => 'CommandTestModel_Map.yaml',
            'csvPath'   => '/nonexistent.csv',
            'modelPath' => $this->dataDir,
        ]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('CSV not found', $tester->getDisplay());
    }

    public function testModelYamlMissing(): void
    {
        $csv = Path::join($this->dataDir, 'import_data', 'CommandTestData.csv');

        $cmd = $this->application->find('ecw:import-model');
        $tester = new CommandTester($cmd);

        $status = $tester->execute([
            'modelName' => 'WrongModel',
            'mapName'   => 'BaseOrderImportDataMap_demo.yaml',
            'csvPath'   => $csv,
            'modelPath' => $this->dataDir,
        ]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Model YAML not found', $tester->getDisplay());
    }

    public function testMapYamlMissing(): void
    {
        $csv = Path::join($this->dataDir, 'import_data', 'CommandTestData.csv');

        $cmd = $this->application->find('ecw:import-model');
        $tester = new CommandTester($cmd);

        $status = $tester->execute([
            'modelName' => 'CommandTestModel',
            'mapName'   => 'NonexistentMap.yaml',
            'csvPath'   => $csv,
            'modelPath' => $this->dataDir,
        ]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertTrue(str_contains($tester->getDisplay(), 'not found'));
    }

    public function testSuccessfulImport(): void
    {
        $csv = Path::join($this->dataDir, 'import_data', 'CommandTestData.csv');

        $cmd = $this->application->find('ecw:import-model');
        $tester = new CommandTester($cmd);

        $status = $tester->execute([
            'modelName' => 'CommandTestModel',
            'mapName'   => 'CommandTestModel_Map.yaml',
            'csvPath'   => $csv,
            'modelPath' => $this->dataDir,
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString(
            '[OK] Imported <info>7</info> records into <info>CommandTestModel</info>',
            $tester->getDisplay()
        );

        // Verify table row count
        $stmt = $this->db->getDb()->query('SELECT COUNT(*) AS total FROM CommandTestModel');
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame(
            7,
            $row['total']
        );
    }
}
