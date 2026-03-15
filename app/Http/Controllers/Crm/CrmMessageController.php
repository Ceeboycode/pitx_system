<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmMessage;
use App\Models\CrmThread;
use Illuminate\Http\Request;

class CrmMessageController extends Controller
{
    public function index(Request $request, CrmThread $thread)
    {
        $this->authorizeThreadAccess($request, $thread);

        $messages = CrmMessage::query()
            ->where('thread_id', $thread->id)
            ->with(['sender:id,name', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json(['data' => $messages]);
    }

    public function store(Request $request, CrmThread $thread)
    {
        $user = $request->user();
        $this->authorizeThreadAccess($request, $thread);

        if ($thread->is_closed && !$this->isTerminalStaff($user)) {
            abort(422, 'Thread is closed.');
        }

        $validated = $request->validate([
            'body'        => 'required|string',
            'is_internal' => 'sometimes|boolean',
        ]);

        // Company users cannot create internal notes
        $isInternal = (bool) ($validated['is_internal'] ?? false);
        if ($isInternal && !$this->isTerminalStaff($user)) {
            abort(403, 'Only terminal staff can post internal notes.');
        }

        $message = $thread->messages()->create([
            'sender_user_id' => $user->id,
            'body'           => $validated['body'],
            'is_internal'    => $isInternal,
        ]);

        $thread->update(['last_message_at' => now()]);

        return response()->json([
            'data' => $message->load(['sender:id,name', 'attachments']),
        ], 201);
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
}
