<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\App\Command;

use Dantweb\Ecommwatch\Framework\Application\Config;
use Dantweb\Ecommwatch\Framework\Application\PluginManager;
use Dantweb\Ecommwatch\Framework\Exception\ECWatchException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;

class InstallPluginCommand extends Command
{
    protected static string $defaultName = 'ecw:install-plugin';
    protected static string $defaultDescription = 'Installs a plugin into the Modules directory';

    protected function configure(): void
    {
        $this
            ->setName('ecw:install-plugin')
            ->setDescription(self::$defaultDescription)
            ->addArgument(
                'pluginId',
                InputArgument::REQUIRED,
                'Path to the plugin source directory'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ecwPluginManager = new PluginManager();
        $availablePlugins = $ecwPluginManager->getAvailablePlugins();
        $pluginId = $input->getArgument('pluginId');

        if (empty($pluginId)) {
            $io->error('Plugin ID is required');
            return Command::FAILURE;
        }

        if (!in_array($pluginId, $availablePlugins)) {
            $io->error('Plugin not found');
            return Command::FAILURE;
        }

        $config = new Config();

        try {
            $config->addPlugin($pluginId['id']);
        } catch (IOException|\Exception $e) {
            $io->error('Failed to install plugin');
            $io->error($e->getMessage());
            throw new ECWatchException($e->getMessage());
        }

        $io->success('Plugin installed successfully');
        return Command::SUCCESS;
    }
}
