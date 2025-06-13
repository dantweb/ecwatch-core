<?php

namespace Dantweb\Atomizer\Map;

use Dantweb\Atomizer\EcwModel\AbstractEcwModel;

abstract class AbstractMap extends AbstractEcwModel implements MapInterface
{

    public function getTargetFieldName(string $srcFieldName): ?string
    {
        return $this->fields[$srcFieldName]['target_name'] ?? null;
    }

    public function getTargetType(string $srcFieldName): string
    {
        return $this->fields[$srcFieldName]['target_type'] ?? 'string';
    }

    public function setMap(array $map): void
    {
        $this->fields = $map;
    }

    public function getMap(): array
    {
        return $this->fields;
    }
}