<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Service;

use Dantweb\Atomizer\Adapter\AdapterInterface;
use Dantweb\Atomizer\EcwModel\EcwModelInterface;
use Dantweb\Atomizer\Map\MapInterface;
use Dantweb\Ecommwatch\Framework\Exception\EcwException;
use Dantweb\Ecommwatch\Framework\Exception\EcwTableNotFoundException;
use Dantweb\Ecommwatch\Framework\Helper\Logger;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\RepoFactory\RepoFactory;
use Dantweb\Ecommwatch\Framework\Middleware\Repository\AbstractRepo;

class BaseImportService
{
    public const PROTOCOL_CSV = 'csv';
    public const PROTOCOL_XML = 'xml';
    public const PROTOCOL_JSON = 'json';

    public const PROTOCOLS = [self::PROTOCOL_CSV, self::PROTOCOL_XML, self::PROTOCOL_JSON];
    protected DatabaseConnector $dbConnect;
    protected RepoFactory $repoFactory;

    public function __construct(protected string $importDataDir)
    {
        $this->dbConnect = DatabaseConnector::getInstance();
        $this->repoFactory = new RepoFactory($this->dbConnect);
    }

    /**
     * @throws EcwTableNotFoundException
     */
    public function importCsv(
        EcwModelInterface $ecwModel,
        MapInterface $map,
        AdapterInterface $adapter,
        string $absPathCsv
    ): int {
        $repo = $this->repoFactory->getRepo($ecwModel);
        $repo->setWritingMode(AbstractRepo::DUPLICATES_REPORT);
        $importedModels = $adapter->getModelArrayFromCsv($ecwModel, $absPathCsv);

        $count = 0;
        foreach ($importedModels as $importedModel) {
            try {
                $repo->save($importedModel);
                $count++;
            } catch (EcwException $e) {
                Logger::error($e->getMessage());
            }
        }

        return $count;
    }

    /**
     * @throws EcwException
     * @throws EcwTableNotFoundException
     */
    public function import(
        string $protocol,
        EcwModelInterface $ecwModel,
        MapInterface $map,
        AdapterInterface $adapter,
        string $absPathCsv
    ): int {
        if (!in_array($protocol, self::PROTOCOLS)) {
            throw new EcwException('Protocol not supported: ' . $protocol);
        }

        switch ($protocol) {
            case self::PROTOCOL_CSV:
                return $this->importCsv($ecwModel, $map, $adapter, $absPathCsv);
            case self::PROTOCOL_XML:
            case self::PROTOCOL_JSON:
                break;
        }

        return 0;
    }
}
