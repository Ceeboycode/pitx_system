<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user
            && $notification->notifiable_type === $user::class
            && (int) $notification->notifiable_id === (int) $user->id,
            403
        );

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return back();
    }
}
