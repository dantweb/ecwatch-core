<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\TimeSeries;

use DateTime;

class DailyGenerator implements TimeSeriesGeneratorInterface
{
    public function generate(DateTime $start, DateTime $end): array
    {
        $result = [];
        $current = clone $start;
        while ($current < $end) {
            $result[(string)$current->getTimestamp()] = clone $current;
            $current->modify('+1 day');
        }
        return $result;
    }
}
