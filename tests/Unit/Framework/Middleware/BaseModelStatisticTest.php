<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Model;

use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Models\Domain\AbstractDomainModel;
use Dantweb\Ecommwatch\Tests\Unit\DemoDataImportTrait;
use Dantweb\Ecommwatch\Tests\Unit\Framework\Middleware\DemoDataImport;
use PHPUnit\Framework\TestCase;
use DateTime;


class BaseModelStatisticTest extends TestCase
{
    use DemoDataImportTrait;

    protected function setUp(): void
    {
        $this->init();
        $this->doMigrations();
        $this->importDemoData();
    }

    public function testTotalOrderBrutto(): void
    {
        $startDate = new DateTime('2024-01-01 00:00:00');
        $endDate = new DateTime('2024-02-05'); // Covers all 100 days
        $start = $startDate->getTimestamp();
        $end = $endDate->getTimestamp();
        $total = AbstractDomainModel::fromEcwModel($this->ecwModel)->sum('order_brutto', $start, $end);//
        // Sum of 1 to 100
        $this->assertEquals(18606.92, $total);
    }

    public function testAverageOrderBrutto(): void
    {
        $startDate = new DateTime('2024-01-01 00:00:00');
        $endDate = new DateTime('2024-01-10 23:59:59');
        $start = $startDate->getTimestamp();
        $end = $endDate->getTimestamp();

        $avg = AbstractDomainModel::fromEcwModel($this->ecwModel)->avg('order_brutto', $start, $end);
        $this->assertEqualsWithDelta(52.55306122448981, $avg, 0.00001);
//        $this->assertEquals(52.5530612244898, $avg);
    }

    public function testMaxOrderBrutto(): void
    {
        $startDate = new DateTime('2024-01-01 00:00:00');
        $endDate = new DateTime('2024-02-01 23:59:59');
        $start = $startDate->getTimestamp();
        $end = $endDate->getTimestamp();

        $max = AbstractDomainModel::fromEcwModel($this->ecwModel)->max('order_brutto', $start, $end);
        $this->assertEquals(89.45, $max);
    }

    public function testMinOrderBrutto(): void
    {
        $startDate = new DateTime('2024-01-01 00:00:00');
        $endDate = new DateTime('2024-02-01 23:59:59');
        $start = $startDate->getTimestamp();
        $end = $endDate->getTimestamp();

        $min = AbstractDomainModel::fromEcwModel($this->ecwModel)->min('order_brutto', $start, $end);
        $this->assertEquals(41.13, $min);
    }

    public function testCountOrders(): void
    {
        $startDate = new DateTime('2024-01-01 00:00:00');
        $endDate = new DateTime('2024-01-03 23:59:59');
        $start = $startDate->getTimestamp();
        $end = $endDate->getTimestamp();

        $count = AbstractDomainModel::fromEcwModel($this->ecwModel)->count('', $start, $end, 'daily');
        $this->assertEquals(16, $count);
    }
}