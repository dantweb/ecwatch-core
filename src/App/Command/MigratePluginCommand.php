<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\App\Command;

use Dantweb\Ecommwatch\Framework\Application\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Exception\ECWatchException;

class MigratePluginCommand extends Command
{
    protected static string $defaultName = 'ecw:migrate-plugin';
    protected static string $defaultDescription = 'Run database migrations for a specific plugin';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }
    protected function configure(): void
    {
        $this
            ->setName('ecw:migrate-plugin')
            ->setDescription(self::$defaultDescription)
            ->addArgument(
                'pluginId',
                InputArgument::REQUIRED,
                'ID of the plugin whose migrations you want to run'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pluginId = $input->getArgument('pluginId');

        if (empty($pluginId)) {
            $io->error('Plugin ID is required');
            return Command::FAILURE;
        }

        $pm = new PluginManager();
        $available  = $pm->getAvailablePlugins();

        $pluginFound = false;
        foreach ($available as $plugin) {
            if ($plugin['id'] === $pluginId) {
                $pluginFound = true;
                break;
            }
        }

        if (!$pluginFound) {
            $io->error("Plugin '{$pluginId}' is not available.");
            return Command::FAILURE;
        }

        $plugin = $pm->getPluginObj($pluginId);
        $models = $plugin->getMigratedModels();

        if (empty($models)) {
            $io->warning("Plugin '{$pluginId}' has no ECW models to migrate.");
            return Command::SUCCESS;
        }

        $db         = DatabaseConnector::getInstance();
        $migration  = new Migration($db);

        try {
            foreach ($models as $model) {
                $sql = $migration->createMigration($model);
                if (empty($sql)) {
                    $io->success("No migration for plugin '{$pluginId}' needed");
                    return Command::SUCCESS;
                }
                $migration->run($sql);
                $io->writeln("Migrated model <info>{$model->getName()}</info>");
            }
        } catch (\Exception $e) {
            $io->error('Migration failed: ' . $e->getMessage());
            throw new ECWatchException($e->getMessage());
        }

        $io->success("All migrations for plugin '{$pluginId}' have run successfully.");
        return Command::SUCCESS;
    }
}
