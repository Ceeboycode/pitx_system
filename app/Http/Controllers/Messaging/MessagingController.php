<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Models\CrmMessage;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessagingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureIsParticipant($user);

        $threads = CrmThread::query()
            ->where('category', 'platform_message')
            ->tap(fn ($q) => $this->applyScope($q, $user))
            ->with(['createdBy:id,name'])
            ->withCount('messages')
            ->latest('last_message_at')
            ->latest('created_at')
            ->get()
            ->map(fn (CrmThread $thread) => $this->serializeThread($thread));

        return response()->json(['data' => $threads]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureIsParticipant($user);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        $thread = DB::transaction(function () use ($user, $validated) {
            $thread = CrmThread::create([
                'company_id'          => $this->isInternal($user) ? null : $user->company_id,
                'created_by_user_id'  => $user->id,
                'category'            => 'platform_message',
                'subject'             => $validated['subject'],
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
            'data' => $this->serializeThread(
                $thread->load(['createdBy:id,name'])->loadCount('messages')
            ),
        ], 201);
    }

    public function messages(Request $request, CrmThread $thread): JsonResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user, $thread);

        $messages = CrmMessage::query()
            ->where('thread_id', $thread->id)
            ->with(['sender:id,name'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (CrmMessage $message) => $this->serializeMessage($message));

        return response()->json(['data' => $messages]);
    }

    public function send(Request $request, CrmThread $thread): JsonResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user, $thread);

        abort_if($thread->is_closed, 403, 'This thread has been closed.');

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $message = $thread->messages()->create([
            'sender_user_id' => $user->id,
            'body'           => $validated['body'],
            'is_internal'    => false,
        ]);

        $thread->update(['last_message_at' => now()]);

        $message->load(['sender:id,name']);

        return response()->json(['data' => $this->serializeMessage($message)], 201);
    }

    private function applyScope($query, User $user): void
    {
        if ($this->isInternal($user)) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $user->company_id);
        }
    }

    private function authorizeAccess(User $user, CrmThread $thread): void
    {
        abort_unless($thread->category === 'platform_message', 404);

        if ($this->isInternal($user)) {
            abort_unless($thread->company_id === null, 403);
        } else {
            abort_unless((int) $thread->company_id === (int) $user->company_id, 403);
        }
    }

    private function ensureIsParticipant(User $user): void
    {
        abort_unless(
            $this->isInternal($user) || $this->isExternal($user),
            403,
            'Only internal staff and company accounts can use messaging.'
        );
    }

    private function isInternal(User $user): bool
    {
        return $user->roles()->where('type', 'internal')->exists();
    }

    private function isExternal(User $user): bool
    {
        return $user->roles()->where('type', 'external')->exists();
    }

    private function serializeThread(CrmThread $thread): array
    {
        return [
            'id'                    => $thread->id,
            'subject'               => $thread->subject,
            'is_closed'             => (bool) $thread->is_closed,
            'created_by'            => $thread->createdBy ? [
                'id'   => $thread->createdBy->id,
                'name' => $thread->createdBy->name,
            ] : null,
            'messages_count'        => $thread->messages_count ?? 0,
            'last_message_at'       => $thread->last_message_at?->toISOString(),
            'last_message_at_human' => $thread->last_message_at?->diffForHumans(),
            'created_at'            => $thread->created_at?->toISOString(),
            'created_at_human'      => $thread->created_at?->diffForHumans(),
        ];
    }

    private function serializeMessage(CrmMessage $message): array
    {
        return [
            'id'               => $message->id,
            'body'             => $message->body,
            'created_at'       => $message->created_at?->toISOString(),
            'created_at_human' => $message->created_at?->diffForHumans(),
            'sender'           => $message->sender ? [
                'id'   => $message->sender->id,
                'name' => $message->sender->name,
            ] : null,
        ];
    }
}
