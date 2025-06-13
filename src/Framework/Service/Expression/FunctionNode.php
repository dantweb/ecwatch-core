<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service\Expression;

class FunctionNode extends ExpressionNode
{
    protected array $availableVectors = ['daily', 'weekly', 'monthly', 'yearly', 'hourly'];
    protected array $availableScalars = ['min', 'max', 'sum', 'total', 'avg', 'count', 'first', 'last'];

    protected string $name;
    protected ExpressionNode $argument;

    public function __construct(string $name, ExpressionNode $argument)
    {
        $this->name = strtolower(trim($name));
        $this->argument = $argument;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getArgument(): ExpressionNode
    {
        return $this->argument;
    }

    public function isVector(): bool
    {
        return in_array($this->name, $this->availableVectors);
    }

    public function isScalar(): bool
    {
        return in_array($this->name, $this->availableScalars);
    }
}
