<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Application;

use Dantweb\Ecommwatch\Framework\Application\PluginManager;
use Dantweb\Ecommwatch\Framework\Application\PluginInterface;
use PHPUnit\Framework\TestCase;

class PluginManagerTest extends TestCase
{
    use AppTrait;

    public function testGetAvailablePluginsReturnsArray()
    {
        $app = new PluginManager();
        $available = $app->getAvailablePlugins();
        $this->assertIsArray($available);
    }

    public function testGetInstalledPluginsReturnsPluginArray()
    {
        $app = new PluginManager();
        $installed = $app->getInstalledPlugins();

        $this->assertIsArray($installed);
        foreach ($installed as $plugin) {
            $this->assertInstanceOf(PluginInterface::class, $plugin);
        }
    }

    public function testGetPluginObjReturnsCorrectInstance()
    {
        $app = new PluginManager();
        $installed = $app->getInstalledPlugins();
        if (!empty($installed)) {
            $plugin = $installed[0];
            $pluginObj = $app->getPluginObj($plugin->getId());

            $this->assertInstanceOf(PluginInterface::class, $pluginObj);
        } else {
            $this->markTestSkipped('No installed plugins found.');
        }
    }

    public function testGetAllMigratedEcwModels()
    {

        $app = new PluginManager();
        $models = $app->getAllMigratedEcwModels();

        $this->assertIsArray($models);
    }
}
