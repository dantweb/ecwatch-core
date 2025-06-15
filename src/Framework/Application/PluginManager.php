<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Application;

use App\Modules\Atomizer\src\EcwModel\EcwModelInterface;
use Symfony\Component\Filesystem\Path;

class PluginManager
{
    protected array $models;
    protected array $installedPlugins;
    protected array $availablePlugins;

    protected Config $config;

    public function __construct(string $configPath = '')
    {
        $this->config = new Config($configPath);
        $this->availablePlugins = $this->getAvailablePlugins();
        $this->installedPlugins = $this->getInstalledPlugins();
        $this->models = $this->getAllMigratedEcwModels();
    }

    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }


    public function getAllMigratedEcwModels(): array
    {
        $migratedModels = [];
        foreach ($this->installedPlugins as $plugin) {
            $models = $plugin->getMigratedModels();
            if (!empty($models) && is_array($models)) {
                $migratedModels = array_merge($migratedModels, $models);
            }
        }

        return $migratedModels;
    }


    /**
     * @return PluginInterface[]
     */
    public function getInstalledPlugins(): array
    {
        $installedPlugins = [];
        $available = $this->getAvailablePlugins();
        foreach ($available as $pluginInfo) {
            $plugin = $this->getPluginObj($pluginInfo['id']);
            if ($plugin !== null && $plugin->isInstalled()) {
                $installedPlugins[] = $plugin;
            }
        }

        return $installedPlugins;
    }


    public function getAvailablePlugins(): array
    {
        $pluginDir = $this->config->getPluginDir();
        if (!is_dir($pluginDir) || !is_readable($pluginDir)) {
            return [];
        }

        $availablePlugins = [];

        $subDirs = array_filter(glob($pluginDir . '/*'), 'is_dir');
        foreach ($subDirs as $subDir) {
            $yamlFiles = glob($subDir . '/*.yaml');

            foreach ($yamlFiles as $yamlFile) {
                $fileContent = file_get_contents($yamlFile);

                if (preg_match('/^ecw_plugin_config:\s*\n\s*id:\s*\w+/', $fileContent)) {
                    $pluginId = null;

                    if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                        $yaml = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($yamlFile));
                        $pluginId = $yaml['ecw_plugin_config']['id'] ?? null;
                    }

                    if ($pluginId !== null) {
                        $availablePlugins[] = [
                            'id' => $pluginId,
                            'path' => $subDir,
                            'configFile' => $yamlFile,
                        ];
                    }
                }
            }
        }

        return $availablePlugins;
    }


    public function getPluginObj(string $id): ?PluginInterface
    {
        $camelCase = str_replace('_', '', ucwords(strtolower($id), '_'));
        $pluginDir = $this->config->getPluginDir();
        $pluginClassFile = $camelCase . '.php';
        $pluginFile = Path::join($pluginDir, $id, $pluginClassFile);
        if (!file_exists($pluginFile) || !is_readable($pluginFile)) {
            return null;
        }

        require_once $pluginFile;

        $pluginNamespace = $this->getNamespaceFromFile($pluginFile);

        $className = sprintf("%s\%s", $pluginNamespace, $camelCase);
        if (class_exists($className)) {
            return new $className($id);
        }

        return null;
    }


    public function getModelByName(string $name): ?EcwModelInterface
    {
        $plugins = $this->getInstalledPlugins();
        foreach ($plugins as $plugin) {
            $models = $plugin->getEcwModels();
            if (isset($models[$name])) {
                return $models[$name];
            }
        }
    }


    protected function getNamespaceFromFile(string $filePath): ?string
    {
        $fileContents = file_get_contents($filePath);
        if ($fileContents === false) {
            return null;
        }

        if (preg_match('/^namespace\s+([a-zA-Z0-9_\\\\]+);/m', $fileContents, $matches)) {
            return $matches[1];
        }

        return null;
    }
}