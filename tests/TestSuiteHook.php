<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit;

use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\AfterLastTestHook;

class TestSuiteHook implements BeforeFirstTestHook, AfterLastTestHook
{
    use DemoDataImportTrait;
    public function setUpBeforeClass(): void
    {
        $this->doMigrations();
        $this->importDemoData();
    }

    public function setUpAfterClass(): void
    {
//        $this->cleanUp();
    }
}