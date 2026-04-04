<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(
        string $action,
        ?Authenticatable $actor = null,
        ?Model $auditable = null,
        array $changedFields = [],
        array $metadata = [],
        ?int $companyId = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id' => $actor?->getAuthIdentifier(),
            'company_id' => $companyId ?? $this->resolveCompanyId($actor, $auditable),
            'action' => $action,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'changed_fields' => $changedFields ?: null,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_method' => $request?->method(),
            'request_url' => $request?->fullUrl(),
        ]);
    }

    public function logModelEvent(Model $model, string $action): ?AuditLog
    {
        if ($model instanceof AuditLog) {
            return null;
        }

        $ignored = config('audit.ignored_fields', []);
        $sensitive = config('audit.sensitive_fields', []);

        if ($action === 'created') {
            $after = $this->sanitizeAttributes($model->getAttributes(), $ignored, $sensitive);

            return $this->log(
                action: 'created',
                actor: Auth::user(),
                auditable: $model,
                changedFields: $this->toDiff([], $after),
            );
        }

        if ($action === 'updated') {
            $changes = $model->getChanges();
            $before = [];
            $after = [];

            foreach (array_keys($changes) as $key) {
                if (in_array($key, $ignored, true)) {
                    continue;
                }

                $before[$key] = $model->getOriginal($key);
                $after[$key] = $model->getAttribute($key);
            }

            $before = $this->sanitizeAttributes($before, $ignored, $sensitive);
            $after = $this->sanitizeAttributes($after, $ignored, $sensitive);
            $diff = $this->toDiff($before, $after);

            if ($diff === []) {
                return null;
            }

            return $this->log(
                action: 'updated',
                actor: Auth::user(),
                auditable: $model,
                changedFields: $diff,
            );
        }

        if ($action === 'deleted') {
            $before = $this->sanitizeAttributes($model->getOriginal(), $ignored, $sensitive);

            return $this->log(
                action: 'deleted',
                actor: Auth::user(),
                auditable: $model,
                changedFields: $this->toDiff($before, []),
            );
        }

        return null;
    }

    private function toDiff(array $before, array $after): array
    {
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $diff = [];

        foreach ($keys as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;

            if ($old === $new) {
                continue;
            }

            $diff[$key] = [
                'old' => $old,
                'new' => $new,
            ];
        }

        return $diff;
    }

    private function sanitizeAttributes(array $attributes, array $ignored, array $sensitive): array
    {
        $sanitized = [];

        foreach ($attributes as $key => $value) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            $sanitized[$key] = in_array($key, $sensitive, true)
                ? '[REDACTED]'
                : $this->normalizeValue($value);
        }

        return $sanitized;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? (string) $value
                : json_decode(json_encode($value), true);
        }

        return $value;
    }

    private function resolveCompanyId(?Authenticatable $actor, ?Model $auditable): ?int
    {
        $auditableCompanyId = $auditable?->getAttribute('company_id');

        if (is_numeric($auditableCompanyId)) {
            return (int) $auditableCompanyId;
        }

        $actorCompanyId = $actor?->company_id ?? null;

        return is_numeric($actorCompanyId) ? (int) $actorCompanyId : null;
    }
}
