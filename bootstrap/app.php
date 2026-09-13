<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'company.verified' => \App\Http\Middleware\CompanyVerified::class,
            // 'company.verified' => \App\Http\Middleware\EnsureCompanyVerified::class,
            'role.type' => \App\Http\Middleware\EnsureRoleType::class,
            'password.change.required' => \App\Http\Middleware\EnsurePasswordIsChanged::class,
            'audit.request' => \App\Http\Middleware\LogAuditRequest::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if ($status === 419) {
                return back()->with(['message' => 'The page expired, please try again.']);
            }

            // Let JSON/API clients keep their normal error payloads.
            if ($request->expectsJson()) {
                return $response;
            }

            // Keep Laravel's detailed debug page for server errors while developing.
            $statuses = app()->environment(['local', 'testing'])
                ? [403, 404]
                : [403, 404, 500, 503];

            if (in_array($status, $statuses, true)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
