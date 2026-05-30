<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Support;

use Illuminate\Support\Facades\Storage;

final class CsvFilePathService
{
    private const CSV_FILE = 'asn-country-ipv4.csv';

    public function getCsvFilePath(): string
    {
        return Storage::path(self::CSV_FILE);
    }

    public function getCsvFileName(): string
    {
        return self::CSV_FILE;
    }

    public function putCsvFile(string $contents): string
    {
        Storage::put($this->getCsvFileName(), $contents);

        return $this->getCsvFilePath();
    }
}
