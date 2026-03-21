<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreCrmMessageRequest;
use App\Models\CrmMessage;
use App\Models\CrmMessageAttachment;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmMessageController extends Controller
{
    public function index(Request $request, CrmThread $thread): JsonResponse
    {
        $this->authorizeThreadAccess($request, $thread);

        $messages = CrmMessage::query()
            ->where('thread_id', $thread->id)
            ->with(['sender:id,name', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json(['data' => $messages]);
    }

    public function store(StoreCrmMessageRequest $request, CrmThread $thread): JsonResponse
    {
        $user = $request->user();
        $this->authorizeThreadAccess($request, $thread);

        if ($thread->is_closed && ! $this->isTerminalStaff($user)) {
            abort(422, 'Thread is closed.');
        }

        $validated = $request->validated();

        $isInternal = (bool) ($validated['is_internal'] ?? false);

        if ($isInternal && ! $this->isTerminalStaff($user)) {
            abort(403, 'Only terminal staff can post internal notes.');
        }

        $message = $thread->messages()->create([
            'sender_user_id' => $user->id,
            'body' => $validated['body'],
            'is_internal' => $isInternal,
        ]);

        $thread->update(['last_message_at' => now()]);

        return response()->json([
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'is_internal' => (bool) $message->is_internal,
                'created_at' => $message->created_at?->toISOString(),
                'created_at_human' => $message->created_at?->diffForHumans(),
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'attachments' => [],
            ],
        ], 201);
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
}
