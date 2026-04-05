<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dispatch\ApproveChangeRequestRequest;
use App\Http\Requests\Dispatch\RejectChangeRequestRequest;
use App\Http\Requests\Dispatch\StoreDispatchChangeRequestRequest;
use App\Models\Dispatch;
use App\Models\DispatchChangeRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DriverAssignmentValidator;
use App\Notifications\DispatchChangeRequestApprovedNotification;
use App\Notifications\DispatchChangeRequestRejectedNotification;
use App\Notifications\DispatchChangeRequestSubmittedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DispatchChangeRequestController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * Display a listing of dispatch change requests
     */
    public function index(Request $request): Response|JsonResponse
    {
        $user = auth()->user();

        // Filter based on user type
        if ($user->company_id) {
            // Company users see their own requests
            $requests = DispatchChangeRequest::whereHas('dispatch', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->with(['dispatch', 'requestedBy.company', 'approvedBy'])
            ->latest()
            ->get();
        } else {
            // Internal users see all requests
            $requests = DispatchChangeRequest::with(['dispatch', 'requestedBy.company', 'approvedBy'])
                ->latest()
                ->get();
        }

        // If this is an API request, return JSON
        if ($request->wantsJson()) {
            return response()->json($requests);
        }

        // Otherwise, return Inertia response
        return Inertia::render('DispatchChangeRequests/Index', [
            'changeRequests' => $requests->map(fn (DispatchChangeRequest $req) => [
                'id' => $req->id,
                'dispatch_id' => $req->dispatch_id,
                'requested_by' => [
                    'id' => $req->requestedBy->id,
                    'name' => $req->requestedBy->name,
                    'email' => $req->requestedBy->email,
                ],
                'company_name' => $req->requestedBy->company?->company_name ?? '—',
                'company_code' => $req->requestedBy->company?->company_code ?? '—',
                'requested_field' => $req->requested_field,
                'old_value' => $req->old_value,
                'old_value_display' => $req->old_value_display,
                'requested_value' => $req->requested_value,
                'requested_value_display' => $req->requested_value_display,
                'reason' => $req->reason,
                'status' => $req->status,
                'approved_by' => $req->approvedBy ? [
                    'id' => $req->approvedBy->id,
                    'name' => $req->approvedBy->name,
                ] : null,
                'rejection_reason' => $req->rejection_reason,
                'approved_at' => $req->approved_at?->toIso8601String(),
                'created_at' => $req->created_at?->toIso8601String(),
                'field_label' => $req->field_label,
                'dispatch' => $req->dispatch ? [
                    'id' => $req->dispatch->id,
                    'plate_number' => $req->dispatch->plate_number,
                    'status' => $req->dispatch->status,
                    'driver' => $req->dispatch->driver ? [
                        'id' => $req->dispatch->driver->id,
                        'name' => $req->dispatch->driver->name,
                    ] : null,
                    'gate' => $req->dispatch->gate ? [
                        'id' => $req->dispatch->gate->id,
                        'gate_name' => $req->dispatch->gate->gate_name,
                    ] : null,
                    'bay_number' => $req->dispatch->bay_number,
                ] : null,
            ])->values(),
        ]);
    }

    /**
     * Store a new change request
     */
    public function store(Dispatch $dispatch, StoreDispatchChangeRequestRequest $request): RedirectResponse|JsonResponse
    {
        if ($request->requested_field === DispatchChangeRequest::FIELD_DRIVER_USER_ID) {
            $driver = User::query()
                ->where('company_id', $dispatch->company_id)
                ->where('status', 'active')
                ->whereHas('roles', fn ($query) => $query->where('name', 'driver'))
                ->findOrFail((int) $request->requested_value);

            $validator = new DriverAssignmentValidator();
            if (! $validator->canAssignToday($driver, now(), $dispatch)) {
                throw ValidationException::withMessages([
                    'requested_value' => $validator->getValidationMessage($driver, now()),
                ]);
            }
        }

        // Store old value before creating the request
        $oldValue = $dispatch->{$request->requested_field};

        $changeRequest = DispatchChangeRequest::create([
            'dispatch_id' => $dispatch->id,
            'requested_by' => auth()->id(),
            'requested_field' => $request->requested_field,
            'old_value' => $oldValue,
            'requested_value' => $request->requested_value,
            'reason' => $request->reason,
            'status' => DispatchChangeRequest::STATUS_PENDING,
        ]);

        // Load relationships for notification
        $changeRequest->load(['dispatch', 'requestedBy.company']);

        $this->auditLogger->log(
            action: 'dispatch.change_request.submitted',
            actor: auth()->user(),
            auditable: $changeRequest,
            changedFields: [
                'requested_field' => [
                    'old' => $changeRequest->old_value,
                    'new' => $changeRequest->requested_value,
                ],
            ],
            metadata: [
                'reason' => $changeRequest->reason,
                'dispatch_id' => $changeRequest->dispatch_id,
                'requested_field' => $changeRequest->requested_field,
            ],
        );

        // Notify internal users about the new request
        $internalUsers = \App\Models\User::whereNull('company_id')->get();
        foreach ($internalUsers as $user) {
            $user->notify(new DispatchChangeRequestSubmittedNotification($changeRequest));
        }

        // If this is an API request, return JSON
        if ($request->wantsJson()) {
            return response()->json($changeRequest, 201);
        }

        // Otherwise, redirect back with success message for Inertia
        return redirect()->back()->with('success', 'Change request submitted successfully. Please wait for approval.');
    }

    /**
     * Approve a change request
     */
    public function approve(DispatchChangeRequest $changeRequest, ApproveChangeRequestRequest $request): RedirectResponse|JsonResponse
    {
        // Ensure request is pending
        if (!$changeRequest->isPending()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Only pending requests can be approved.',
                ], 422);
            }
            return redirect()->back()->withErrors(['message' => 'Only pending requests can be approved.']);
        }

        if ($changeRequest->requested_field === DispatchChangeRequest::FIELD_DRIVER_USER_ID) {
            $dispatch = $changeRequest->dispatch;
            $driverId = is_numeric($changeRequest->requested_value)
                ? (int) $changeRequest->requested_value
                : null;

            if (! $driverId) {
                throw ValidationException::withMessages([
                    'message' => 'The selected driver is invalid.',
                ]);
            }

            $driver = User::query()
                ->where('company_id', $dispatch->company_id)
                ->where('status', 'active')
                ->whereHas('roles', fn ($query) => $query->where('name', 'driver'))
                ->findOrFail($driverId);

            $validator = new DriverAssignmentValidator();
            if (! $validator->canAssignToday($driver, now(), $dispatch)) {
                throw ValidationException::withMessages([
                    'message' => $validator->getValidationMessage($driver, now()),
                ]);
            }
        }

        // Approve the request and apply the change
        $changeRequest->approve(auth()->user());

        $this->auditLogger->log(
            action: 'dispatch.change_request.approved',
            actor: auth()->user(),
            auditable: $changeRequest,
            changedFields: [
                'status' => [
                    'old' => DispatchChangeRequest::STATUS_PENDING,
                    'new' => DispatchChangeRequest::STATUS_APPROVED,
                ],
            ],
            metadata: [
                'dispatch_id' => $changeRequest->dispatch_id,
                'requested_field' => $changeRequest->requested_field,
            ],
        );

        // Notify the requester
        $changeRequest->requestedBy->notify(new DispatchChangeRequestApprovedNotification($changeRequest));

        // If this is an API request, return JSON
        if ($request->wantsJson()) {
            return response()->json($changeRequest->load(['dispatch', 'requestedBy', 'approvedBy']), 200);
        }

        // Otherwise, redirect back with success message for Inertia
        return redirect()->back()->with('success', 'Change request approved successfully.');
    }

    /**
     * Reject a change request
     */
    public function reject(DispatchChangeRequest $changeRequest, RejectChangeRequestRequest $request): RedirectResponse|JsonResponse
    {
        // Ensure request is pending
        if (!$changeRequest->isPending()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Only pending requests can be rejected.',
                ], 422);
            }
            return redirect()->back()->withErrors(['message' => 'Only pending requests can be rejected.']);
        }

        // Reject the request (don't apply the change)
        $changeRequest->reject(auth()->user(), $request->rejection_reason);

        $this->auditLogger->log(
            action: 'dispatch.change_request.rejected',
            actor: auth()->user(),
            auditable: $changeRequest,
            changedFields: [
                'status' => [
                    'old' => DispatchChangeRequest::STATUS_PENDING,
                    'new' => DispatchChangeRequest::STATUS_REJECTED,
                ],
            ],
            metadata: [
                'dispatch_id' => $changeRequest->dispatch_id,
                'requested_field' => $changeRequest->requested_field,
                'rejection_reason' => $request->rejection_reason,
            ],
        );

        // Notify the requester with rejection reason
        $changeRequest->requestedBy->notify(new DispatchChangeRequestRejectedNotification($changeRequest));

        // If this is an API request, return JSON
        if ($request->wantsJson()) {
            return response()->json($changeRequest->load(['dispatch', 'requestedBy', 'approvedBy']), 200);
        }

        // Otherwise, redirect back with success message for Inertia
        return redirect()->back()->with('success', 'Change request rejected successfully.');
    }
}
