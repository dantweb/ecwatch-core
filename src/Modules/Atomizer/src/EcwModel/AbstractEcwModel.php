<?php

namespace Dantweb\Atomizer\EcwModel;

use Dantweb\Atomizer\DataPassport\DataPassport;

abstract class AbstractEcwModel implements EcwModelInterface
{
    protected string $name = '';
    protected array $fields = [];
    protected array $data = [];
    protected DataPassport $dataPassport;
    public function __construct(string $name = '', array $fields = [])
    {
        $this->name = $name;
        $this->fields = $fields;
        $this->dataPassport = new DataPassport();
    }
    public function getFields(): array
    {
        return $this->fields;
    }

    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->fields) && isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    public function __set(string $name, mixed $value): void
    {
        if (array_key_exists($name, $this->fields)) {
            $this->data[$name] = $value;
        }
    }

    public function __call(string $name, array $arguments)
    {
        // Handle setter methods like setTestField('value')
        if (str_starts_with($name, 'set')) {
            $fieldNamePortion = substr($name, 3);
            $field = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldNamePortion))));
            $camelCase = strtolower(preg_replace('/(?<!^)(?=[A-Z])/', '_', $field));
            $this->__set($camelCase, $arguments[0] ?? null);
            return;
        }

        // Handle getter methods like getTestIntField()
        if (str_starts_with($name, 'get')) {
            $fieldNamePortion = substr($name, 3);
            $field = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldNamePortion))));
            $camelCase = strtolower(preg_replace('/(?<!^)(?=[A-Z])/', '_', $field));

            // Check if the field exists before returning
            if (array_key_exists($camelCase, $this->fields)) {
                return $this->__get($camelCase);
            }
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    public function set(string $name, mixed $value): void
    {
        $this->__set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->data[$name]);
    }

    public function __toString(): string
    {
        return json_encode($this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getModelName(): string
    {
        return $this->name;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->__get($name) ?? $default;
    }

    public function hasField(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    public function setMappedField(string $name, mixed $value): bool
    {
        [$className, $fieldName] = $this->getFieldMapping($name);
        if ($className && $fieldName) {
            if ($this->getModelName() === $className && $this->hasField($fieldName)) {
                $this->set($fieldName, $value);
                return true;
            }
        } else if($className === null && $fieldName !== null && $this->hasField($fieldName)) {
            $this->set($fieldName, $value);
            return true;
        }

        return false;
    }

    public function getMappedField(string $name): mixed
    {
        [$className, $fieldName] = $this->getFieldMapping($name);
        if ($className && $fieldName) {
            if ($this->getModelName() === $className && $this->hasField($fieldName)) {
                return $this->get($fieldName);
            }
        } else if($className === null && $fieldName !== null && $this->hasField($fieldName)) {
            return $this->get($fieldName);
        }
    }

    public function canHaveMappedField(string $name): bool
    {
        [$className, $fieldName] = $this->getFieldMapping($name);

        if ($className && $fieldName) {
            if ($this->getModelName() === $className && $this->hasField($fieldName)) {
                return true;
            }
        }

        if ($className === null && $this->hasField($name)) {
            return true;
        }

        return false;
    }

    public function getFieldMapping(string $name): array
    {
        $parseFieldName = explode('.', $name);
        $fieldName = $parseFieldName[0];
        $className = null;
        if (isset($parseFieldName[1])) {
            $className = $parseFieldName[0];
            $fieldName = $parseFieldName[1];
        }
        return [$className, $fieldName];
    }

    /**
     * Returns the field name used as primary key: first 'primary', then 'unique', else 'id'
     */
    public function getPrimaryKeyName(): string
    {
        foreach ($this->fields as $name => $meta) {
            if (!empty($meta['primary'])) {
                return $name;
            }
        }

        foreach ($this->fields as $name => $meta) {
            if (!empty($meta['unique'])) {
                return $name;
            }
        }

        return 'id';
    }
}