<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Models\AbstractModel;

interface AbstractInterface
{
    public function __toString(): string;

    public function toArray(): array;

    public function addItem(array $item): void;

    public function setRawData(array $data): void;

    public function getDataByFieldPath(string $rawField): mixed;

    public function setDataByFieldPath(string $name, mixed $value): bool;

    public function hasProperty(string $name): bool;

    public function getDbTableName(): string;

    public function getClassName(): string;

    public function setField(string $name, mixed $default = null): void;
}