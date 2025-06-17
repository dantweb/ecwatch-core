<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\TimeSeries;

use Dantweb\Ecommwatch\Framework\Exception\ExpressionTimeSpaceException;
use DateTime;

class WeeklyGenerator implements TimeSeriesGeneratorInterface
{
    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array<int|string, DateTime>
     * @throws ExpressionTimeSpaceException
     * @throws \DateMalformedStringException
     */
    public function generate(DateTime $start, DateTime $end): array
    {
        $interval = $start->diff($end);
        if ($interval->days < 7) {
            throw new ExpressionTimeSpaceException('Insufficient time span');
        }
        $result = [];
        $current = clone $start;
        while ($current < $end) {
            $result[(string)$current->getTimestamp()] = clone $current;
            $current->modify('+1 week');
        }
        return $result;
    }
}
