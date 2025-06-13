<?php

declare(strict_types=1);

namespace App\src\Modules\Atomizer\tests\Unit\EcwModel;

use Dantweb\Atomizer\Tests\Unit\EcwModel\BaseUnitTestCase;
use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\EcwModel\EcwModelInterface;

class EcwModelFactoryTest extends BaseUnitTestCase
{
    private string $modelConfigPath = __DIR__ . '/../../_test_data/TestModelConfig.yaml';
    private string $csv = __DIR__ . '/../../_test_data/test_model_raw_data.csv';

    public function testModelConfig()
    {
        $yaml = $this->getYamlModelConfig();
        $testModel = (new EcwModelFactory)->createModelFromYaml($yaml);
        $this->assertTrue($testModel->hasField('order_brutto'));
        $this->assertTrue($testModel->hasField('order_number'));
        $this->assertTrue($testModel->hasField('order_date'));

        $this->assertTrue($testModel instanceof EcwModelInterface);
    }
    private function getYamlModelConfig(): string
    {
        return file_get_contents($this->modelConfigPath);
    }
}