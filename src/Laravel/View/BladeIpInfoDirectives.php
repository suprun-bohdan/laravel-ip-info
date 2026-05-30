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

        Blade::directive('geoblock', static function (string $expression): string {
            return "<?php if (ip_info()->isCountry({$expression})) { abort((int) config('ip-info.security.block_response_status', 403), (string) config('ip-info.security.block_response_message', 'Access from your country is not allowed.')); } ?>";
        });
    }
}
