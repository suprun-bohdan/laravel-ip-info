<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

use MaxMind\Db\Reader;
use SuprunBohdan\IpInfo\Support\IpValidator;

class MmdbReaderPool
{
    private ?Reader $ipv4Reader = null;

    private ?Reader $ipv6Reader = null;

    public function __construct(
        private LocationDbCatalog $catalog,
        private IpValidator $validator,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function lookup(string $ip): ?array
    {
        if (! class_exists(Reader::class)) {
            return null;
        }

        if (! $this->validator->isPublic($ip)) {
            return null;
        }

        $reader = $this->readerFor($ip);

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
        if ($this->ipv4Reader !== null) {
            $this->ipv4Reader->close();
        }

        if ($this->ipv6Reader !== null) {
            $this->ipv6Reader->close();
        }
    }

    private function readerFor(string $ip): ?Reader
    {
        $edition = $this->catalog->edition();
        $isIpv6 = str_contains($ip, ':');

        if ($isIpv6) {
            if ($this->ipv6Reader === null) {
                $path = $this->catalog->filePath($edition, 'ipv6');

                if (! is_readable($path)) {
                    return null;
                }

                $this->ipv6Reader = new Reader($path);
            }

            return $this->ipv6Reader;
        }

        if ($this->ipv4Reader === null) {
            $path = $this->catalog->filePath($edition, 'ipv4');

            if (! is_readable($path)) {
                return null;
            }

            $this->ipv4Reader = new Reader($path);
        }

        return $this->ipv4Reader;
    }
}
