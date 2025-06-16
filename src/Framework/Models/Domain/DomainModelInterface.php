<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Models\Domain;

interface DomainModelInterface
{
    public function count(string $field, int $start, int $end, string $resolution): int;
    public function total(string $field, int $start, int $end): float;
    public function sum(string $field, int $start, int $end): float;
    public function max(string $field, int $start, int $end): float;
    public function min(string $field, int $start, int $end): float;
    public function avg(string $field, int $start, int $end): float;
    public function movavg(string $field, int $start, int $end, string $resolution): float;
    public function last(string $field, int $start, int $end): float;
    public function first(string $field, int $start, int $end): float;
    public function weightAvg(string $field, int $start, int $end, string $resolution): float;
    public function disp(string $field, int $start, int $end, string $resolution): float;
    public function relDisp(string $field, int $start, int $end, string $resolution): float;
    public function avgSqrVariation(string $field, int $start, int $end): float;
}
