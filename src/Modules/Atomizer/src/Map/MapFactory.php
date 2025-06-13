<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\Map;


use Dantweb\Atomizer\EcwModel\EcwModelFactory;

class MapFactory extends EcwModelFactory implements MapFactoryInterface
{
    public function create(array $yaml): MapInterface
    {
        $fields = $yaml['ecw_data_map'];
        return new class(
            $fields['name'],
            $fields['map']
        ) extends AbstractMap implements MapInterface {
        };
    }
}