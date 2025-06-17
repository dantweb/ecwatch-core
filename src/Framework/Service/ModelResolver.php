<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service;

use Dantweb\Ecommwatch\Framework\Application\PluginManager;
use Dantweb\Ecommwatch\Framework\Exception\EcwException;
use Dantweb\Ecommwatch\Framework\Models\Domain\AbstractDomainModel;
use Dantweb\Ecommwatch\Framework\Models\Domain\DomainModelInterface;
use DateTime;

class ModelResolver
{
    protected static string $configPath = '';

    /**
     * Main method that orchestrates parsing the value string, locating the domain model,
     * checking the field name, and calling the requested method.
     */
    public static function resolve(string $value, DateTime $start, DateTime $end, string $resolution): float
    {
        $multiplier = 1.0;

        $parseResult = self::parseValue($value, $multiplier);
        if ($parseResult === null) {
            return 0.0;
        }

        [$modelName, $method, $fieldName] = $parseResult;

        $domainModel = self::findDomainModel($modelName);
        if ($domainModel === null) {
            return 0.0;
        }

        if ($fieldName !== '' && !self::fieldExists($domainModel, $fieldName)) {
            throw new EcwException("Field '{$fieldName}' not found in '{$modelName}'.");
        }

        try {
            $result = self::callDomainMethod($domainModel, $method, $fieldName, $start, $end, $resolution);
        } catch (EcwException $e) {
            return 0.0;
        }

        return is_numeric($result) ? ((float)$result * $multiplier) : 0.0;
    }

    /**
     * Splits the input $value into:
     *  - $modelName (BaseOrderModel)
     *  - $segment (e.g. "count", "sum")
     *  - $fieldName (possibly empty)
     * Also applies any “multiplier” logic if present.
     *
     * Returns an array [$modelName, $segment, $fieldName] or null on failure.
     */
    private static function parseValue(string $value, float &$multiplier): ?array
    {
        $parts = explode('.', $value);

        if (count($parts) < 2) {
            return null;
        }

        $modelName = array_shift($parts);

        $segment = '';
        $fieldName = '';

        if (count($parts) === 1) {
            // e.g. "BaseOrderModel.count()" OR "BaseOrderModel.sum(order_sum)"
            [$segment, $fieldName] = self::parseFinalSegment($parts[0], $multiplier);
            if ($segment === null) {
                return null;
            }
        } elseif (count($parts) === 2) {
            // e.g. "BaseOrderModel.field_name.sum()"
            $fieldName = $parts[0];
            [$segment, $possibleField] = self::parseFinalSegment($parts[1], $multiplier);
            if ($segment === null) {
                return null;
            }
            // If the user wrote sum(order_sum), override $fieldName with 'order_sum'.
            if (!empty($possibleField)) {
                $fieldName = $possibleField;
            }
        } else {
            // More than 3 segments is outside our current pattern.
            return null;
        }

        // If the method is “count”, then the field is empty by definition.
        if ($segment === 'count') {
            $fieldName = '';
        }

        return [$modelName, $segment, $fieldName];
    }

    /**
     * Helper that parses a segment like "count()", "sum(order_sum)", or "sum()".
     * Returns [string $segment, string $fieldName] or [null, ''] on parse failure.
     *
     * Example:
     *   parseFinalSegment("count()", $mult) => ["count", ""]
     *   parseFinalSegment("sum(order_sum)", $mult) => ["sum", "order_sum"]
     */
    private static function parseFinalSegment(string $segmentString, float &$multiplier): array
    {
        // e.g. matches "sum(...)" capturing 'sum' as group1, and '...' as group2
        if (preg_match('/^(\w+)\((.*?)\)$/', $segmentString, $m)) {
            $segment = self::getSegment($m[1], $multiplier);
            $field = trim($m[2] ?? '');
            return [$segment, $field];
        }

        // If no parentheses, check if it ends with "()", e.g. "count()"
        if (str_ends_with($segmentString, '()')) {
            $methodName = rtrim($segmentString, '()');
            $segment = self::getSegment($methodName, $multiplier);
            return [$segment, ''];
        }

        // Could not parse
        return [null, ''];
    }

    /**
     * Searches all migrated EcwModels for one matching $modelName,
     * and returns an AbstractDomainModel instance or null if not found.
     */
    private static function findDomainModel(string $modelName): ?AbstractDomainModel
    {
        $models = (new PluginManager(self::$configPath))->getAllMigratedEcwModels();

        foreach ($models as $model) {
            if ($model->getModelName() === $modelName) {
                return AbstractDomainModel::fromEcwModel($model);
            }
        }
        return null;
    }

    /**
     * Calls $segment($fieldName, $startTS, $endTS, $resolution) on $domainModel.
     */
    private static function callDomainMethod(
        DomainModelInterface $domainModel,
        string $method,
        string $fieldName,
        DateTime $start,
        DateTime $end,
        string $resolution
    ): float|int {
        // Convert to timestamps before passing
        $startTS = $start->getTimestamp();
        $endTS   = $end->getTimestamp();

        // For demonstration, map 'new' => 'newCount', if you have that logic.
        if ($method === 'new') {
            $method = 'newCount';
        }

        return $domainModel->$method($fieldName, $startTS, $endTS, $resolution);
    }

    /**
     * Example placeholder method for checking if a field exists.
     * You can adapt this to your model’s capabilities.
     */
    private static function fieldExists(AbstractDomainModel $domainModel, string $fieldName): bool
    {
        // If your model has an actual “hasField($fieldName): bool” method:
        if (method_exists($domainModel, 'hasField')) {
            return $domainModel->hasField($fieldName);
        }
        // Fallback: always true if hasField doesn’t exist
        return true;
    }

    /**
     * Converts a segment string into a method name, possibly adjusting $multiplier.
     * Example: A trailing 's' might double the multiplier, etc.
     */
    private static function getSegment(string $segment, float &$multiplier): string
    {
        if (str_ends_with($segment, 's')) {
            $multiplier *= 2.0;
            $segment = rtrim($segment, 's');
        }
        return $segment;
    }
}
