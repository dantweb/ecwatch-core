<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Application;

use Dantweb\Ecommwatch\Config\Defaults;
use Dantweb\Ecommwatch\Framework\Exception\ECWatchException;

class Config
{
    protected string $configPath = Defaults::ECW_APP_CONFIG_PATH;
    protected array $appConfig = [];

    public function __construct(string $configPath = '')
    {
        if (!empty($configPath)) {
            $this->configPath = $configPath;
        }

        $this->appConfig = yaml_parse_file($this->configPath);
    }
    public function getPluginDir(): ?string
    {
        if (isset($this->appConfig['ecw_app_config']['plugin_dir'])) {
            return $this->appConfig['ecw_app_config']['plugin_dir'];
        }

        return null;
    }

    public function getInstalledPlugins(): ?array
    {
        if (isset($this->appConfig['ecw_app_config']['properties']['plugins'])) {
            return $this->appConfig['ecw_app_config']['properties']['plugins'];
        }

        return null;
    }

    public function getPluginConfig(string $pluginName): ?array
    {
        return $this->appConfig['ecw_app_config']['properties']['plugins'][$pluginName] ?? null;
    }

    /**
     * @throws ECWatchException
     */
    public function addPlugin(string $pluginName): bool
    {
        if (!isset($this->appConfig['ecw_app_config']['properties']['plugins'][$pluginName])) {
            $this->appConfig['ecw_app_config']['properties']['plugins'][$pluginName] = [
                'id' => $pluginName,
            ];
            file_put_contents($this->configPath, yaml_emit($this->appConfig));
            return true;
        }

        throw new  ECWatchException('Plugin already exists');
    }
}
