<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service;

use Dantweb\Ecommwatch\Framework\Service\Expression\BinaryOpNode;
use Dantweb\Ecommwatch\Framework\Service\Expression\ExpressionNode;
use Dantweb\Ecommwatch\Framework\Service\Expression\FunctionNode;
use Dantweb\Ecommwatch\Framework\Service\Expression\ValueNode;
use Dantweb\Ecommwatch\Framework\Service\TimeSeries\TimeSeriesGeneratorFactory;
use DateTime;

class ExpressionEvaluator
{
    private TimeSeriesGeneratorFactory $factory;

    public function __construct()
    {
        $this->factory = new TimeSeriesGeneratorFactory();
    }

    public function evaluate(ExpressionNode $node, DateTime $start, DateTime $end): array
    {
        if ($node instanceof ValueNode) {
            $value = $this->evaluateValue($node->getValue(), $start, $end, 'daily');
            return [$start->getTimestamp() => $value];
        }

        if ($node instanceof FunctionNode) {
            $generator = $this->factory->create($node->getName());
            $buckets = $generator->generate($start, $end);
            $result = [];
            foreach ($buckets as $timestamp => $bucketStart) {
                $bucketEnd = $this->advance($bucketStart, $node->getName(), $end);
                $evaluated = $this->evaluate($node->getArgument(), $bucketStart, $bucketEnd);

                if ($node->isVector()) {
                    $result[$timestamp] = is_array($evaluated) ? reset($evaluated) : $evaluated;
                }

                if ($node->isScalar()) {
                    $result[$timestamp] = $this->calculateScalarValue($node, $evaluated);
                }
            }
            return $result;
        }
        if ($node instanceof BinaryOpNode) {
            $leftSeries = $this->evaluate($node->getLeft(), $start, $end);
            $rightSeries = $this->evaluate($node->getRight(), $start, $end);
            return $this->applyOperator($leftSeries, $node->getOperator(), $rightSeries);
        }
        return [];
    }

    private function evaluateValue(string $value, DateTime $start, DateTime $end, string $resolution): float
    {
        if (str_contains($value, '.')) {
            return ModelResolver::resolve($value, $start, $end, $resolution);
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        return 0.0;
    }

    private function advance(DateTime $bucketStart, string $functionName, DateTime $end): DateTime
    {
        $bucketEnd = clone $bucketStart;
        $functionName = strtolower($functionName);
        if ($functionName === 'daily') {
            $bucketEnd->modify('+1 day');
        } elseif ($functionName === 'weekly') {
            $bucketEnd->modify('+1 week');
        } elseif ($functionName === 'monthly') {
            $bucketEnd->modify('+1 month');
        } elseif ($functionName === 'quarterly') {
            $bucketEnd->modify('+3 months');
        } else {
            $bucketEnd = $end;
        }
        if ($bucketEnd > $end) {
            $bucketEnd = clone $end;
        }
        return $bucketEnd;
    }

    private function applyOperator(array $left, string $operator, array $right): array
    {
        $result = [];
        if (count($left) === 1 && count($right) > 1) {
            $scalar = reset($left);
            foreach ($right as $key => $value) {
                $result[$key] = $this->applyOp($scalar, $operator, $value);
            }
        } elseif (count($right) === 1 && count($left) > 1) {
            $scalar = reset($right);
            foreach ($left as $key => $value) {
                $result[$key] = $this->applyOp($value, $operator, $scalar);
            }
        } else {
            foreach ($left as $key => $value) {
                if (isset($right[$key])) {
                    $result[$key] = $this->applyOp($value, $operator, $right[$key]);
                }
            }
        }
        return $result;
    }

    private function applyOp(float $a, string $operator, float $b): float
    {
        if ($operator === '/') {
            return $b == 0.0 ? 0.0 : $a / $b;
        }
        if ($operator === '*') {
            return $a * $b;
        }
        return 0.0;
    }

    private function calculateScalarValue($node, array $evaluated): float
    {
        if (($node->getName() === 'total' || $node->getName() === 'sum') && is_array($evaluated)) {
            return array_sum($evaluated);
        }

        if ($node->getName() === 'avg' && is_array($evaluated)) {
            return array_sum($evaluated) / count($evaluated);
        }

        if ($node->getName() === 'max' && is_array($evaluated)) {
            return max($evaluated);
        }

        if ($node->getName() === 'min' && is_array($evaluated)) {
            return min($evaluated);
        }

        if ($node->getName() === 'count' && is_array($evaluated)) {
            return count($evaluated);
        }

        if ($node->getName() === 'last' && is_array($evaluated)) {
            return end($evaluated);
        }

        if ($node->getName() === 'first' && is_array($evaluated)) {
            return reset($evaluated);
        }
    }
}
