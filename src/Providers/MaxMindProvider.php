<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use MaxMind\Db\Reader;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\MaxMind\MaxMindCatalog;
use SuprunBohdan\IpInfo\MaxMind\MaxMindRecordMapper;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class MaxMindProvider implements IpProvider
{
    public function __construct(
        private IpValidator $validator,
        private MaxMindCatalog $catalog,
        private MaxMindRecordMapper $recordMapper,
    ) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if (! config('ip-info.maxmind.enabled', false)) {
            return ProviderResult::skipped('maxmind');
        }

        if (! $this->validator->isPublic($ip->value)) {
            return ProviderResult::skipped('maxmind');
        }

        if (! class_exists(Reader::class)) {
            return ProviderResult::skipped('maxmind');
        }

        $path = $this->catalog->databasePath();

        if ($path === '' || ! is_readable($path)) {
            return ProviderResult::skipped('maxmind');
        }

        try {
            $reader = new Reader($path);
            /** @var array<string, mixed>|null $record */
            $record = $reader->get($ip->value);
            $reader->close();

            if (! is_array($record)) {
                return ProviderResult::miss('maxmind');
            }

            return $this->recordMapper->map($record, $this->catalog->edition());
        } catch (\Throwable) {
            return ProviderResult::skipped('maxmind');
        }
    }
}
