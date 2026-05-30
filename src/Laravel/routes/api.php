<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SuprunBohdan\IpInfo\Laravel\Http\Controllers\IpInfoController;

Route::match(['get', 'post'], config('ip-info.routes.path', '/ip-info'), [IpInfoController::class, 'show']);
