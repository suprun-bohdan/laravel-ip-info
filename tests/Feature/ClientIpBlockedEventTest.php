<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use SuprunBohdan\IpInfo\Contracts\ReverseDnsResolver;
use SuprunBohdan\IpInfo\Intel\ClientIpFilter;
use SuprunBohdan\IpInfo\Intel\VerifiedCrawlerInspector;
use SuprunBohdan\IpInfo\Laravel\Events\ClientIpBlocked;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\FilterClientIp;
use SuprunBohdan\IpInfo\Tests\Support\FakeReverseDnsResolver;
use SuprunBohdan\IpInfo\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ClientIpBlockedEventTest extends TestCase
{
    public function test_filter_middleware_dispatches_client_ip_blocked_event(): void
    {
        Event::fake([ClientIpBlocked::class]);

        config([
            'ip-info.filtering.enabled' => true,
            'ip-info.filtering.block_tor' => true,
            'ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24'],
            'ip-info.filtering.responses' => [
                'tor' => ['status' => 451, 'message' => 'Tor blocked'],
            ],
        ]);

        IpInfo::fake(['198.96.155.10' => 'IS']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.96.155.10']);
        $middleware = app(FilterClientIp::class);

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $exception) {
            $this->assertSame(451, $exception->getStatusCode());
        }

        Event::assertDispatched(ClientIpBlocked::class, function (ClientIpBlocked $event): bool {
            return $event->reason === 'tor'
                && $event->status === 451
                && $event->message === 'Tor blocked';
        });
    }

    public function test_filter_middleware_exposes_block_reason_header_when_enabled(): void
    {
        config([
            'ip-info.filtering.enabled' => true,
            'ip-info.filtering.block_tor' => true,
            'ip-info.filtering.expose_block_reason_header' => true,
            'ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24'],
        ]);

        IpInfo::fake(['198.96.155.10' => 'IS']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.96.155.10']);
        $middleware = app(FilterClientIp::class);

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $exception) {
            $this->assertSame('tor', $exception->getHeaders()['X-Ip-Info-Block-Reason'] ?? null);
        }
    }

    public function test_verified_crawler_skips_filtering_for_tor_exit(): void
    {
        config([
            'ip-info.filtering.enabled' => true,
            'ip-info.filtering.block_tor' => true,
            'ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24'],
            'ip-info.verified_crawlers.enabled' => true,
            'ip-info.verified_crawlers.skip_filtering' => true,
            'ip-info.verified_crawlers.host_suffixes' => ['.googlebot.com'],
        ]);

        $dns = new FakeReverseDnsResolver;
        $dns->register('198.96.155.10', 'crawl-test.googlebot.com');
        $this->app->instance(ReverseDnsResolver::class, $dns);
        $this->app->forgetInstance(VerifiedCrawlerInspector::class);
        $this->app->forgetInstance(ClientIpFilter::class);

        IpInfo::fake(['198.96.155.10' => 'IS']);

        $intel = IpInfo::for('198.96.155.10')->intel();
        $filter = app(ClientIpFilter::class);

        $this->assertNull($filter->blockReason($intel));
        $this->assertTrue(is_verified_crawler('198.96.155.10'));
    }
}
