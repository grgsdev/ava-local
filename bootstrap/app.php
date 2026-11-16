<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php', // <-- Deixe apenas o padrão
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend([
            //     \App\Http\Middleware\SetUserTimezone::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\SetUserTimezone::class,
        ]);
        $middleware->alias([
            'set_locale' => \App\Http\Middleware\SetLocale::class,
            'panel.access' => \App\Http\Middleware\CheckPanelAccess::class,
            'role.system-engineer' => \App\Http\Middleware\CheckSystemEngineeringRole::class,
            'cache.response' => \Spatie\ResponseCache\Middlewares\CacheResponse::class,
            'doNotCacheResponse' => \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class,

            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'tenant' => \App\Http\Middleware\SetTenantCompany::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->respond(function (Response $response, Throwable $e, $request) {

            // --- LÓGICA CORRIGIDA E SEGURA ---
            // Renderiza nossa página de erro customizada SE E SOMENTE SE:
            // 1. A requisição tiver o cabeçalho 'X-Inertia'
            // 2. O código de status for um dos que queremos customizar
            if ($request->header('X-Inertia') && in_array($response->getStatusCode(), [400, 403, 404, 500, 503])) {
                return Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            // Para todas as outras requisições (incluindo API), retorna a resposta padrão do Laravel.
            return $response;
        });
    })->create();
