<?php

namespace Dantweb\Atomizer\Map;

interface MapInterface
{
    public function getTargetFieldName(string $srcFieldName): ?string;

    public function getTargetType(string $srcFieldName): string;
}