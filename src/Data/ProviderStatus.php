<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

enum ProviderStatus: string
{
    case Skipped = 'skipped';
    case Failed = 'failed';
    case Miss = 'miss';
    case Hit = 'hit';
}
