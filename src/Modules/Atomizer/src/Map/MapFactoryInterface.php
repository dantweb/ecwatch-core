<?php

namespace Dantweb\Atomizer\Map;

interface MapFactoryInterface
{
    public function create(array $yaml): MapInterface;
}