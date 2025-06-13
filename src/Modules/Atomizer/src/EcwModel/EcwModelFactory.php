<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\EcwModel;

class EcwModelFactory implements EcwModelFactoryInterface
{

    /**
     * EcwModelExample.yaml content:
     * ecw_data_model:
     *   name: OutputModel
     *   properties:
     *     order_number:
     *       type: string
     *       required: true ## (same as notnull in db)
     *     order_sum:
     *       type: float
     *     order_date:
     *       type: timestamp
     *       autofill: true
     *     order_payed_date:
     *       type: timestamp
     *       autofill: false
     *       required: false
     * @param string $yaml
     * @return void
     * @throws \Exception
     */
    public function createClassFromYaml(string $yaml): void
    {
        $className = $this->getClassName($yaml);
        if (class_exists($className)) {
            throw new \Exception("Class $className already exists");
        }

        eval("class $className extends Danteweb\EcwModels\EcwModels\AbstractEcwModel{}");
    }

    public function createAnonymousEcwModel(array $yaml): EcwModelInterface
    {
        return new class(
            $yaml['ecw_data_model']['name'],
            $yaml['ecw_data_model']['properties']
        ) extends AbstractEcwModel {};
    }

    public function createModelFromYamlFileContent(string $yamlFileContents): EcwModelInterface
    {
        $yaml = yaml_parse($yamlFileContents);
        $model = $yaml['ecw_data_model']['name'];
        return new $model();
    }

    public function createModelFromAbsPath(string $absPath): ?EcwModelInterface
    {
        try {
            $model = $this->createModelFromYaml(file_get_contents($absPath));
        } catch (\Exception $e) {
            return null;
        }

        return $model;
    }

    public function createModelFromYaml(string $yaml): ?EcwModelInterface
    {
        $parsed = yaml_parse($yaml);

        try {
            return $this->createAnonymousEcwModel($parsed);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getClassName(string $yaml)
    {
        $yaml = yaml_parse($yaml);

        if (!empty($yaml['ecw_data_model']['name'])) {
            return $yaml['ecw_data_model']['name'];
        }

        throw new \Exception(
            "EcwModelFactory::getClassName: ecw_data_model.name not found in yaml"
        );
    }
}