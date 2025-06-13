<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Application;

use Dantweb\Ecommwatch\Config\Defaults;
use Dantweb\Ecommwatch\Framework\Middleware\BaseModelMigrator;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use Symfony\Component\Filesystem\Path;

abstract class AbstractPlugin implements PluginInterface
{
    use EcwModelsTrait;

    public static string $MODEL_DIR = Defaults::ECW_PLUGIN_MODELS_DIR_NAME;
    public static string $MAP_DIR = Defaults::ECW_PLUGIN_MAPS_DIR_NAME;
    protected array $migratedModels = [] ;
    protected array $allModels = [];
    protected array $maps = [];
    protected Migration $migration;
    private string $modelDir;
    private string $mapDir;
    private BaseModelMigrator $baseModelMigrator;

    public function __construct(protected string $id,  protected string $version = 'dev')
    {
        $this->modelDir = Path::join(
            (new Config())->getPluginDir(),
            $this->id,
            self::$MODEL_DIR
        );
        $this->mapDir = Path::join(
            (new Config())->getPluginDir(),
            $this->id,
            self::$MAP_DIR
        );
        $this->migration = new Migration(DatabaseConnector::getInstance());
        $this->baseModelMigrator = new BaseModelMigrator(
            $this->migration, $this->modelDir
        );
        $this->migratedModels = $this->getMigratedModels();
        $this->allModels = $this->baseModelMigrator->getEcwModels();
        $this->maps = $this->getEcwMapsFromPlugin($this->mapDir);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getMigratedModels(): array
    {
        if (empty($this->migratedModels)) {
            $this->allModels = $this->getEcwModels();
            foreach($this->allModels as $model) {
                if ($this->migration->isTableExists($model->getModelName()))
                {
                    $this->migratedModels[] = $model;
                }
            }
        }

        return $this->migratedModels;
    }

    public function getEcwModels(): array
    {
        return $this->getEcwModelsFromPlugin($this->modelDir);
    }

    public function getEcwMaps(string $mapDir): array
    {
        return $this->getEcwMapsFromPlugin($mapDir);
    }

    public function isInstalled(): bool
    {
        $installed = (new Config())->getInstalledPlugins();
        foreach ($installed as $plugin) {
            if ($plugin['id'] === $this->getId()) {
                return true;
            }
        }

        return false;
    }
}