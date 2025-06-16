<?php

namespace Dantweb\Ecommwatch\Framework\Middleware\RepoFactory;

use App\Modules\Atomizer\src\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Middleware\Repository\RepoInterface;

interface RepoFactoryInterface
{
    public function getRepo(EcwModelInterface $ecwModel): RepoInterface;
}
