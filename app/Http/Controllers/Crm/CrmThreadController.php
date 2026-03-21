<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\IndexCrmThreadRequest;
use App\Http\Requests\Crm\StoreCrmThreadRequest;
use App\Http\Requests\Crm\UpdateCrmThreadRequest;
use App\Models\CrmMessageAttachment;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CrmThreadController extends Controller
{
    public function index(IndexCrmThreadRequest $request): Response
    {
        $user = $request->user();
        $filters = $request->validated();
        $search = trim((string) ($filters['search'] ?? ''));
        $category = trim((string) ($filters['category'] ?? 'all'));
        $status = trim((string) ($filters['status'] ?? 'all'));

        $threads = CrmThread::query()
            ->with([
                'company:id,company_name',
                'createdBy:id,name',
                'assignedTo:id,name',
            ])
            ->withCount('messages')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('subject', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($company) => $company->where('company_name', 'like', "%{$search}%"))
                        ->orWhereHas('createdBy', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('assignedTo', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($category, ['compliance', 'system'], true), function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when(in_array($status, ['open', 'closed'], true), function ($query) use ($status) {
                $query->where('is_closed', $status === 'closed');
            })
            ->when(! $user->isSuperAdmin(), function ($query) use ($user) {
                $query->where('assigned_to_user_id', $user->id);
            })
            ->latest('last_message_at')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $threads->through(fn (CrmThread $thread) => $this->threadSummary($thread));

        $staffUsers = $user->isSuperAdmin()
            ? User::query()
                ->whereHas('roles', fn ($query) => $query->where('type', 'internal'))
                ->select(['id', 'name', 'email'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $staffUser) => [
                    'id' => $staffUser->id,
                    'name' => $staffUser->name,
                    'email' => $staffUser->email,
                    'label' => $staffUser->email ? "{$staffUser->name} ({$staffUser->email})" : $staffUser->name,
                ])
                ->values()
            : collect();

        return Inertia::render('Crm/Threads/Index', [
            'threads' => $threads,
            'filters' => [
                'search' => $search ?: null,
                'category' => $category,
                'status' => $status,
            ],
            'staffUsers' => $staffUsers,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'can_assign_threads' => $user->isSuperAdmin(),
            ],
        ]);
    }

    public function store(StoreCrmThreadRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

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
            'message' => 'Thread created successfully.',
            'data' => $this->threadDetail($thread->fresh()),
        ], 201);
    }

    public function show(Request $request, CrmThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($request, $thread);

        return response()->json([
            'data' => $this->threadDetail($thread),
        ]);
    }

    public function update(UpdateCrmThreadRequest $request, CrmThread $thread): JsonResponse
    {
        $user = $request->user();
        $this->authorizeThreadAccess($request, $thread);

        $validated = $request->validated();

        if (array_key_exists('assigned_to_user_id', $validated)) {
            abort_unless($user->isSuperAdmin(), 403, 'Only super-admin users can assign threads.');

            $assigneeId = $validated['assigned_to_user_id'];

            if ($assigneeId) {
                $isInternalAssignee = User::query()
                    ->whereKey($assigneeId)
                    ->whereHas('roles', fn ($query) => $query->where('type', 'internal'))
                    ->exists();

                abort_unless($isInternalAssignee, 422, 'Assigned user must be an internal staff member.');
            }
        }

        $thread->fill($validated)->save();

        return response()->json([
            'message' => 'Thread updated successfully.',
            'data' => $this->threadDetail($thread->fresh()),
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
            'message' => 'Thread closed successfully.',
            'data' => $this->threadDetail($thread->fresh()),
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
            'message' => 'Thread reopened successfully.',
            'data' => $this->threadDetail($thread->fresh()),
        ]);
    }

    private function authorizeThreadAccess(Request $request, CrmThread $thread): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($this->isTerminalStaff($user)) {
            abort_unless((int) $thread->assigned_to_user_id === (int) $user->id, 403);

            return;
        }

        abort_unless((int) $user->company_id === (int) $thread->company_id, 403);
    }

    private function isTerminalStaff(User $user): bool
    {
        return $user->isInternalUser();
    }

    /**
     * @return array<string, mixed>
     */
    private function threadSummary(CrmThread $thread): array
    {
        return [
            'id' => $thread->id,
            'subject' => $thread->subject,
            'category' => $thread->category,
            'is_closed' => (bool) $thread->is_closed,
            'messages_count' => (int) ($thread->messages_count ?? 0),
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
            'closed_at' => $thread->closed_at?->toISOString(),
            'closed_at_human' => $thread->closed_at?->diffForHumans(),
            'last_message_at' => $thread->last_message_at?->toISOString(),
            'last_message_at_human' => $thread->last_message_at?->diffForHumans(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function threadDetail(CrmThread $thread): array
    {
        $thread->loadMissing([
            'company:id,company_name',
            'createdBy:id,name',
            'assignedTo:id,name,email',
            'messages' => fn ($query) => $query->with([
                'sender:id,name',
                'attachments',
            ])->orderBy('created_at'),
        ]);

        return array_merge($this->threadSummary($thread), [
            'details' => $thread->details,
            'messages' => $thread->messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'body' => $message->body,
                    'is_internal' => (bool) $message->is_internal,
                    'created_at' => $message->created_at?->toISOString(),
                    'created_at_human' => $message->created_at?->diffForHumans(),
                    'sender' => $message->sender ? [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ] : null,
                    'attachments' => $message->attachments->map(function (CrmMessageAttachment $attachment) {
                        return [
                            'id' => $attachment->id,
                            'original_name' => $attachment->original_name,
                            'mime_type' => $attachment->mime_type,
                            'size_bytes' => $attachment->size_bytes,
                            'download_url' => route('crm.attachments.download', $attachment),
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }
}
