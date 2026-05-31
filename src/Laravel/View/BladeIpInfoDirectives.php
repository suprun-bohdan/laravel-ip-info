<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\View;

use Illuminate\Support\Facades\Blade;

final class BladeIpInfoDirectives
{
    public static function register(): void
    {
        Blade::directive('country', static function (string $expression): string {
            return "<?php if (ip_info()->isCountry({$expression})): ?>";
        });

        Blade::directive('endcountry', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('unlesscountry', static function (string $expression): string {
            return "<?php if (! ip_info()->isCountry({$expression})): ?>";
        });

        Blade::directive('endunlesscountry', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('clientcountry', static function (?string $expression = null): string {
            if ($expression === null || trim($expression) === '') {
                return '<?php echo e(client_country()); ?>';
            }

            return "<?php echo e(client_country(default: {$expression})); ?>";
        });

        Blade::directive('clientcity', static function (?string $expression = null): string {
            if ($expression === null || trim($expression) === '') {
                return '<?php echo e(client_city()); ?>';
            }

            return "<?php echo e(client_city(default: {$expression})); ?>";
        });

        Blade::directive('city', static function (string $expression): string {
            return "<?php if (ip_info()->isCity({$expression})): ?>";
        });

        Blade::directive('endcity', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('unlesscity', static function (string $expression): string {
            return "<?php if (! ip_info()->isCity({$expression})): ?>";
        });

        Blade::directive('endunlesscity', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('geoblock', static function (string $expression): string {
            return "<?php if (ip_info()->isCountry({$expression})) { abort((int) config('ip-info.security.block_response_status', 403), (string) config('ip-info.security.block_response_message', 'Access from your country is not allowed.')); } ?>";
        });

        Blade::directive('eu', static function (): string {
            return '<?php if (ip_info()->isEu()): ?>';
        });

        Blade::directive('endeu', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('continent', static function (string $expression): string {
            return "<?php if (ip_info()->isInContinent({$expression})): ?>";
        });

        Blade::directive('endcontinent', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('privateip', static function (): string {
            return '<?php if (ip_info()->isPrivate()): ?>';
        });

        Blade::directive('endprivateip', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('publicip', static function (): string {
            return '<?php if (ip_info()->isPublic()): ?>';
        });

        Blade::directive('endpublicip', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('tor', static function (): string {
            return '<?php if (ip_info()->isTor()): ?>';
        });

        Blade::directive('endtor', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('unlesstor', static function (): string {
            return '<?php if (! ip_info()->isTor()): ?>';
        });

        Blade::directive('endunlesstor', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('proxy', static function (): string {
            return '<?php if (ip_info()->isProxy() || ip_info()->isVpn()): ?>';
        });

        Blade::directive('endproxy', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('unlessproxy', static function (): string {
            return '<?php if (! ip_info()->isProxy() && ! ip_info()->isVpn()): ?>';
        });

        Blade::directive('endunlessproxy', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('anonymous', static function (): string {
            return '<?php if (ip_info()->isAnonymous()): ?>';
        });

        Blade::directive('endanonymous', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('hosting', static function (): string {
            return '<?php if (ip_info()->isHosting()): ?>';
        });

        Blade::directive('endhosting', static function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('clientip', static function (): string {
            return '<?php echo e(client_ip()); ?>';
        });

        Blade::directive('anonymizedclientip', static function (): string {
            return '<?php echo e(ip_info()->result()->anonymized()->ip); ?>';
        });

        Blade::directive('highrisk', static function (): string {
            return '<?php if (client_ip_risk()->isHigh()): ?>';
        });

        Blade::directive('endhighrisk', static function (): string {
            return '<?php endif; ?>';
        });
    }
}
