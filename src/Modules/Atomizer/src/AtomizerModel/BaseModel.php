<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\AtomizerModel;

use Dantweb\Atomizer\DataPassport\DataPassport;

class BaseModel extends AbstractModel
{
    protected DataPassport $dataPassport;

    public function __construct() {
        parent::__construct();
        $this->dataPassport = new DataPassport();
    }

    public function getDataPassport(): DataPassport
    {
        return $this->dataPassport;
    }

    public function setDataPassport(DataPassport $dataPassport): void
    {
        $this->dataPassport = $dataPassport;
    }
}