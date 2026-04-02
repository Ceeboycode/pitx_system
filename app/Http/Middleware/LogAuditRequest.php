<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAuditRequest
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $config = config('audit.request_logging', []);

        if (! ($config['enabled'] ?? false)) {
            return $response;
        }

        if (! $request->user()) {
            return $response;
        }

        $method = strtoupper($request->method());
        $allowedMethods = $config['methods'] ?? [];

        if (! in_array($method, $allowedMethods, true)) {
            return $response;
        }

        $routeName = optional($request->route())->getName();
        if ($routeName && in_array($routeName, $config['excluded_route_names'] ?? [], true)) {
            return $response;
        }

        foreach ($config['excluded_paths'] ?? [] as $excludedPath) {
            if ($request->is($excludedPath) || $request->is($excludedPath . '/*')) {
                return $response;
            }
        }

        $this->auditLogger->log(
            action: $this->resolveAction($request),
            actor: $request->user(),
            auditable: null,
            changedFields: [],
            metadata: [
                'source' => 'request',
                'route_name' => $routeName,
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'input' => $this->sanitizeInput($request->except(config('audit.sensitive_fields', []))),
            ],
        );

        return $response;
    }

    private function resolveAction(Request $request): string
    {
        $routeName = optional($request->route())->getName();

        if ($routeName) {
            return 'request.' . str_replace('.', '_', $routeName);
        }

        return 'request.' . strtolower($request->method()) . '.' . str_replace('/', '_', $request->path());
    }

    private function sanitizeInput(array $input): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (in_array($key, config('audit.sensitive_fields', []), true)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            $sanitized[$key] = is_scalar($value) || is_array($value) || is_null($value)
                ? $value
                : (string) $value;
        }

        return $sanitized;
    }
}
