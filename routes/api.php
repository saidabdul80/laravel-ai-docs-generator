<?php

use Illuminate\Support\Facades\Route;
use SchoolTry\AIDocumentationGenerator\Http\Controllers\CustomerSupportController;

$supportConfig = config('ai-docs.customer_support', []);

if (!($supportConfig['enabled'] ?? true)) {
    return;
}

$prefix = $supportConfig['prefix'] ?? 'customer-support';
$middleware = $supportConfig['middleware'] ?? [];

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () {
        Route::post('/query', [CustomerSupportController::class, 'query']);
        Route::get('/history', [CustomerSupportController::class, 'history']);
        Route::delete('/memory', [CustomerSupportController::class, 'clearMemory']);
        Route::post('/ai-service/query', [CustomerSupportController::class, 'queryAIService']);
    });

$guestPrefix = $supportConfig['guest_prefix'] ?? 'customer-support/guest';
$guestMiddleware = $supportConfig['guest_middleware'] ?? [];
$guestWithout = $supportConfig['guest_without_middleware'] ?? [];

$guestRoutes = Route::prefix($guestPrefix)->middleware($guestMiddleware);
if (!empty($guestWithout)) {
    $guestRoutes = $guestRoutes->withoutMiddleware($guestWithout);
}

$guestRoutes->group(function () {
    Route::post('/query', [CustomerSupportController::class, 'query']);
});
