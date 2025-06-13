<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Helper;

class Logger
{
    public static function log($level, $message, array $context = []): void
    {
        (new DebugHelper())->log($level, $message, $context);
    }

    public static function warn($message, array $context = []): void
    {
        (new DebugHelper())->log('WARNING', $message, $context);
    }

    public static function error($message, array $context = []): void
    {
        (new DebugHelper())->log('ERROR', $message, $context);
    }

    public static function info($message, array $context = []): void
    {
        (new DebugHelper())->log('INFO', $message, $context);
    }
}