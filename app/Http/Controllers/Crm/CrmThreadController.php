<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\AssignCrmThreadRequest;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CrmThreadController extends Controller
{
    // public function index(Request $request)
    // {
    //     $user = $request->user();

    //     $query = CrmThread::query()
    //         ->with(['company:id,company_name', 'createdBy:id,name', 'assignedTo:id,name'])
    //         ->latest('last_message_at');

    //     if (!$this->isTerminalStaff($user)) {
    //         // Company users only see their company's threads
    //         $query->where('company_id', $user->company_id);
    //     }

    //     return response()->json([
    //         'data' => $query->paginate(20),
    //     ]);
    // }

    public function index(Request $request)
    {
        $user = $request->user();
        $canAssign = $user->hasRole('super-admin');

        $query = CrmThread::query()
            ->with(['company:id,company_name'])
            ->latest('last_message_at');

        if ($user->hasRole('admin') && ! $canAssign) {
            $query->where('assigned_to_user_id', $user->id);
        }

        $threads = $query->paginate(20);

        return Inertia::render('Crm/Threads/Index', [
            'threads' => $threads,
            'filters' => request()->only(['category','status','search']),
            'canAssign' => $canAssign,
            'assignableAdmins' => $canAssign
                ? $this->assignableAdmins()
                : [],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Only bus company users (customers) should create company CRM threads
        // If terminal staff should also create, remove this check.
        if ($this->isTerminalStaff($user)) {
            // optional: allow terminal to create on behalf of a company
        }

        $validated = $request->validate([
            'category' => 'required|in:compliance,system',
            'subject'  => 'required|string|max:255',
            'body'     => 'required|string',
            'details'  => 'nullable|array',
        ]);

        $thread = DB::transaction(function () use ($user, $validated) {
            $thread = CrmThread::create([
                'company_id'          => $user->company_id,
                'created_by_user_id'  => $user->id,
                'category'            => $validated['category'],
                'subject'             => $validated['subject'],
                'details'             => $validated['details'] ?? null,
                'last_message_at'     => now(),
            ]);

            $thread->messages()->create([
                'sender_user_id' => $user->id,
                'body'           => $validated['body'],
                'is_internal'    => false,
            ]);

            return $thread;
        });

        return response()->json([
            'data' => $thread->load(['company:id,company_name', 'createdBy:id,name', 'assignedTo:id,name']),
        ], 201);
    }

    public function show(Request $request, CrmThread $thread)
    {
        $this->authorizeThreadAccess($request, $thread);

        return response()->json([
            'data' => $thread->load([
                'company:id,company_name',
                'createdBy:id,name',
                'assignedTo:id,name',
                'messages.sender:id,name',
                'messages.attachments',
            ]),
        ]);
    }

    public function assign(AssignCrmThreadRequest $request, CrmThread $thread)
    {
        $this->authorizeThreadAccess($request, $thread);

        $validated = $request->validated();

        $thread->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
        ]);

        return response()->json([
            'data' => $thread->fresh()->load([
                'company:id,company_name',
                'createdBy:id,name',
                'assignedTo:id,name',
            ]),
        ]);
    }

    public function update(Request $request, CrmThread $thread)
    {
        $user = $request->user();
        $this->authorizeThreadAccess($request, $thread);

        $validated = $request->validate([
            'subject'             => 'sometimes|string|max:255',
            'details'             => 'sometimes|nullable|array',
            'assigned_to_user_id' => 'sometimes|nullable|integer|exists:users,id',
        ]);

        // Only terminal staff should be able to assign
        if (array_key_exists('assigned_to_user_id', $validated) && !$this->isTerminalStaff($user)) {
            abort(403, 'Only terminal staff can assign threads.');
        }

        $thread->fill($validated)->save();

        return response()->json([
            'data' => $thread->fresh()->load(['company:id,company_name', 'createdBy:id,name', 'assignedTo:id,name']),
        ]);
    }

    public function close(Request $request, CrmThread $thread)
    {
        $this->authorizeThreadAccess($request, $thread);

        // optionally: only terminal can close; or allow company to close too
        $thread->update([
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        return response()->json(['data' => $thread->fresh()]);
    }

    public function reopen(Request $request, CrmThread $thread)
    {
        $this->authorizeThreadAccess($request, $thread);

        // optionally: only terminal can reopen
        $thread->update([
            'is_closed' => false,
            'closed_at' => null,
        ]);

        return response()->json(['data' => $thread->fresh()]);
    }

    private function authorizeThreadAccess(Request $request, CrmThread $thread): void
    {
        $user = $request->user();

        if ($user->hasRole('super-admin')) {
            return;
        }

        if ($user->hasRole('admin')) {
            abort_unless((int) $thread->assigned_to_user_id === (int) $user->id, 403);
            return;
        }

        if ($this->isTerminalStaff($user)) {
            return;
        }

        if ((int) $user->company_id !== (int) $thread->company_id) {
            abort(403);
        }
    }

    private function isTerminalStaff($user): bool
    {
        return $user->roles()->where('type', 'internal')->exists();
    }

    /**
     * @return Collection<int, array{id:int,name:string,email:string}>
     */
    private function assignableAdmins(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => (string) $user->email,
            ]);
    }
}
