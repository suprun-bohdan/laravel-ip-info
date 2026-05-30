<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Whois;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use SuprunBohdan\IpInfo\Contracts\WhoisClient;
use SuprunBohdan\IpInfo\Data\WhoisRecord;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class WhoisLookupService
{
    private const IANA_SERVER = 'whois.iana.org';

    public function __construct(
        private WhoisClient $client,
        private WhoisParser $parser,
        private IpNormalizer $normalizer,
        private IpValidator $validator,
        private CacheRepository $cache,
    ) {}

    public function lookup(string $ip, bool $force = false): ?WhoisRecord
    {
        if (! $force && ! (bool) config('ip-info.whois.enabled', false)) {
            return null;
        }

        $normalized = $this->normalizer->normalize($ip);

        if (! $this->validator->isValid($normalized) || ! $this->validator->isPublic($normalized)) {
            return null;
        }

        $cacheKey = $this->cacheKey($normalized);
        $ttl = (int) config('ip-info.whois.cache_ttl', 86400);

        if ($ttl > 0) {
            $cached = $this->cache->get($cacheKey);

            if ($cached instanceof WhoisRecord) {
                return $cached;
            }
        }

        $record = $this->performLookup($normalized);

        if ($record !== null && $ttl > 0) {
            $this->cache->put($cacheKey, $record, $ttl);
        }

        return $record;
    }

    private function performLookup(string $ip): ?WhoisRecord
    {
        $timeout = (int) config('ip-info.whois.timeout', 5);
        $maxReferrals = max(0, (int) config('ip-info.whois.max_referrals', 2));
        $server = self::IANA_SERVER;
        $raw = $this->client->query($server, $ip, $timeout);
        $registry = $server;

        for ($attempt = 0; $attempt <= $maxReferrals; $attempt++) {
            $referral = $this->parser->referralServer($raw);

            if ($referral === null || $referral === $server) {
                break;
            }

            $server = $referral;
            $raw = $this->client->query($server, $ip, $timeout);
            $registry = $server;
        }

        $record = $this->parser->parse($ip, $raw, $registry);

        if ($record->inetnum === null && $record->netname === null) {
            return null;
        }

        return $record;
    }

    private function cacheKey(string $ip): string
    {
        $prefix = (string) config('ip-info.cache.prefix', 'laravel_ip_info');

        return $prefix.':whois:v1:'.$ip;
    }
}
