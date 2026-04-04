<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmMessage;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CrmThreadController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['category', 'status', 'search']);

        $threads = CrmThread::query()
            ->with([
                'company:id,company_name',
                'createdBy:id,name',
                'assignedTo:id,name',
                'messages' => fn ($query) => $query
                    ->with(['sender:id,name', 'attachments'])
                    ->orderBy('created_at'),
            ])
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where('subject', 'like', '%' . $filters['search'] . '%')
            )
            ->when(
                filled($filters['category'] ?? null),
                fn ($query) => $query->where('category', $filters['category'])
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('is_closed', $filters['status'] === 'closed')
            )
            ->tap(fn ($query) => $this->applyVisibilityScope($query, $user))
            ->latest('last_message_at')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CrmThread $thread) => $this->serializeThread($thread, true));

        return Inertia::render('Crm/Threads/Index', [
            'threads' => $threads,
            'filters' => [
                'search' => $filters['search'] ?? null,
                'category' => $filters['category'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
            'canAssignThreads' => $this->canAssignThreads($user),
            'assignees' => $this->canAssignThreads($user)
                ? User::query()
                    ->whereHas('roles', fn ($query) => $query->where('type', 'internal'))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (User $assignee) => [
                        'id' => $assignee->id,
                        'name' => $assignee->name,
                    ])
                    ->values()
                : [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category' => 'required|in:facilities,terminal_operations,commuter_app,other',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'details' => 'nullable|array',
        ]);

        $thread = DB::transaction(function () use ($user, $validated) {
            $thread = CrmThread::create([
                'company_id' => $user->company_id,
                'created_by_user_id' => $user->id,
                'category' => $validated['category'],
                'subject' => $validated['subject'],
                'details' => $validated['details'] ?? null,
                'last_message_at' => now(),
            ]);

            $thread->messages()->create([
                'sender_user_id' => $user->id,
                'body' => $validated['body'],
                'is_internal' => false,
            ]);

            return $thread;
        });

        return response()->json([
            'data' => $this->serializeThread($thread->load([
                'company:id,company_name',
                'createdBy:id,name',
                'assignedTo:id,name',
                'messages' => fn ($query) => $query
                    ->with(['sender:id,name', 'attachments'])
                    ->orderBy('created_at'),
            ]), true),
        ], 201);
    }

    public function show(Request $request, CrmThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($request, $thread);

        $thread->load([
            'company:id,company_name',
            'createdBy:id,name',
            'assignedTo:id,name',
            'messages' => fn ($query) => $query
                ->with(['sender:id,name', 'attachments'])
                ->orderBy('created_at'),
        ]);

        return response()->json([
            'data' => $this->serializeThread($thread, true),
        ]);
    }

    public function update(Request $request, CrmThread $thread): JsonResponse
    {
        $user = $request->user();
        $this->authorizeThreadAccess($request, $thread);

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'details' => 'sometimes|nullable|array',
            'assigned_to_user_id' => 'sometimes|nullable|integer|exists:users,id',
        ]);

        if (array_key_exists('assigned_to_user_id', $validated)) {
            abort_unless($this->canAssignThreads($user), 403, 'Only super-admin can assign threads.');

            if ($validated['assigned_to_user_id']) {
                $assignee = User::query()
                    ->whereKey($validated['assigned_to_user_id'])
                    ->whereHas('roles', fn ($query) => $query->where('type', 'internal'))
                    ->first();

                abort_unless($assignee !== null, 422, 'Assignee must be an internal user.');
            }
        }

        $thread->fill($validated)->save();

        return response()->json([
            'data' => $this->serializeThread($thread->fresh()->load([
                'company:id,company_name',
                'createdBy:id,name',
                'assignedTo:id,name',
                'messages' => fn ($query) => $query
                    ->with(['sender:id,name', 'attachments'])
                    ->orderBy('created_at'),
            ]), true),
        ]);
    }

    public function close(Request $request, CrmThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($request, $thread);

        $thread->update([
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        return response()->json([
            'data' => $this->serializeThread($thread->fresh()->load([
                'company:id,company_name',
                'createdBy:id,name',
                'assignedTo:id,name',
                'messages' => fn ($query) => $query
                    ->with(['sender:id,name', 'attachments'])
                    ->orderBy('created_at'),
            ]), true),
        ]);
    }

    public function reopen(Request $request, CrmThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($request, $thread);

        $thread->update([
            'is_closed' => false,
            'closed_at' => null,
        ]);

        return response()->json([
            'data' => $this->serializeThread($thread->fresh()->load([
                'company:id,company_name',
                'createdBy:id,name',
                'assignedTo:id,name',
                'messages' => fn ($query) => $query
                    ->with(['sender:id,name', 'attachments'])
                    ->orderBy('created_at'),
            ]), true),
        ]);
    }

    private function applyVisibilityScope($query, User $user): void
    {
        if ($this->canAssignThreads($user)) {
            return;
        }

        if ($this->isInternalStaff($user)) {
            $query->where('assigned_to_user_id', $user->id);
            return;
        }

        $query->where('company_id', $user->company_id);
    }

    private function authorizeThreadAccess(Request $request, CrmThread $thread): void
    {
        $user = $request->user();

        if ($this->canAssignThreads($user)) {
            return;
        }

        if ($this->isInternalStaff($user)) {
            abort_unless((int) $thread->assigned_to_user_id === (int) $user->id, 403);
            return;
        }

        abort_unless((int) $user->company_id === (int) $thread->company_id, 403);
    }

    private function canAssignThreads(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    private function isInternalStaff(User $user): bool
    {
        return $user->roles()->where('type', 'internal')->exists();
    }

    private function serializeThread(CrmThread $thread, bool $includeMessages = false): array
    {
        return [
            'id' => $thread->id,
            'subject' => $thread->subject,
            'category' => $thread->category,
            'is_closed' => (bool) $thread->is_closed,
            'company' => $thread->company ? [
                'id' => $thread->company->id,
                'company_name' => $thread->company->company_name,
            ] : null,
            'created_by' => $thread->createdBy ? [
                'id' => $thread->createdBy->id,
                'name' => $thread->createdBy->name,
            ] : null,
            'assigned_to' => $thread->assignedTo ? [
                'id' => $thread->assignedTo->id,
                'name' => $thread->assignedTo->name,
            ] : null,
            'created_at' => $thread->created_at?->toISOString(),
            'created_at_human' => $thread->created_at?->diffForHumans(),
            'last_message_at' => $thread->last_message_at?->toISOString(),
            'last_message_at_human' => $thread->last_message_at?->diffForHumans(),
            'messages' => $includeMessages
                ? $thread->messages->map(fn (CrmMessage $message) => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'is_internal' => (bool) $message->is_internal,
                    'created_at' => $message->created_at?->toISOString(),
                    'created_at_human' => $message->created_at?->diffForHumans(),
                    'sender' => $message->sender ? [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ] : null,
                    'attachments' => $message->attachments->map(fn ($attachment) => [
                        'id' => $attachment->id,
                        'original_name' => $attachment->original_name,
                        'mime_type' => $attachment->mime_type,
                        'size_bytes' => $attachment->size_bytes,
                        'preview_url' => Storage::disk($attachment->disk)->url($attachment->path),
                        'download_url' => route('crm.attachments.download', $attachment),
                    ])->values(),
                ])->values()
                : [],
        ];
    }
}
