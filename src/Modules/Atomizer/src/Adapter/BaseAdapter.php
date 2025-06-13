<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\Adapter;

use Dantweb\Atomizer\EcwModel\EcwModelInterface;

class BaseAdapter extends AbstractAdapter
{
    public function getModelArrayFromCsv(EcwModelInterface $model, string $csvFilePath): ?array
    {
        if (!file_exists($csvFilePath)) {
            $this->logger->error('File not found: ' . $csvFilePath);
            return null;
        }

        $data = $this->readCsvAsAssociativeArray($csvFilePath);

        return $this->convertToEcwModels($model, $data);
    }

    public function readCsvAsAssociativeArray(string $filename): ?array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return null;
        }

        $rows = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($rows)) {
            return null;
        }

        // Automatically detect the delimiter (';' or ',')
        $delimiter = $this->detectCsvDelimiter($rows[0]);

        // Extract the header row
        $headers = str_getcsv(array_shift($rows), $delimiter);
        $data = [];

        foreach ($rows as $row) {
            $values = str_getcsv($row, $delimiter);
            $data[] = array_combine($headers, $values);
        }

        return $data;
    }

    private function detectCsvDelimiter(string $line): string
    {
        $commaCount = substr_count($line, ',');
        $semicolonCount = substr_count($line, ';');
        return $semicolonCount > $commaCount ? ';' : ',';
    }

}