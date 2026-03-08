<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmMessage;
use App\Models\CrmMessageAttachment;
use App\Models\CrmThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrmMessageAttachmentController extends Controller
{
    public function store(Request $request, CrmThread $thread, CrmMessage $message)
    {
        $user = $request->user();
        $this->authorizeThreadAccess($request, $thread);

        if ($message->thread_id !== $thread->id) {
            abort(404);
        }

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        $file = $validated['file'];
        $disk = 'public';

        $folder = "crm/company-{$thread->company_id}/thread-{$thread->id}/message-{$message->id}";
        $name   = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($folder, $name, $disk);

        $attachment = CrmMessageAttachment::create([
            'thread_id'           => $thread->id,
            'message_id'          => $message->id,
            'uploaded_by_user_id' => $user->id,
            'disk'                => $disk,
            'path'                => $path,
            'original_name'       => $file->getClientOriginalName(),
            'mime_type'           => $file->getClientMimeType(),
            'size_bytes'          => $file->getSize(),
        ]);

        return response()->json([
            'data' => $attachment,
        ], 201);
    }

    public function download(Request $request, CrmMessageAttachment $attachment)
    {
        // Basic access control via thread scope
        $thread = $attachment->thread;
        $this->authorizeThreadAccess($request, $thread);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name
        );
    }

    private function authorizeThreadAccess(Request $request, CrmThread $thread): void
    {
        $user = $request->user();

        if ($this->isTerminalStaff($user)) {
            return;
        }

        if ((int) $user->company_id !== (int) $thread->company_id) {
            abort(403);
        }
    }

    private function isTerminalStaff($user): bool
    {
        return (string) optional($user->role)->type === 'internal';
    }
}