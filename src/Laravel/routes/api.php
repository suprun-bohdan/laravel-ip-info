<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SuprunBohdan\IpInfo\Laravel\Http\Controllers\IpInfoController;

$middleware = config('ip-info.routes.middleware', []);

if (! is_array($middleware)) {
    $middleware = array_values(array_filter(array_map(
        'trim',
        explode(',', is_string($middleware) ? $middleware : ''),
    )));
}

$route = Route::match(
    ['get', 'post'],
    config('ip-info.routes.path', '/ip-info'),
    [IpInfoController::class, 'show'],
);

if ($middleware !== []) {
    $route->middleware($middleware);
}
