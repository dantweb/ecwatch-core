<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Model;

use Dantweb\Ecommwatch\Framework\Exception\ECWatchException;
use Dantweb\Ecommwatch\Framework\Service\ModelResolver;
use Dantweb\Ecommwatch\Tests\Unit\DemoDataImportTrait;
use DateTime;
use PHPUnit\Framework\TestCase;

class ModelResolverTest extends TestCase
{
    use DemoDataImportTrait;


    /**
     * @throws ECWatchException
     */
    public function testOrderCount2(): void
    {
        $start = DateTime::createFromFormat('d.m.Y H:i:s', '01.01.2024 00:00:00');
        $end   = DateTime::createFromFormat('d.m.Y H:i:s', '03.01.2024 00:00:00');
        $result = ModelResolver::resolve('BaseOrderModel.count()', $start, $end, 'daily');
        $this->assertEquals(9.0, $result);
    }

    public function testOrderCount5(): void
    {
        $start = DateTime::createFromFormat('d.m.Y H:i:s', '01.01.2024 00:00:00');
        $end   = DateTime::createFromFormat('d.m.Y H:i:s', '06.01.2024 00:00:00');
        $result = ModelResolver::resolve('BaseOrderModel.count()', $start, $end, 'daily');

        $this->assertEquals(25.0, $result, 'Count should be 5');
    }

    /**
     * @throws ECWatchException
     */
    public function testOrderCountW(): void
    {
        $start = DateTime::createFromFormat('d.m.Y H:i:s', '01.01.2024 00:00:00');
        $end = DateTime::createFromFormat('d.m.Y H:i:s', '07.01.2024 00:00:00');
        $result = ModelResolver::resolve('BaseOrderModel.count()', $start, $end, 'weekly');
        $this->assertEquals(29.0, $result);
    }

    /**
     * @throws ECWatchException
     */
    public function testOrderBrutto(): void
    {
        $start = DateTime::createFromFormat('d.m.Y H:i:s', '01.01.2024 00:00:00');
        $end   = DateTime::createFromFormat('d.m.Y H:i:s', '04.01.2024 00:00:00');
        $result = ModelResolver::resolve('BaseOrderModel.order_brutto.sum()', $start, $end, 'daily');
        $this->assertEquals(807.38, $result);

        $result = ModelResolver::resolve('BaseOrderModel.sum(order_brutto)', $start, $end, 'daily');
        $this->assertEquals(807.38, $result);
    }

    public function testOrdersBruttoPlural(): void
    {
        $start = DateTime::createFromFormat('d.m.Y H:i:s', '01.01.2024 00:00:00');
        $end   = DateTime::createFromFormat('d.m.Y H:i:s', '02.01.2024 00:00:00');
        $result = ModelResolver::resolve('BaseOrderModel.sum(order_brutto)', $start, $end, 'daily');
        $this->assertEquals(251.82, $result);
    }

    public function testUsersNew(): void
    {
        $start = DateTime::createFromFormat('d.m.Y H:i:s', '01.01.2024 00:00:00');
        $end   = DateTime::createFromFormat('d.m.Y H:i:s', '04.01.2024 00:00:00');
        $result = ModelResolver::resolve('BaseOrderModel.max(order_brutto)', $start, $end, 'daily');
        $this->assertEquals(58.82, $result);
    }

    public function testOrdersNetto(): void
    {
        $start = DateTime::createFromFormat('d.m.Y H:i:s', '01.01.2024 00:00:00');
        $end   = DateTime::createFromFormat('d.m.Y H:i:s', '02.01.2024 00:00:00');
        $result = ModelResolver::resolve('BaseOrderModel.avg(order_brutto)', $start, $end, 'daily');
        $this->assertEquals(50.364, $result);

        $result = ModelResolver::resolve('BaseOrderModel.count()', $start, $end, 'daily');
        $this->assertEquals(5.0, $result);

        $result = ModelResolver::resolve('BaseOrderModel.sum(order_brutto)', $start, $end, 'daily');
        $this->assertEquals(251.82, $result);
    }
}
