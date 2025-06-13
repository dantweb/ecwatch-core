<?php

namespace Dantweb\Atomizer\EcwModel;

interface EcwModelInterface
{
    public function getFields(): array;
    public function __get(string $name): mixed;

    public function __set(string $name, mixed $value): void;

    public function __isset(string $name): bool;

    public function __unset(string $name): void;

    public function __toString(): string;

    public function toArray(): array;

    public function getModelName(): string;
}