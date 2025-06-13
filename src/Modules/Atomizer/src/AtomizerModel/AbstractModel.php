<?php

namespace Dantweb\Atomizer\AtomizerModel;

abstract class AbstractModel implements ModelInterface
{
    protected array $data;
    public function __construct()
    {
    }
}