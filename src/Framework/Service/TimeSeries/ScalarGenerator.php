<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\TimeSeries;

use DateTime;

class ScalarGenerator implements TimeSeriesGeneratorInterface
{
    public function __construct(protected string $name)
    {
    }

    public function generate(DateTime $start, DateTime $end): array
    {
        return [(string)$start->getTimestamp() => $start];
    }
}
