<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\AtomizerModel;

use Dantweb\Atomizer\DataPassport\DataPassport;
use Dantweb\Ecommwatch\Framework\Exception\ECWatchException;

class Atom extends AbstractModel
{
    protected DataPassport $dataPassport;

    public function __construct(
        protected string $table = '',
        protected string $fieldName = '',
        protected string $fieldType = ''
    )
    {
        parent::__construct();
        $this->dataPassport = new DataPassport();
        $this->dataPassport->setSrcFieldName($this->fieldName);
        $this->dataPassport->setSrcTableName($this->table);
    }

    public function getValue()
    {
        return $this->data[0];
    }

    /**
     * @throws \Exception
     */
    public function setValue($data): void
    {
        if (empty($this->fieldType)) {
            throw new ECWatchException(
                sprintf(
                    'Atomizer\Atom::setField Error: Type not set for field %s. This data : %s ',
                    $this->fieldName,
                    print_r($this, true)
                )
            );
        }

        if ($this->fieldType === 'timestamp') {
            if (gettype($data) === 'int') {
                $data = date('Y-m-d H:i:s', $data);
            } else if (gettype($data) === 'string') {
                $data = date('Y-m-d H:i:s', strtotime($data));
            }
            $this->data[0] = $data;
            return;
        }

        if ($this->fieldType === 'int' && $data == (int)$data) {
            $this->data[0] = (int)$data;
            return;
        }

        if ($this->fieldType === 'float' && $data == (float)$data) {
            $this->data[0] = (float)$data;
            return;
        }

        if ($this->fieldType === 'double' && $data == (double)$data) {
            $this->data[0] = (double)$data;
            return;
        }

        if (!empty($this->fieldType) && $this->validData($data)) {
            $this->data[0] = $data;
            return;
        }

        $msg = sprintf(
            'Invalid data type, expect %s, but got %s. Data: %s ',
            $this->fieldType,
            gettype($data),
            print_r($data, true)
        );

        throw new ECWatchException($msg);
    }

    public function getName(): string
    {
        return $this->fieldName;
    }

    public function getDataType(): ?string
    {
        return $this->fieldType;
    }

    public function setDataType(string $type): void
    {
        $this->fieldType = $type;
    }

    public function getPassport(): DataPassport
    {
        return $this->dataPassport ?? new DataPassport();
    }

    private function validData($data): bool
    {
       if  (gettype($data) === $this->fieldType) {
           return true;
       }

       if ($this->fieldType === 'array' && is_array($data)) {
           return true;
       }

       if ($this->fieldType === 'string' && is_string($data)) {
           return true;
       }

        if ($this->fieldType === 'string' && $data == (string)$data) {
            return true;
        }

        if ($this->fieldType === 'int' && $data == (string)$data) {
            return true;
        }

        if ($this->fieldType === 'double' && $data == (string)$data) {
            return true;
        }

        if ($this->fieldType === 'float' && $data == (string)$data) {
            return true;
        }

        if (str_contains('varchar', $this->fieldType) && gettype($data) === 'string') {
            return true;
        }

       return false;
    }
}