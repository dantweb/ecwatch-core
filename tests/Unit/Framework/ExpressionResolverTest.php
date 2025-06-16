<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework;

use Dantweb\Ecommwatch\Framework\Exception\ExpressionTimeSpaceException;
use Dantweb\Ecommwatch\Framework\Service\ExpressionResolver;
use Dantweb\Ecommwatch\Tests\Unit\BaseTestCase;
use Dantweb\Ecommwatch\Tests\Unit\DemoDataImportTrait;
use DateTime;
use PHPUnit\Framework\TestCase;

class ExpressionResolverTest extends TestCase
{
    use DemoDataImportTrait;

    private ExpressionResolver $expressionResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->init();
        $this->importDemoData();
        $this->expressionResolver = new ExpressionResolver();
    }

    // Helper that computes expected bucket count based on outer function.
    private function expectedCount(string $expression, DateTime $start, DateTime $end): int
    {
        $trim = ltrim($expression);
        if (preg_match('/^(daily|weekly|monthly|quarterly)/i', $trim, $matches)) {
            $fn = strtolower($matches[1]);
            $count = -1;
            $current = clone $start;
            if ($fn === 'daily') {
                while ($current <= $end) {
                    $count++;
                    $current->modify('+1 day');
                }
            } elseif ($fn === 'weekly') {
                while ($current <= $end) {
                    $count++;
                    $current->modify('+1 week');
                }
            } elseif ($fn === 'monthly') {
                while ($current <= $end) {
                    $count++;
                    $current->modify('+1 month');
                }
            } elseif ($fn === 'quarterly') {
                while ($current <= $end) {
                    $count++;
                    $current->modify('+3 months');
                }
            } else {
                while ($current <= $end) {
                    $count++;
                    $current->modify('+1 day');
                }
            }
            return $count;
        }
        // Default to daily if no function detected.
        $count = -1;
        $current = clone $start;
        while ($current <= $end) {
            $count++;
            $current->modify('+1 day');
        }
        return $count;
    }

    // Helper that runs the resolve() and checks expected count and value.
    private function finishTest(
        string $expression,
        string $basis,
        string $end,
        ?array $expectedArrayValues = null
    ): array {
        $result = $this->expressionResolver->resolve($expression, $basis, $end);
        $startDate = DateTime::createFromFormat('d.m.Y', $basis);
        $endDate   = DateTime::createFromFormat('d.m.Y', $end);
        $expectedCount = $this->expectedCount($expression, $startDate, $endDate);

        $this->assertCount(
            $expectedCount,
            $result,
            sprintf('Expected %d buckets, got %d', $expectedCount, count($result))
        );

        foreach ($result as $value) {
            $this->assertIsFloat($value);
            $this->assertTrue($value >= 0);
        }

        if ($expectedArrayValues === null) {
            return $result;
        }

        $this->assertCount(count($expectedArrayValues), $result, 'Array length differs from expected');

        $actualValues   = array_values($result);
        $actualValues = array_map(function ($value) {
            return is_numeric($value) ? round($value, 2) : $value;
        }, $actualValues);

        $expectedValues = array_values($expectedArrayValues);

        $this->assertCount(count($expectedValues), $actualValues);

        $this->assertEqualsWithDelta(
            $expectedValues,
            $actualValues,
            0.1,
            'Array values differ or order is incorrect.'
        );

        return $result;
    }

    public function testDailyExpressionReturnsCorrectCount(): void
    {
        $expression = 'daily(BaseOrderModel.count())';
        $basis      = '01.01.2024';
        $end        = '03.01.2024';
        // Expect each bucket = 10.0 (from mapping)
        $result = $this->finishTest($expression, $basis, $end);
        $this->assertCount(2, $result);
        $this->assertEquals(3.0, reset($result));
        $this->assertNotEmpty($result);
    }

    public function testMonthExpressionThrowsExceptionOnInsufficientTimespan(): void
    {
        $expression = 'weekly(BaseOrderModel.count())';
        $basis      = '01.01.2024';
        $end        = '05.01.2024';
        $this->expectException(ExpressionTimeSpaceException::class);
        $this->expectExceptionMessage('Insufficient time span');
        $result = $this->expressionResolver->resolve($expression, $basis, $end);
        $this->assertNull($result);
    }

    public function testComposableExpressions1(): void
    {
        $expression = 'daily( BaseOrderModel.sum(order_brutto) ) / daily( BaseOrderModel.count() ) )';
        $basis      = '01.02.2024';
        $end        = '07.02.2024';

        $expected = [
            76.97,
            78.67,
            73.86,
            79.45,
            77.36,
            79.42
        ];
        $this->finishTest($expression, $basis, $end, $expected);
    }

    public function testComposableExpressions2(): void
    {
        $expression = 'weekly( BaseOrderModel.avg( order_brutto ) )';
        $basis      = '01.01.2024';
        $end        = '15.01.2024';

        $expected = [51.58, 58.86];
        $this->finishTest($expression, $basis, $end, $expected);
    }

    public function testComposableExpressions3(): void
    {
        $expression = 'daily( BaseOrderModel.min(order_brutto) / 100 ) ';
        $basis      = '01.01.2024';
        $end        = '05.01.2024';

        $expected = [0.49, 0.44, 0.49,  0.43];

        $this->finishTest($expression, $basis, $end, $expected);
    }

    public function testComposableExpressions4(): void
    {
        $expression = 'monthly(BaseOrderModel.sum(order_brutto))';
        $basis      = '03.01.2024';
        $end        = '03.04.2024';

        $expected = [16099.51, 52173.52, 105920.42];
        $this->finishTest($expression, $basis, $end, $expected);
    }

    public function testComposableExpressions5(): void
    {
        $this->markTestSkipped('Not implemented yet');
        $expression = 'total( daily( BaseOrderModel.sum(order_brutto) ) )';
        $basis      = '10.03.2024';
        $end        = '15.03.2024';

        // we are expecting 1 value
        $result = $this->expressionResolver->resolve($expression, $basis, $end);

        $this->assertCount(1, $result);
        $this->assertEquals(15368.0, reset($result));

        $expression = 'daily( BaseOrderModel.sum(order_brutto) ) / total( daily( BaseOrderModel.sum(order_brutto) ) )';
        $this->assertCount(5, $result);

        $expected = [
            0.20094,
            0.18782,
            0.21379,
            0.20006,
            0.19737
        ];

        $this->finishTest($expression, $basis, $end, $expected);

        $expression = 'daily( BaseOrderModel.max(order_brutto) ) / daily( BaseOrderModel.avg(order_brutto) )';
        $result = $this->finishTest($expression, $basis, $end);
    }
}
