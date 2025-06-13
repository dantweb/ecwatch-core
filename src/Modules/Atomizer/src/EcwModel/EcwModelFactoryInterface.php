<?php

namespace Dantweb\Atomizer\EcwModel;

interface EcwModelFactoryInterface
{
    /**
     * Parse the YAML content and create a dynamic class based on its definition.
     *
     * @param string $yaml
     * @return void
     * @throws \Exception If the class already exists.
     */
    public function createClassFromYaml(string $yaml): void;

    /**
     * Create an anonymous ECW model dynamically based on a provided YAML array.
     *
     * @param array $yaml
     * @return EcwModelInterface
     */
    public function createAnonymousEcwModel(array $yaml): EcwModelInterface;

    /**
     * Parse the YAML file content and create a named ECW model based on its definition.
     *
     * @param string $yamlFileContents
     * @return EcwModelInterface
     */
    public function createModelFromYamlFileContent(string $yamlFileContents): EcwModelInterface;

    /**
     * Create an ECW model from the absolute file path of a YAML file.
     *
     * @param string $absPath
     * @return EcwModelInterface|null Returns null in case of errors.
     */
    public function createModelFromAbsPath(string $absPath): ?EcwModelInterface;

    /**
     * Parse the YAML data string and create an ECW model if possible.
     *
     * @param string $yaml
     * @return EcwModelInterface|null Returns null in case of errors.
     */
    public function createModelFromYaml(string $yaml): ?EcwModelInterface;
}