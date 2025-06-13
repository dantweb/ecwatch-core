<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Traits;

use Dantweb\Ecommwatch\Framework\Helper\DebugHelper;

trait DataPathParser
{
    public static function parseDataPath(string $rawField, string $field): ?array
    {
        if (!str_starts_with($rawField, 'raw.')) {
            return null;
        }

        $parts = explode('.', $rawField);
        if (count($parts) < 3) {
            return null;
        }

        if ($parts[1] !== $field) {
            (new DebugHelper())->error('Import RawDataModel::objectName is not configured');
            return null;
        }

        return $parts;
    }
}