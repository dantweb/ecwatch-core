<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\Expression;

class ValueNode extends ExpressionNode
{
    private string $value;
    public function __construct(string $value)
    {
        $this->value = trim($value);
    }
    public function getValue(): string
    {
        return $this->value;
    }
}
