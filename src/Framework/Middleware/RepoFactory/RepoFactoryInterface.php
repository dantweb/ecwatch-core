<?php

namespace Dantweb\Ecommwatch\Framework\Middleware\RepoFactory;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Middleware\Repository\RepoInterface;

interface RepoFactoryInterface
{
    public function getRepo(EcwModelInterface $ecwModel): RepoInterface;
}