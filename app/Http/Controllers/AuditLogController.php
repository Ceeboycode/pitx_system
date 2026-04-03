<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate as GateFacade;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    private const DISPLAY_TIMEZONE = 'Asia/Manila';

    public function index(Request $request): Response
    {
        GateFacade::authorize('viewAny', AuditLog::class);

        $filters = $this->extractFilters($request, true);

        $auditLogs = $this->buildAuditLogs(
            filters: $filters,
            forceUserId: null,
            forceCompanyId: null,
            includeTechnicalDetails: true,
        );

        $actions = $this->buildActionOptions(
            includeRequestActions: (bool) ($filters['include_request_actions'] ?? false),
            forceUserId: null,
            forceCompanyId: null,
        );

        $entityTypes = $this->buildEntityTypeOptions(
            includeRequestActions: (bool) ($filters['include_request_actions'] ?? false),
            forceUserId: null,
            forceCompanyId: null,
        );

        $users = User::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('AuditLogs/Index', [
            'auditLogs' => $auditLogs,
            'filters' => $filters,
            'actions' => $actions,
            'entityTypes' => $entityTypes,
            'users' => $users,
        ]);
    }

    public function myActivity(Request $request): Response
    {
        GateFacade::authorize('viewOwn', AuditLog::class);

        $filters = $this->extractFilters($request, false);
        $authUser = $request->user();

        $auditLogs = $this->buildAuditLogs(
            filters: $filters,
            forceUserId: $authUser?->id,
            forceCompanyId: null,
            includeTechnicalDetails: true,
        );

        return Inertia::render('ActivityLogs/Index', [
            'auditLogs' => $auditLogs,
            'filters' => $filters,
            'actions' => $this->buildActionOptions(
                includeRequestActions: (bool) ($filters['include_request_actions'] ?? false),
                forceUserId: $authUser?->id,
                forceCompanyId: null,
            ),
            'entityTypes' => $this->buildEntityTypeOptions(
                includeRequestActions: (bool) ($filters['include_request_actions'] ?? false),
                forceUserId: $authUser?->id,
                forceCompanyId: null,
            ),
        ]);
    }

    public function externalMyActivity(Request $request): Response
    {
        GateFacade::authorize('viewOwnExternal', AuditLog::class);

        $filters = $this->extractFilters($request, false);
        $authUser = $request->user();
        $companyId = $authUser?->company_id;

        $auditLogs = $this->buildAuditLogs(
            filters: $filters,
            forceUserId: $authUser?->id,
            forceCompanyId: $companyId,
            includeTechnicalDetails: false,
        );

        return Inertia::render('External/ActivityLogs/Index', [
            'auditLogs' => $auditLogs,
            'filters' => $filters,
            'actions' => $this->buildActionOptions(
                includeRequestActions: false,
                forceUserId: $authUser?->id,
                forceCompanyId: $companyId,
            ),
            'entityTypes' => $this->buildEntityTypeOptions(
                includeRequestActions: false,
                forceUserId: $authUser?->id,
                forceCompanyId: $companyId,
            ),
        ]);
    }

    private function extractFilters(Request $request, bool $includeUserFilter): array
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'action' => $request->string('action')->toString() ?: null,
            'entity_type' => $request->string('entity_type')->toString() ?: null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
            'include_request_actions' => $request->boolean('include_request_actions', false),
        ];

        if ($includeUserFilter) {
            $filters['user_id'] = $request->integer('user_id') ?: null;
        }

        return $filters;
    }

    private function buildAuditLogs(
        array $filters,
        ?int $forceUserId,
        ?int $forceCompanyId,
        bool $includeTechnicalDetails,
    ): LengthAwarePaginator {
        $includeRequestActions = (bool) ($filters['include_request_actions'] ?? false);

        return AuditLog::query()
            ->with(['user:id,name,email'])
            ->when(! $includeRequestActions, fn ($query) => $query->where('action', 'not like', 'request.%'))
            ->when($forceUserId !== null, fn ($query) => $query->where('user_id', $forceUserId))
            ->when($forceCompanyId !== null, fn ($query) => $query->where('company_id', $forceCompanyId))
            ->applyFilters($filters)
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AuditLog $log) => $this->transformAuditLogRow($log, $includeTechnicalDetails));
    }

    private function buildActionOptions(bool $includeRequestActions, ?int $forceUserId, ?int $forceCompanyId)
    {
        return AuditLog::query()
            ->when(! $includeRequestActions, fn ($query) => $query->where('action', 'not like', 'request.%'))
            ->when($forceUserId !== null, fn ($query) => $query->where('user_id', $forceUserId))
            ->when($forceCompanyId !== null, fn ($query) => $query->where('company_id', $forceCompanyId))
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn (string $action) => [
                'value' => $action,
                'label' => $this->formatAction($action),
            ])
            ->values();
    }

    private function buildEntityTypeOptions(bool $includeRequestActions, ?int $forceUserId, ?int $forceCompanyId)
    {
        return AuditLog::query()
            ->when(! $includeRequestActions, fn ($query) => $query->where('action', 'not like', 'request.%'))
            ->when($forceUserId !== null, fn ($query) => $query->where('user_id', $forceUserId))
            ->when($forceCompanyId !== null, fn ($query) => $query->where('company_id', $forceCompanyId))
            ->whereNotNull('auditable_type')
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(fn (string $type) => [
                'value' => $type,
                'label' => $this->formatEntityType($type),
            ])
            ->values();
    }

    private function transformAuditLogRow(AuditLog $log, bool $includeTechnicalDetails): array
    {
        $base = [
            'id' => $log->id,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
            'action' => $log->action,
            'action_label' => $this->formatAction($log->action),
            'entity_type' => $log->auditable_type,
            'entity_label' => $this->formatEntityType($log->auditable_type),
            'entity_name' => $this->resolveEntityName($log->auditable_type, $log->auditable_id),
            'changes' => $this->transformChanges($log->changed_fields ?? []),
            'created_at' => $log->created_at?->toIso8601String(),
            'created_at_human' => $log->created_at?->timezone(self::DISPLAY_TIMEZONE)->format('M d, Y h:i A'),
        ];

        if (! $includeTechnicalDetails) {
            return $base;
        }

        return [
            ...$base,
            'metadata' => $log->metadata,
            'ip_address' => $log->ip_address,
            'request_method' => $log->request_method,
            'request_url' => $log->request_url,
        ];
    }

    private function transformChanges(array $changes): array
    {
        $result = [];

        $requestedFieldCode = (string) ($changes['requested_field']['new'] ?? '');
        $requestedOldValue = $changes['old_value']['new'] ?? null;
        $requestedNewValue = $changes['requested_value']['new'] ?? null;

        // For dispatch change requests, collapse technical fields into one readable change line.
        if ($requestedFieldCode !== '' && (array_key_exists('old_value', $changes) || array_key_exists('requested_value', $changes))) {
            $result[] = [
                'field' => 'requested_change',
                'label' => $this->formatRequestedField($requestedFieldCode),
                'old' => $this->resolveValueDisplay($requestedFieldCode, $requestedOldValue),
                'new' => $this->resolveValueDisplay($requestedFieldCode, $requestedNewValue),
            ];
        }

        $skipFields = [
            'requested_field',
            'old_value',
            'requested_value',
        ];

        foreach ($changes as $field => $value) {
            if (in_array((string) $field, $skipFields, true)) {
                continue;
            }

            $old = $this->resolveValueDisplay((string) $field, $value['old'] ?? null);
            $new = $this->resolveValueDisplay((string) $field, $value['new'] ?? null);

            $result[] = [
                'field' => $field,
                'label' => $this->formatFieldLabel((string) $field),
                'old' => $old,
                'new' => $new,
            ];
        }

        return $result;
    }

    private function resolveEntityName(?string $type, ?int $id): ?string
    {
        if (! $type || ! $id) {
            return null;
        }

        return match ($type) {
            User::class => User::query()->whereKey($id)->value('name'),
            Company::class => Company::query()->whereKey($id)->value('company_name'),
            Gate::class => Gate::query()->whereKey($id)->value('gate_name'),
            Route::class => Route::query()->whereKey($id)->value('route_name'),
            Role::class => Role::query()->whereKey($id)->value('name'),
            Dispatch::class => Dispatch::query()->whereKey($id)->value('plate_number'),
            Vehicle::class => Vehicle::query()->whereKey($id)->value('plate_number'),
            default => null,
        };
    }

    private function resolveValueDisplay(string $field, mixed $value): mixed
    {
        $value = $this->decodeJsonScalar($value);

        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_numeric($value) && $this->isIdLikeField($field)) {
            $resolved = $this->resolveRelatedNameByField($field, (int) $value);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        if (is_string($value)) {
            if ($field === 'requested_field') {
                return $this->formatRequestedField($value);
            }

            if ($field === 'status') {
                return Str::headline($value);
            }

            if ($field === 'business_type') {
                return Str::headline(str_replace('_', ' ', $value));
            }
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }

    private function formatFieldLabel(string $field): string
    {
        $labels = [
            'dispatch_id' => 'Dispatch',
            'requested_by' => 'Requested By',
            'approved_by' => 'Approved By',
            'requested_field' => 'Requested Field',
            'old_value' => 'Previous Value',
            'requested_value' => 'Requested Value',
            'rejection_reason' => 'Rejection Reason',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'deleted_by' => 'Deleted By',
            'driver_user_id' => 'Driver',
            'dispatcher_user_id' => 'Dispatcher',
            'company_id' => 'Company',
            'gate_id' => 'Gate',
            'route_id' => 'Route',
            'vehicle_id' => 'Vehicle',
        ];

        return $labels[$field] ?? Str::headline($field);
    }

    private function formatRequestedField(string $field): string
    {
        return match ($field) {
            'departed_at' => 'Departed Time',
            'driver_user_id' => 'Driver',
            'pax_count' => 'Passenger Count',
            'vehicle_id' => 'Vehicle',
            'gate_id' => 'Gate',
            'bay_number' => 'Bay Number',
            default => Str::headline($field),
        };
    }

    private function decodeJsonScalar(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        $decoded = json_decode($trimmed, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return is_array($decoded) ? $value : $decoded;
    }

    private function isIdLikeField(string $field): bool
    {
        if (str_ends_with($field, '_id')) {
            return true;
        }

        return in_array($field, [
            'requested_by',
            'approved_by',
            'created_by',
            'updated_by',
            'deleted_by',
            'dispatcher_user_id',
            'driver_user_id',
        ], true);
    }

    private function resolveRelatedNameByField(string $field, int $id): ?string
    {
        static $cache = [];

        $cacheKey = $field . ':' . $id;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = match ($field) {
            'user_id', 'created_by', 'updated_by', 'deleted_by', 'requested_by', 'approved_by', 'dispatcher_user_id', 'driver_user_id' => User::query()->whereKey($id)->value('name'),
            'company_id' => Company::query()->whereKey($id)->value('company_name'),
            'gate_id' => Gate::query()->whereKey($id)->value('gate_name'),
            'route_id' => Route::query()->whereKey($id)->value('route_name'),
            'role_id' => Role::query()->whereKey($id)->value('name'),
            'dispatch_id' => Dispatch::query()->whereKey($id)->value('plate_number'),
            'vehicle_id' => Vehicle::query()->whereKey($id)->value('plate_number'),
            default => null,
        };

        return $cache[$cacheKey];
    }

    private function formatAction(string $action): string
    {
        return match ($action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'auth.login' => 'Login',
            'auth.logout' => 'Logout',
            'auth.login_failed' => 'Failed Login',
            'dispatch.change_request.submitted' => 'Dispatch Change Submitted',
            'dispatch.change_request.approved' => 'Dispatch Change Approved',
            'dispatch.change_request.rejected' => 'Dispatch Change Rejected',
            str_starts_with($action, 'request.') => 'System Request Action',
            default => Str::title(str_replace(['.', '_'], ' ', $action)),
        };
    }

    private function formatEntityType(?string $type): string
    {
        if (! $type) {
            return 'System';
        }

        return Str::headline(class_basename($type));
    }
}
