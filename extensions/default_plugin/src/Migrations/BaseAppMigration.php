<?php

declare(strict_types=1);

namespace Dantweb\EcwDeafultPlugin\Migrations;

use Dantweb\Ecommwatch\Framework\Middleware\AbstractModelMigrator;

final class BaseAppMigration extends AbstractModelMigrator
{
    /**
     * @throws \Exception
     */
    public function run(): void
    {
        parent::migrate();
    }
}