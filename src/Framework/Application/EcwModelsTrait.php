<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Application;

use App\Modules\Atomizer\src\EcwModel\EcwModelFactory;
use App\Modules\Atomizer\src\EcwModel\EcwModelInterface;
use App\Modules\Atomizer\src\Map\MapFactory;
use App\Modules\Atomizer\src\Map\MapInterface;
use Symfony\Component\Filesystem\Path;

trait EcwModelsTrait
{
    /**
     * @return EcwModelInterface[]
     */
    public function getEcwModelsFromPlugin(string $modelDir): array
    {
        $models = [];

        $files = glob(Path::join($modelDir, '*.yaml'));
        if ($files === false) {
            return $models;
        }

        $factory = new EcwModelFactory();
        foreach ($files as $filePath) {
            $yamlString = file_get_contents($filePath);
            if ($yamlString === false) {
                continue;
            }

            $ecwModel = $factory->createModelFromYaml($yamlString);
            if ($ecwModel instanceof EcwModelInterface) {
                $models[$ecwModel->getModelName()] = $ecwModel;
            }
        }

        return $models;
    }

    public function getEcwMapsFromPlugin(string $mapDir): array
    {
        $maps = [];

        $files = glob(Path::join($mapDir, '*.yaml'));
        if ($files === false) {
            return $maps;
        }

        $mapFactory = new MapFactory();
        foreach ($files as $filePath) {
            $yamlString = file_get_contents($filePath);
            if ($yamlString === false) {
                continue;
            }

            $map = $mapFactory->create(yaml_parse($yamlString));

            if ($map instanceof MapInterface) {
                $maps[] = $map;
            }
        }

        return $maps;
    }
}
