<?php

namespace Dantweb\Ecommwatch\Framework\Helper;

use Psr\Log\AbstractLogger;

class DebugHelper extends AbstractLogger
{

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        // TODO: Implement log() method.
    }
}