<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\IpPrivacyProfile;
use SuprunBohdan\IpInfo\Data\IpThreatSignals;

interface IpLookupContract
{
    public function lookup(IpAddress $address): IpInfoResult;

    public function isPublic(IpAddress $address): bool;

    public function isPrivate(IpAddress $address): bool;

    public function normalizedIp(IpAddress $address): string;

    public function privacyProfile(IpAddress $address): IpPrivacyProfile;

    public function threatSignals(IpAddress $address, ?IpThreatSignals $providerThreats = null): IpThreatSignals;
}
