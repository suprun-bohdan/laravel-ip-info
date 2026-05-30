<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Database\Concerns\HasGeoFromIp;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class HasGeoFromIpTest extends TestCase
{
    public function test_trait_fills_country_from_request(): void
    {
        IpInfo::fake(['203.0.113.10' => 'ua']);

        $model = new class extends Model
        {
            use HasGeoFromIp;

            protected $guarded = [];
        };

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $model->fillGeoFromRequest($request);

        $this->assertSame('UA', $model->country_code);
    }
}
