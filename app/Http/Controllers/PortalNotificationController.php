<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class PortalNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->portalUser($request);
        $notifications = $user->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->notificationData($request, $notification))
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        $user = $this->portalUser($request);
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->whereKey($notification)->firstOrFail();
        $notification->markAsRead();
        $destination = (string) ($notification->data['url'] ?? $this->dashboardRoute($user->role));

        if ($request->expectsJson()) {
            return response()->json([
                'redirect_url' => $destination,
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        return redirect()->to($destination);
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->portalUser($request);
        $user->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => 0]);
        }

        return back();
    }

    /** @return array<string, bool|string> */
    private function notificationData(Request $request, DatabaseNotification $notification): array
    {
        $routePrefix = $this->portalUser($request)->role === 'student' ? 'student' : 'instructor';

        return [
            'id' => $notification->id,
            'title' => (string) ($notification->data['title'] ?? 'Schedule notification'),
            'message' => (string) ($notification->data['message'] ?? 'Your schedule has changed.'),
            'created_at' => $notification->created_at?->diffForHumans() ?? 'Just now',
            'unread' => $notification->read_at === null,
            'read_url' => route($routePrefix.'.notifications.read', $notification->id),
        ];
    }

    private function portalUser(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['instructor', 'student'], true), 403);

        return $user;
    }

    private function dashboardRoute(string $role): string
    {
        return route($role === 'student' ? 'student.dashboard' : 'instructor.dashboard');
    }
}
