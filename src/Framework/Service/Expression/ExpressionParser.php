<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\Expression;

class ExpressionParser
{
    public function parse(string $expression): ExpressionNode
    {
        $expression = trim($expression);
        $opPos = $this->findTopLevelOperator($expression, '/');
        if ($opPos !== null) {
            $left = substr($expression, 0, $opPos);
            $right = substr($expression, $opPos + 1);
            return new BinaryOpNode($this->parse($left), '/', $this->parse($right));
        }
        if (preg_match('/^(\w+)\((.*)\)$/', $expression, $matches)) {
            $funcName = $matches[1];
            $inner = $matches[2];
            return new FunctionNode($funcName, $this->parse($inner));
        }
        return new ValueNode($expression);
    }

    private function findTopLevelOperator(string $expression, string $operator): ?int
    {
        $depth = 0;
        $len = strlen($expression);
        for ($i = 0; $i < $len; $i++) {
            $char = $expression[$i];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === $operator && $depth === 0) {
                return $i;
            }
        }
        return null;
    }
}
