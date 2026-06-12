<?php

namespace App\Services;

use App\Enums\ContainerStatus;
use App\Enums\NotificationCategory;
use App\Enums\RetailPackageStatus;
use App\Mail\PortalNotificationMail;
use App\Models\Container;
use App\Models\NotificationPreference;
use App\Models\PortalNotification;
use App\Models\RetailPackage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Unified notification pipeline. Every domain event funnels through here so the
 * in-app inbox, the admin delivery log, and the email channel stay consistent
 * and honour each student's channel preferences.
 */
class NotificationService
{
    /**
     * Container statuses that produce a student-facing notification.
     *
     * @var array<string, array{title: string, body: string}>
     */
    private const CONTAINER_EVENTS = [
        ContainerStatus::SHIPPED_TO_HOME => [
            'title' => 'Your container is on its way',
            'body' => 'Good news — your New Life container has shipped to your home address. Track its progress from the My Move page.',
        ],
        ContainerStatus::DELIVERED_TO_HOME => [
            'title' => 'Your container was delivered home',
            'body' => 'Your container has arrived at your home. You can start packing whenever you are ready.',
        ],
        ContainerStatus::PICKUP_SCHEDULED => [
            'title' => 'Pickup scheduled',
            'body' => 'A pickup has been scheduled for your packed container. Please have it ready by the scheduled date.',
        ],
        ContainerStatus::OUT_FOR_DELIVERY => [
            'title' => 'Out for dorm delivery',
            'body' => 'Your container is out for delivery to your dorm. It should arrive shortly.',
        ],
        ContainerStatus::DELIVERED_TO_DORM => [
            'title' => 'Delivered to your dorm',
            'body' => 'Your container has been delivered to your dorm. Welcome to campus!',
        ],
    ];

    /**
     * Retail package statuses that produce a student-facing notification.
     *
     * @var array<string, array{title: string, body: string}>
     */
    private const RETAIL_EVENTS = [
        RetailPackageStatus::RECEIVED_AT_HUB => [
            'title' => 'Retail package received at hub',
            'body' => 'We received one of your retail packages at the New Life hub and it is being processed for delivery.',
        ],
        RetailPackageStatus::STAGED_FOR_DELIVERY => [
            'title' => 'Retail delivery scheduled',
            'body' => 'Your retail package is staged and scheduled for delivery to your dorm.',
        ],
        RetailPackageStatus::DELIVERED_TO_DORM => [
            'title' => 'Retail package delivered',
            'body' => 'Your retail package has been delivered to your dorm.',
        ],
    ];

    /**
     * Create an in-app notification for the recipient and dispatch any enabled
     * channels (email today, SMS reserved for a future driver).
     *
     * @param  array<string, mixed>  $meta
     */
    public function notify(
        User $recipient,
        string $category,
        string $type,
        string $title,
        string $body,
        ?string $url = null,
        ?User $actor = null,
        array $meta = [],
    ): PortalNotification {
        $preference = $this->preferenceFor($recipient);

        $notification = PortalNotification::query()->create([
            'user_id' => $recipient->id,
            'created_by_user_id' => $actor?->id,
            'category' => $category,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'email_status' => PortalNotification::EMAIL_NONE,
            'meta' => $meta === [] ? null : $meta,
        ]);

        $this->dispatchEmail($notification, $recipient, $preference);

        return $notification;
    }

    public function containerStatusChanged(Container $container, string $toStatus): ?PortalNotification
    {
        $event = self::CONTAINER_EVENTS[$toStatus] ?? null;

        if ($event === null) {
            return null;
        }

        $recipient = $container->studentProfile->user;

        return $this->notify(
            recipient: $recipient,
            category: NotificationCategory::SHIPMENT,
            type: 'container.' . $toStatus,
            title: $event['title'],
            body: $event['body'] . ' (Container ' . $container->code . ')',
            url: route('student.move-tracking'),
            meta: ['container_id' => $container->id, 'status' => $toStatus],
        );
    }

    public function retailPackageStatusChanged(RetailPackage $package, string $toStatus): ?PortalNotification
    {
        $event = self::RETAIL_EVENTS[$toStatus] ?? null;

        if ($event === null) {
            return null;
        }

        $recipient = $package->studentProfile->user;

        return $this->notify(
            recipient: $recipient,
            category: NotificationCategory::RETAIL,
            type: 'retail.' . $toStatus,
            title: $event['title'],
            body: $event['body'] . ' (' . $package->retailer . ' — ' . $package->description . ')',
            url: route('student.retail-packages'),
            meta: ['retail_package_id' => $package->id, 'status' => $toStatus],
        );
    }

    public function markRead(PortalNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }
    }

    public function markAllRead(User $user): int
    {
        return PortalNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCount(User $user): int
    {
        return PortalNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Re-attempt email delivery for an existing notification (admin action).
     */
    public function resend(PortalNotification $notification, ?User $actor = null): PortalNotification
    {
        $recipient = $notification->user;
        $preference = $this->preferenceFor($recipient);

        $this->dispatchEmail($notification, $recipient, $preference, force: true);

        return $notification->refresh();
    }

    public function preferenceFor(User $user): NotificationPreference
    {
        $preference = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_enabled' => true,
                'sms_enabled' => true,
                'sms_number' => $user->studentProfile?->phone,
                'parent_cc_enabled' => true,
            ],
        );

        $user->setRelation('notificationPreference', $preference);

        return $preference;
    }

    private function dispatchEmail(
        PortalNotification $notification,
        User $recipient,
        NotificationPreference $preference,
        bool $force = false,
    ): void {
        if (! $force && ! $preference->email_enabled) {
            $notification->forceFill(['email_status' => PortalNotification::EMAIL_SKIPPED])->save();

            return;
        }

        if ($recipient->email === null || $recipient->email === '') {
            $notification->forceFill(['email_status' => PortalNotification::EMAIL_SKIPPED])->save();

            return;
        }

        $profile = $recipient->studentProfile;
        $greeting = $profile?->first_name ?: $recipient->name;

        $mail = new PortalNotificationMail(
            subjectLine: $notification->title,
            heading: $notification->title,
            bodyText: (string) $notification->body,
            actionUrl: $notification->url,
            greetingName: $greeting,
        );

        $cc = $this->parentCc($notification, $preference, $profile);

        try {
            $pending = Mail::to($recipient->email);

            if ($cc !== null) {
                $pending->cc($cc);
            }

            $pending->queue($mail);

            $notification->forceFill([
                'email_status' => PortalNotification::EMAIL_SENT,
                'emailed_at' => now(),
                'email_attempts' => $notification->email_attempts + 1,
            ])->save();
        } catch (\Throwable $e) {
            Log::error('Failed to queue portal notification email', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            $notification->forceFill([
                'email_status' => PortalNotification::EMAIL_FAILED,
                'email_attempts' => $notification->email_attempts + 1,
            ])->save();
        }
    }

    private function parentCc(
        PortalNotification $notification,
        NotificationPreference $preference,
        ?\App\Models\StudentProfile $profile,
    ): ?string {
        if (! $preference->parent_cc_enabled) {
            return null;
        }

        if (! in_array($notification->category, NotificationCategory::parentCcCategories(), true)) {
            return null;
        }

        $parentEmail = $profile?->parentGuardian?->email;

        return ($parentEmail !== null && $parentEmail !== '') ? $parentEmail : null;
    }
}
