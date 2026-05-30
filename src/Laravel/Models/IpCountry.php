<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

final class IpCountry extends Model
{
    protected $table = 'ip_country';

    protected $fillable = [
        'first_ip',
        'last_ip',
        'country',
    ];

    public $timestamps = true;
}
