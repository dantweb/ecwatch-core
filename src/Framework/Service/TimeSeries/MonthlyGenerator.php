<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\TimeSeries;

use DateTime;
use Dantweb\Ecommwatch\Framework\Exception\ExpressionTimeSpaceException;

class MonthlyGenerator implements TimeSeriesGeneratorInterface
{
    public function generate(DateTime $start, DateTime $end): array
    {
        $interval = $start->diff($end);
        if ($interval->y === 0 && $interval->m < 1) {
            throw new ExpressionTimeSpaceException('Insufficient time span');
        }
        $result = [];
        $current = clone $start;
        while ($current < $end) {
            $result[(string)$current->getTimestamp()] = clone $current;
            $current->modify('+1 month');
        }
        return $result;
    }
}
