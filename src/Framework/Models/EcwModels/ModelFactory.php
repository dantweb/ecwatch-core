<?php

namespace Dantweb\Ecommwatch\Framework\Models\EcwModels;

use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Models\Domain\AbstractDomainModel;

class ModelFactory extends EcwModelFactory
{
    public function createAnonymousEcwModel(array $yaml): EcwModelInterface
    {
        return new class (
            $yaml['ecw_data_model']['name'],
            $yaml['ecw_data_model']['properties']
        ) extends AbstractDomainModel {
        };
    }
}
