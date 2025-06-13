<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\Expression;

class BinaryOpNode extends ExpressionNode
{
    private ExpressionNode $left;
    private string $operator;
    private ExpressionNode $right;

    public function __construct(ExpressionNode $left, string $operator, ExpressionNode $right)
    {
        $this->left = $left;
        $this->operator = trim($operator);
        $this->right = $right;
    }

    public function getLeft(): ExpressionNode
    {
        return $this->left;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getRight(): ExpressionNode
    {
        return $this->right;
    }
}
