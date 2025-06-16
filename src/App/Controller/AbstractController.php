<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\App\Controller;

use Dantweb\Ecommwatch\Framework\Service\BaseImportService;
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

        try {
            $count = $this->importService->import(
                $body['moduleId'],
                $body['modelName'],
                $body['payload']
            );
            return $this->json(['success' => true, 'imported' => $count]);
        } catch (\Throwable $e) {
            return $this->json(
                ['success' => false, 'error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
