<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Application;

use Dantweb\Ecommwatch\Framework\Application\BasePlugin;
use PHPUnit\Framework\TestCase;

class BasePluginTest extends TestCase
{
    use AppTrait;

    public function testBasePluginCanBeInstantiated()
    {
        $plugin = new class ('demo_plugin', '1.0') extends BasePlugin {
        };
        $this->assertEquals('demo_plugin', $plugin->getId());
        $this->assertEquals('1.0', $plugin->getVersion());
    }

    public function testGetMigratedModelsReturnsArray()
    {
        $plugin = new class ('demo_plugin') extends BasePlugin {
        };
        $models = $plugin->getMigratedModels();
        $this->assertIsArray($models);
    }

    public function testGetEcwModelsReturnsArray()
    {
        $plugin = new class ('demo_plugin') extends BasePlugin {
        };

        $models = $plugin->getEcwModels();
        $this->assertIsArray($models);
    }

    public function testIsInstalledReturnsBool()
    {
        $plugin = new class ('demo_plugin') extends BasePlugin {
        };

        $this->assertIsBool($plugin->isInstalled());
    }
}
