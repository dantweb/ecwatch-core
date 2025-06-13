<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\TimeSeries;

use DateTime;

interface TimeSeriesGeneratorInterface
{
    public function generate(DateTime $start, DateTime $end): array;
}
