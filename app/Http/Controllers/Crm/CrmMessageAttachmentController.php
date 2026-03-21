<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmMessage;
use App\Models\CrmMessageAttachment;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrmMessageAttachmentController extends Controller
{
    public function store(Request $request, CrmThread $thread, CrmMessage $message): JsonResponse
    {
        $user = $request->user();
        $this->authorizeThreadAccess($request, $thread);

        abort_unless($message->thread_id === $thread->id, 404);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $validated['file'];
        $disk = 'public';
        $folder = "crm/company-{$thread->company_id}/thread-{$thread->id}/message-{$message->id}";
        $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($folder, $name, $disk);

        $attachment = CrmMessageAttachment::create([
            'thread_id' => $thread->id,
            'message_id' => $message->id,
            'uploaded_by_user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Attachment uploaded successfully.',
            'data' => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size_bytes' => $attachment->size_bytes,
                'download_url' => route('crm.attachments.download', $attachment),
            ],
        ], 201);
    }

    public function download(Request $request, CrmMessageAttachment $attachment)
    {
        $thread = $attachment->thread;
        $this->authorizeThreadAccess($request, $thread);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
        );
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
