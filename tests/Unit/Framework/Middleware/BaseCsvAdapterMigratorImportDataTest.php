<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Middleware;

use Dantweb\Ecommwatch\Tests\Unit\DemoDataImportTrait;
use PHPUnit\Framework\TestCase;

class BaseCsvAdapterMigratorImportDataTest extends TestCase
{
    use DemoDataImportTrait;

    // List the tables that the migration creates.
    protected static array $tablesToCleanup = [
        'BaseOrderModel',
        'CustomerModel',
        'ShipmentModel',
    ];

    public function setUp(): void
    {
        $this->init();
        $this->doMigrations();
        $this->importDemoData();
    }

    public function testImportedDemoDataContainsCorrectValues(): void
    {
        $testRepo = $this->repoFactory->getRepo($this->ecwModel);

        $all = $testRepo->findAll();
        $importedOrder = $all[0];

        $this->assertEquals('ORD-0001', $importedOrder->getOrderNumber());
        $this->assertEquals(51.32, $importedOrder->getOrderBrutto());
        $this->assertEquals(6, $importedOrder->getOrderItemsCount());
        $this->assertEquals('Cash', $importedOrder->getOrderPaymentMethodId());
        $this->assertEquals('2024-01-01 21:38:57', $importedOrder->getTimestamp());

        // Additional repository method tests
        $foundByNumber = $testRepo->findBy(['order_number' => 'ORD-0001']);
        $this->assertCount(1, $foundByNumber);
        $this->assertEquals('ORD-0001', $foundByNumber[0]->getOrderNumber());

        // Test finding by multiple conditions
        $foundByConditions = $testRepo->findBy([
            'order_number' => 'ORD-0001'
        ]);

        $this->assertCount(1, $foundByConditions);
    }
}