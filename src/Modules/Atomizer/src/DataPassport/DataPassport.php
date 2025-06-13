<?php

namespace Dantweb\Atomizer\DataPassport;

class DataPassport
{
    protected static int $timestamp;
    protected string $srcFieldName;
    protected string $trgFieldName;
    protected string $srcFieldType;
    protected string $trgFieldType;
    protected string $srcTableName;

    public function __construct()
    {
        self::$timestamp = time();
    }
    public function getTimestamp(): int
    {
        return self::$timestamp;
    }

    public function getSrcFieldName(): string
    {
        return $this->srcFieldName;
    }

    public function setSrcFieldName(string $srcFieldName): void
    {
        $this->srcFieldName = $srcFieldName;
    }

    public function getTrgFieldName(): string
    {
        return $this->trgFieldName;
    }

    public function setTrgFieldName(string $trgFieldName): void
    {
        $this->trgFieldName = $trgFieldName;
    }

    public function getSrcFieldType(): string
    {
        return $this->srcFieldType;
    }

    public function setSrcFieldType(string $srcFieldType): void
    {
        $this->srcFieldType = $srcFieldType;
    }

    public function getTrgFieldType(): string
    {
        return $this->trgFieldType;
    }

    public function setTrgFieldType(string $trgFieldType): void
    {
        $this->trgFieldType = $trgFieldType;
    }

    public function getSrcTableName(): string
    {
        return $this->srcTableName;
    }

    public function setSrcTableName(string $srcTableName): void
    {
        $this->srcTableName = $srcTableName;
    }
}