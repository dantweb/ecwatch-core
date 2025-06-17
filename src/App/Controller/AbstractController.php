<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\App\Controller;

use Dantweb\Atomizer\Adapter\BaseAdapter;
use Dantweb\Atomizer\EcwModel\EcwModelFactory;
use Dantweb\Atomizer\Map\MapFactory;
use Dantweb\Ecommwatch\Framework\Service\BaseImportService;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Dantweb\Ecommwatch\Framework\Service\ExpressionResolver;
use Dantweb\Ecommwatch\Framework\Service\ModelResolver;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController extends SymfonyAbstractController
{
    public function __construct(
        protected ExpressionResolver $expressionResolver,
        protected ModelResolver $modelResolver,
        protected BaseImportService $importService
    ) {
    }

    /**
     * GET  /data?expression=...&start=dd.mm.YYYY&end=dd.mm.YYYY
     */
    public function getDataAction(Request $request): JsonResponse
    {
        $expr  = $request->query->get('expression', '');
        $start = $request->query->get('start', null);
        $end   = $request->query->get('end', null);

        if (!$expr) {
            return $this->json(
                ['success' => false, 'error' => 'Missing expression parameter'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $result = $this->expressionResolver->resolve($expr, $start, $end);
            return $this->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return $this->json(
                ['success' => false, 'error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * POST /import
     * JSON body: { "moduleId": "...", "modelName": "...", "payload": [ {...}, {...} ] }
     */
    public function importDataAction(Request $request): Response
    {
        $body = json_decode($request->getContent(), true);
        if (!isset($body['moduleId'], $body['modelName'], $body['payload']) || !is_array($body['payload'])) {
            return $this->json(
                ['success' => false, 'error' => 'Invalid import request body'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $csvAbsPath = $this->saveCsvToTmp($body['payload']);

        $mapYaml = $body['map_yaml'];
        $map     = (new MapFactory())->create(yaml_parse($mapYaml));
        $adapter = new BaseAdapter($map, new NullLogger());
        $model   = (new EcwModelFactory())->createModelFromAbsPath($mapYaml);

        try {
            $count = $this->importService->import(
                $body['protocol'],
                $model,
                $map,
                $adapter,
                $csvAbsPath
            );

            unlink($csvAbsPath);
            return $this->json(['success' => true, 'imported' => $count]);
        } catch (\Throwable $e) {
            return $this->json(
                ['success' => false, 'error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    //** saves payload in a temp file and return its absolute path */
    private function saveCsvToTmp(array $payload): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_');

        if ($tempFile === false) {
            throw new RuntimeException('Failed to create temporary file');
        }

        $csvFile = $tempFile . '.csv';
        rename($tempFile, $csvFile);

        $handle = fopen($csvFile, 'w');
        if ($handle === false) {
            throw new RuntimeException('Failed to open temporary file for writing');
        }

        if (!empty($payload)) {
            $headers = array_keys(reset($payload));
            fputcsv($handle, $headers);
            foreach ($payload as $row) {
                fputcsv($handle, $row);
            }
        }

        fclose($handle);

        return $csvFile;
    }
}
