<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Application;

use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Atomizer\Map\MapFactory;
use Dantweb\Atomizer\Map\MapInterface;
use Dantweb\Ecommwatch\Framework\Models\Domain\AbstractDomainModel;
use Dantweb\Ecommwatch\Framework\Models\Domain\DomainModelInterface;
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