<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

use MaxMind\Db\Reader;
use SuprunBohdan\IpInfo\Support\IpValidator;

class MmdbReaderPool
{
    /** @var array<string, Reader> */
    private array $readers = [];

    public function __construct(
        private LocationDbCatalog $catalog,
        private IpValidator $validator,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function lookup(string $ip, ?string $edition = null): ?array
    {
        if (! class_exists(Reader::class)) {
            return null;
        }

        if (! $this->validator->isPublic($ip)) {
            return null;
        }

        $edition ??= $this->catalog->edition();
        $reader = $this->readerFor($ip, $edition);

        if ($reader === null) {
            return null;
        }

        try {
            /** @var array<string, mixed>|null $record */
            $record = $reader->get($ip);

            return is_array($record) ? $record : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function __destruct()
    {
        foreach ($this->readers as $reader) {
            $reader->close();
        }
    }

    private function readerFor(string $ip, string $edition): ?Reader
    {
        $isIpv6 = str_contains($ip, ':');
        $ipVersion = $isIpv6 ? 'ipv6' : 'ipv4';
        $cacheKey = $edition.':'.$ipVersion;

        if (isset($this->readers[$cacheKey])) {
            return $this->readers[$cacheKey];
        }

        $path = $this->catalog->filePath($edition, $ipVersion);

        if (! is_readable($path)) {
            return null;
        }

        $this->readers[$cacheKey] = new Reader($path);

        return $this->readers[$cacheKey];
    }
}
