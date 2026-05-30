<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

enum SyncStatus: string
{
    case Ok = 'ok';
    case Missing = 'missing';
    case Outdated = 'outdated';
    case Modified = 'modified';
    case Unsafe = 'unsafe';
    case Unknown = 'unknown';
    case Disabled = 'disabled';
}
