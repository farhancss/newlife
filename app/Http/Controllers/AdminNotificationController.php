<?php

namespace App\Http\Controllers;

use App\Enums\NotificationCategory;
use App\Models\PortalNotification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = (string) $request->query('category', '');
        $emailStatus = (string) $request->query('status', '');

        $notifications = PortalNotification::query()
            ->with(['user.studentProfile', 'createdBy'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user.studentProfile', function ($profileQuery) use ($search): void {
                            $profileQuery->where('new_life_id', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($category, NotificationCategory::all(), true), fn ($query) => $query->where('category', $category))
            ->when($emailStatus !== '', fn ($query) => $query->where('email_status', $emailStatus))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('pages.portal.admin.notifications', [
            'title' => 'Notifications',
            'pageHeading' => 'Notifications',
            'portal' => 'admin',
            'notifications' => $notifications,
            'search' => $search,
            'categoryFilter' => $category,
            'statusFilter' => $emailStatus,
            'categories' => NotificationCategory::all(),
            'emailStatuses' => [
                PortalNotification::EMAIL_SENT,
                PortalNotification::EMAIL_QUEUED,
                PortalNotification::EMAIL_FAILED,
                PortalNotification::EMAIL_SKIPPED,
                PortalNotification::EMAIL_NONE,
            ],
        ]);
    }

    public function resend(PortalNotification $notification): RedirectResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        $this->notifications->resend($notification, $actor);

        return redirect()
            ->route('admin.notifications')
            ->with('status', 'Notification re-sent.');
    }
}
