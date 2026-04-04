<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        $company = $user?->company;

        return [
            ...parent::share($request),

            'name' => config('app.name'),

            'quote' => [
                'message' => trim($message),
                'author' => trim($author),
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
            ],

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->profile_photo_path ? Storage::url($user->profile_photo_path) : null,
                    'type' => $user->type,
                    'status' => $user->status,
                    'must_change_password' => (bool) $user->must_change_password,
                ] : null,

                'company' => $company ? [
                    'id' => $company->id,
                    'company_name' => $company->company_name,
                    'company_code' => $company->company_code,
                    'status' => $company->status,
                    'logo_url' => $company->logo_url,
                ] : null,

                'permissions' => fn () => $user?->getAllPermissions()->pluck('name')->values() ?? [],
            ],

            'notifications' => $user ? [
                'unread_count' => $user->unreadNotifications()->count(),
                'items' => $user->notifications()
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(fn ($notification) => [
                        'id' => $notification->id,
                        'type' => $notification->data['type'] ?? null,
                        'title' => $notification->data['title'] ?? 'Notification',
                        'message' => $notification->data['message'] ?? '',
                        'data' => $notification->data,
                        'read_at' => $notification->read_at,
                        'created_at' => optional($notification->created_at)->diffForHumans(),
                    ]),
            ] : [
                'unread_count' => 0,
                'items' => [],
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
