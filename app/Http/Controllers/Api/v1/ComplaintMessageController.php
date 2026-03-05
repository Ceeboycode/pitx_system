<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\ComplaintMessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplaintMessageController extends Controller
{
    public function index(Request $request, Complaint $complaint)
    {
        if ($complaint->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = ComplaintMessage::query()
            ->where('complaint_id', $complaint->id)
            ->with(['attachments'])
            ->oldest()
            ->get();

        return response()->json($messages);
    }

    public function store(Request $request, Complaint $complaint)
    {
        if ($complaint->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'message' => ['nullable','string'],
            'attachments' => ['nullable','array'],
            'attachments.*' => ['file','max:20480'],
        ]);

        $files = $request->file('attachments', []);

        if (empty($data['message']) && empty($files)) {
            return response()->json(['message' => 'Message or attachment is required.'], 422);
        }

        $msg = DB::transaction(function () use ($request, $complaint, $data, $files) {
            $msg = ComplaintMessage::create([
                'complaint_id' => $complaint->id,
                'sender_user_id' => $request->user()->id,
                'message' => $data['message'] ?? null,
                'is_internal' => false,
            ]);

            foreach ($files as $file) {
                $path = $file->store("complaints/{$complaint->id}/messages/{$msg->id}", 'public');

                ComplaintMessageAttachment::create([
                    'complaint_message_id' => $msg->id,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $msg->load('attachments');
        });

        return response()->json($msg, 201);
    }
}