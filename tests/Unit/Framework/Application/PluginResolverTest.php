<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Application;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Application\PluginManager;
use PHPUnit\Framework\TestCase;

class PluginResolverTest extends TestCase
{
    use AppTrait;

    public function testInstalledPlugins()
    {
        $app = new PluginManager();
        $installedPlugins = $app->getInstalledPlugins();
        $defaultModelId = 'default_plugin';
        $this->assertNotEmpty($installedPlugins);
        $this->assertCount(1, $installedPlugins);

        $this->assertEquals($defaultModelId, $installedPlugins[0]->getId());

        $defaultPlugin = $app->getPluginObj('default_plugin');
        $this->assertEquals('default_plugin', $defaultPlugin->getId());

        $defaultPluginModels = $defaultPlugin->getEcwModels();
        $firstModel = reset($defaultPluginModels);
        $this->assertArrayHasKey('BaseOrderModel', $defaultPluginModels);
        $this->assertEquals('BaseOrderModel', $firstModel->getModelName());

        $ecwModel = $app->getModelByName('BaseOrderModel');
        $this->assertInstanceOf(EcwModelInterface::class, $ecwModel);
        $this->assertEquals('BaseOrderModel', $ecwModel->getModelName());
    }
}
