<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Middleware;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Config\Defaults;
use Dantweb\Ecommwatch\Framework\Application\EcwModelsTrait;
use Dantweb\Ecommwatch\Framework\Exception\EcwException;
use Dantweb\Ecommwatch\Framework\Helper\Logger;

abstract class AbstractModelMigrator
{
    use EcwModelsTrait;

    /**
     * @SuppressWarnings(PHPMD.CamelCasePropertyName)
     */
    public static string $defaultModelDir = Defaults::ECW_PLUGIN_MODELS_DIR_NAME;

    public function __construct(
        protected Migration $migrationService,
        protected string $modelDir
    ) {
        if (empty($this->modelDir)) {
            $this->modelDir = self::$defaultModelDir;
        }
    }

    /**
     * @throws \Exception
     */
    public function migrate(): void
    {
        $ecwModels = $this->getEcwModels();
        foreach ($ecwModels as $migrationModel) {
            $migrationSql = $this->migrationService->createMigration($migrationModel);

            if (empty($migrationSql)) {
                continue;
            }

            try {
                $this->migrationService->run($migrationSql);
            } catch (EcwException $e) {
                Logger::error($e->getMessage());
            }
        }
    }

    /**
     * Parses the model directory and creates models from each YAML file
     * that can be accepted by EcwModelFactory.
     *
     * @return EcwModelInterface[]
     */
    public function getEcwModels(): array
    {
        return $this->getEcwModelsFromPlugin($this->modelDir);
    }

    public function getEcwMaps(string $mapDir): array
    {
        return $this->getEcwMapsFromPlugin($mapDir);
    }
}
