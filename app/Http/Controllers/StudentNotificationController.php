<?php

namespace App\Http\Controllers;

use App\Models\PortalNotification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StudentNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user->portalNotifications()->paginate(20);

        return view('pages.portal.student.notifications', [
            'title' => 'Notifications',
            'portal' => 'student',
            'notifications' => $notifications,
            'unreadCount' => $this->notifications->unreadCount($user),
        ]);
    }

    public function markRead(PortalNotification $notification): RedirectResponse
    {
        $this->ensureOwner($notification);

        $this->notifications->markRead($notification);

        if ($notification->url !== null && $notification->url !== '') {
            return redirect()->to($notification->url);
        }

        return redirect()->route('student.notifications');
    }

    public function markAllRead(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->notifications->markAllRead($user);

        return redirect()
            ->route('student.notifications')
            ->with('status', 'All notifications marked as read.');
    }

    private function ensureOwner(PortalNotification $notification): void
    {
        abort_if($notification->user_id !== Auth::id(), Response::HTTP_FORBIDDEN);
    }
}
