<?php

namespace Dantweb\Ecommwatch\Framework\Middleware\Repository;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;

interface RepoInterface
{
    public function findAll(): array;

    public function findOne(int $id): ?EcwModelInterface;

    public function findById(int $id): ?EcwModelInterface;

    public function findRange(int $start, int $end): array;

    public function save(EcwModelInterface $ecwModel): void;

    public function delete(int $id): void;

    public function update(EcwModelInterface $ecwModel): void;

    public function insert(EcwModelInterface $ecwModel): void;

    public function count(): int;

    public function getEcwModel(): EcwModelInterface;

    public function where(string $field, string $operator, mixed $value): ?EcwModelInterface;

    public function setWritingMode(string $mode): void;
}
