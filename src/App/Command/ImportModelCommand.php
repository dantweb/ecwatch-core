<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\App\Command;

use Dantweb\Ecommwatch\Framework\Service\BaseImportService;
use Dantweb\Ecommwatch\Framework\Service\ImportService;
use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\Map\MapFactory;
use Dantweb\Atomizer\Adapter\BaseAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Path;
use Dantweb\Ecommwatch\Framework\Exception\ECWatchException;

class ImportModelCommand extends Command
{
    protected static string $defaultName = 'ecw:import-model';
    protected static string $defaultDescription = 'Import CSV data into a specified ECW model';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setName('ecw:import-model')
            ->setDescription(self::$defaultDescription)
            ->addArgument(
                'modelName',
                InputArgument::REQUIRED,
                'ECW model name (without extension, e.g. BaseOrderModel)'
            )
            ->addArgument(
                'mapName',
                InputArgument::REQUIRED,
                'YAML map filename (e.g. BaseOrderImportDataMap_demo.yaml)'
            )
            ->addArgument(
                'csvPath',
                InputArgument::REQUIRED,
                'Full file path to the CSV to import'
            )
            ->addArgument(
                'modelPath',
                InputArgument::OPTIONAL,
                'Full file path to directory with model YAML and map YAML',
                '/app/var/import_data'
            )
            ->addArgument(
                'protocol',
                InputArgument::OPTIONAL,
                'Please choose a protocol: csv, xml, json.',
                BaseImportService::PROTOCOL_CSV
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $modelName = $input->getArgument('modelName');
        $mapName   = $input->getArgument('mapName');
        $csvPath   = $input->getArgument('csvPath');
        $dataDir   = $input->getArgument('modelPath');
        $protocol   = $input->getArgument('protocol');

        if (!file_exists($csvPath)) {
            $io->error("CSV not found at: {$csvPath}");
            return Command::FAILURE;
        }

        // Model YAML
        $modelYaml = Path::join($dataDir, "{$modelName}.yaml");
        if (!file_exists($modelYaml)) {
            $io->error("Model YAML not found: {$modelYaml}");
            return Command::FAILURE;
        }

        // Map YAML
        $mapYaml = Path::join($dataDir, 'ecw_maps', $mapName);
        if (!file_exists($mapYaml)) {
            $io->error("Map YAML not found: {$mapYaml}");
            return Command::FAILURE;
        }

        // Prepare model, map, adapter, and service
        $model   = (new EcwModelFactory())->createModelFromAbsPath($modelYaml);
        $map     = (new MapFactory())->create(yaml_parse(file_get_contents($mapYaml)));
        $adapter = new BaseAdapter($map, new NullLogger());
        $service = new BaseImportService(Path::join($dataDir, 'import_data'));

        try {
            $count = $service->import($protocol, $model, $map, $adapter, $csvPath);
        } catch (ECWatchException $e) {
            $io->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success("Imported <info>{$count}</info> records into <info>{$modelName}</info>.");
        return Command::SUCCESS;
    }
}
