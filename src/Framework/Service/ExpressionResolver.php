<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service;

use Dantweb\Ecommwatch\Framework\Service\Expression\ExpressionParser;
use DateTime;

class ExpressionResolver
{
    private ExpressionParser $parser;

    private ExpressionEvaluator $evaluator;

    public function __construct()
    {
        $this->parser = new ExpressionParser();
        $this->evaluator = new ExpressionEvaluator();
    }

    public function resolve(string $expression, string $basis, string $end): array
    {
        $ast = $this->parser->parse($expression);
        $start = DateTime::createFromFormat('d.m.Y', $basis);
        $finish = DateTime::createFromFormat('d.m.Y', $end);

        if (!$start || !$finish) {
            throw new \InvalidArgumentException('Invalid date format. Expected format is d.m.Y');
        }

        return $this->evaluator->evaluate($ast, $start, $finish);
    }
}
