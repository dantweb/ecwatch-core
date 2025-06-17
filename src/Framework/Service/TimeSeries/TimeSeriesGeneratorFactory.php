<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\TimeSeries;

use InvalidArgumentException;

class TimeSeriesGeneratorFactory
{
    /** @var array|string[]  */
    private array $functions = ['total', 'avg', 'min', 'max'];

    public function create(string $functionName): TimeSeriesGeneratorInterface
    {
        $functionName = strtolower($functionName);
        if ($functionName === 'daily') {
            return new DailyGenerator();
        }
        if ($functionName === 'weekly') {
            return new WeeklyGenerator();
        }
        if ($functionName === 'monthly') {
            return new MonthlyGenerator();
        }
        if ($functionName === 'quarterly') {
            return new QuarterlyGenerator();
        }
        if (in_array($functionName, $this->functions, true)) {
            return new ScalarGenerator($functionName);
        }
        throw new InvalidArgumentException(
            "Unsupported time resolution: {$functionName}"
        );
    }
}
