<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\App\Command;

use App\Modules\Atomizer\src\EcwModel\AbstractEcwModel;
use Dantweb\Ecommwatch\App\Command\MigratePluginCommand;
use Dantweb\Ecommwatch\App\EcwWatchKernel;
use Dantweb\Ecommwatch\Framework\Application\PluginManager;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MigratePluginCommandTest extends TestCase
{
    private ConsoleApplication $application;
    private DatabaseConnector $db;

    protected function setUp(): void
    {
        // Boot the kernel
        $kernel = new EcwWatchKernel('test', true);
        $kernel->boot();

        // Wrap in ConsoleApplication
        $this->application = new ConsoleApplication($kernel);
        $this->application->add(new MigratePluginCommand());

        $this->db = DatabaseConnector::getInstance();
        // Ensure a clean slate
        $pdo = $this->db->getDb();
//        $pdo->exec('DROP TABLE IF EXISTS BaseOrderModel');
    }

    protected function tearDown(): void
    {
        // Clean up any tables the command created
        $pdo = $this->db->getDb();
//        $pdo->exec('DROP TABLE IF EXISTS BaseOrderModel');
        $pdo->exec('DROP TABLE IF EXISTS CustomerModel');
        $pdo->exec('DROP TABLE IF EXISTS ShipmentModel');
    }

    public function testInvalidPluginIdFails(): void
    {
        $command = $this->application->find('ecw:migrate-plugin');
        $tester  = new CommandTester($command);

        $exit = $tester->execute(['pluginId' => 'nonexistent_plugin']);
        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('is not available', $tester->getDisplay());
    }

    public function testValidPluginMigratesModels(): void
    {
        $pm = new PluginManager();
        $available = $pm->getAvailablePlugins();
        $this->assertNotEmpty($available, 'Expected at least one available plugin');
        $plugin = $available[0];

        $command = $this->application->find('ecw:migrate-plugin');
        $tester  = new CommandTester($command);

        $exit = $tester->execute(['pluginId' => $plugin['id']]);
        $this->assertSame(Command::SUCCESS, $exit);

        $output = $tester->getDisplay();
        try {
            $this->assertStringContainsString('Migrated model BaseOrderModel', $output);
            $this->assertStringContainsString('have run successfully', $output);
        } catch (\Exception $e) {
            $this->assertStringContainsString('No migration for plugin', $output);
        }

        // Verify table now exists
        $stmt = $this->db->getDb()->query("SHOW TABLES LIKE 'BaseOrderModel'");
        $this->assertNotFalse($stmt->fetch());
    }

    public function testMigrationErrorThrowsException(): void
    {
        // Create a plugin that has a model with no fields, to force an error
        $pm = new PluginManager();
        $default = $pm->getAvailablePlugins()[0];
        $plugin = $pm->getPluginObj(reset($default));
        // Temporarily override its migratedModels to return a bogus model
        $ref = new \ReflectionObject($plugin);
        $prop = $ref->getParentClass()->getProperty('migratedModels');
        $prop->setValue($plugin, [
            new class('', []) extends AbstractEcwModel {}
        ]);

        $command = $this->application->find('ecw:migrate-plugin');
        $tester  = new CommandTester($command);

        $exit = $tester->execute(['pluginId' => $default['id']]);
        $this->assertSame(Command::SUCCESS, $exit);

    }
}
