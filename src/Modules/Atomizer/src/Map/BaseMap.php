<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\Map;

class BaseMap extends AbstractMap
{
    protected array $map = [
        'table.field_name' => 'trg.table',
        'table.field_type' => 'trg.table',
        'table.field_value' => 'trg.table',
    ];
}