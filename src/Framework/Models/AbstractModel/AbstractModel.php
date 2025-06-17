<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Models\AbstractModel;

use Dantweb\Atomizer\EcwModel\AbstractEcwModel;
use Dantweb\Ecommwatch\Framework\Helper\DebugHelper;
use Dantweb\Ecommwatch\Framework\Helper\Logger;
use Dantweb\Ecommwatch\Framework\Traits\DataPathParser;

abstract class AbstractModel extends AbstractEcwModel implements AbstractInterface
{
    use DataPathParser;

    protected string $className;
    protected string $rawDataObjectName;
    protected DebugHelper $logger;
    protected mixed $dbTableName;

    public function __construct(string $name = '', array $fields = [])
    {
        $this->className = get_class($this);
        parent::__construct($name, $fields);
    }

    public function __destruct()
    {
        unset($this->data);
    }
    public function __toString(): string
    {
        return json_encode($this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function addItem(array $item): void
    {
        $this->data[] = $item;
    }

    public function setRawData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Allowed format for $rawField is like "raw.order.total_brutto",
     * where:
     *  "raw" means - it is data from source,
     *  "order" means - data domain
     *  "total_brutto" - is actual field name in the import i.e. CSV or JSON
     * @param string $rawField
     * @return mixed
     */
    public function getDataByFieldPath(string $rawField): mixed
    {
        $parts = self::parseDataPath($rawField, $this->className);

        $key = $parts[2];

        if (key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        $this->logger->error("Field $rawField expected, but no '$key' was found");

        return null;
    }

    public function setDataByFieldPath(string $name, mixed $value): bool
    {
        $parts = self::parseDataPath($name, $this->className);

        $key = $parts[2];

        if ($this->hasProperty($key)) {
            $this->setField($key, $value);
            return true;
        } else {
            Logger::warn("AbstractImportDataModel::setDataByFieldPath error: $key "
                . "does not exist in the model $this->className ");
        }

        return false;
    }

    public function hasProperty(string $name): bool
    {
        return property_exists($this, $name);
    }

    public function getDbTableName(): string
    {
        return $this->dbTableName;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setField(string $name, mixed $default = null): void
    {
        $this->data->{$name} = $default;
    }

    public function getRawDataObjectName(): string
    {
        return $this->rawDataObjectName;
    }

    public function setRawDataObjectName(string $rawDataObjectName): void
    {
        $this->rawDataObjectName = $rawDataObjectName;
    }

    public function setDbTableName(mixed $dbTableName): void
    {
        $this->dbTableName = $dbTableName;
    }
}
