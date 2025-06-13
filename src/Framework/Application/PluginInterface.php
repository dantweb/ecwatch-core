<?php

namespace Dantweb\Ecommwatch\Framework\Application;

interface PluginInterface
{
    public function getId(): string;

    public function getVersion(): string;

    public function getMigratedModels(): array;

    public function getEcwModels(): array;

    public function getEcwMaps(string $mapDir): array;

    public function isInstalled(): bool;
}