<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\App\Command;

use Dantweb\Ecommwatch\App\Command\InstallPluginCommand;
use Dantweb\Ecommwatch\App\EcwWatchKernel;
use Dantweb\Ecommwatch\Framework\Application\PluginManager;
use Dantweb\Ecommwatch\Framework\Exception\ECWatchException;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class InstallPluginCommandTest extends TestCase
{
    private ConsoleApplication $application;

    protected function setUp(): void
    {
        // Boot the micro‑kernel in test mode
        $kernel = new EcwWatchKernel('test', true);
        $kernel->boot();

        // Wrap it in Symfony’s Console Application
        $this->application = new ConsoleApplication($kernel);

        // Register the command under its defaultName
        $this->application->add(new InstallPluginCommand('ecw:install-plugin'));
    }

    public function testPluginIdIsRequired(): void
    {
        $command = $this->application->find('ecw:install-plugin');
        $tester  = new CommandTester($command);

        // Missing required argument should throw a runtime exception
        $this->expectException(\RuntimeException::class);
        $tester->execute([]);
    }

    public function testInvalidPluginIdGivesFailure(): void
    {
        $command = $this->application->find('ecw:install-plugin');
        $tester  = new CommandTester($command);

        // Provide a pluginId that isn’t in PluginManager::getAvailablePlugins()
        $tester->execute(['pluginId' => 'nonexistent_plugin']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Plugin not found', $tester->getDisplay());
    }

    public function testValidPluginInstallsSuccessfully(): void
    {
        $manager   = new PluginManager();
        $available = $manager->getAvailablePlugins();
        $this->assertNotEmpty($available, 'Expected at least one available plugin in test environment');

        $pluginId = $available[0];
        $command = $this->application->find('ecw:install-plugin');
        $tester  = new CommandTester($command);
        $installedPlugins = $manager->getInstalledPlugins();
        if (!empty($installedPlugins)) {
            $this->expectException(ECWatchException::class);
            $tester->execute(['pluginId' => $pluginId]);
        } else {
            $tester->execute(['pluginId' => $pluginId]);
            $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
            $this->assertStringContainsString('Plugin installed successfully', $tester->getDisplay());
        }
    }
}
