@extends('layouts.app')

@php
    use App\Enums\NotificationCategory;
    use App\Models\PortalNotification;

    $statusBadge = function (string $status): string {
        return match ($status) {
            PortalNotification::EMAIL_SENT => 'bg-emerald-50 text-emerald-700',
            PortalNotification::EMAIL_QUEUED => 'bg-amber-50 text-amber-700',
            PortalNotification::EMAIL_FAILED => 'bg-error-50 text-error-700',
            default => 'bg-gray-100 text-gray-600',
        };
    };
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Notifications</h1>
                <p class="mt-1 text-sm text-gray-600">Delivery log across all students. Resend failed or missed messages.</p>
            </div>
            <a href="{{ route('admin.notifications.create') }}"
                class="inline-flex h-10 flex-none items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2.94 2.94a1.5 1.5 0 011.62-.33l12 5a1.5 1.5 0 010 2.78l-12 5A1.5 1.5 0 012.5 14V6a1.5 1.5 0 01.44-1.06zM4 6.6v2.65l5.2 0.75L4 10.75V13.4l11-5.4L4 6.6z"/></svg>
                Send notification
            </a>
        </div>

        <form method="GET" class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="q" class="mb-1.5 block text-xs font-medium text-gray-500">Search</label>
                <input id="q" name="q" type="text" value="{{ $search }}" placeholder="Student, email, New Life ID, or title"
                    class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
            </div>
            <div>
                <label for="category" class="mb-1.5 block text-xs font-medium text-gray-500">Category</label>
                <select id="category" name="category" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($categoryFilter === $category)>{{ NotificationCategory::label($category) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-xs font-medium text-gray-500">Email status</label>
                <select id="status" name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All</option>
                    @foreach ($emailStatuses as $emailStatus)
                        <option value="{{ $emailStatus }}" @selected($statusFilter === $emailStatus)>{{ ucfirst($emailStatus) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="h-10 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
        </form>

        <x-portal.data-table table-class="min-w-[860px]">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Category</th>
                    <th>Notification</th>
                    <th>Email</th>
                    <th>Read</th>
                    <th>Sent</th>
                    <th data-sortable="false"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($notifications as $notification)
                    <tr class="hover:bg-gray-50/80">
                        <td class="font-medium text-gray-900">
                            {{ $notification->user->name }}
                            @if ($notification->user->studentProfile)
                                <span class="block font-mono text-xs text-gray-400">{{ $notification->user->studentProfile->new_life_id }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $notification->categoryLabel() }}</span>
                        </td>
                        <td>
                            <p class="font-medium text-gray-900">{{ $notification->title }}</p>
                            @if ($notification->body)
                                <p class="mt-0.5 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($notification->body, 80) }}</p>
                            @endif
                            @if ($notification->createdBy)
                                <p class="mt-0.5 text-xs text-gray-400">Sent by {{ $notification->createdBy->name }}</p>
                            @endif
                        </td>
                        <td>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $statusBadge($notification->email_status) }}">
                                {{ $notification->email_status }}
                            </span>
                            @if ($notification->email_attempts > 1)
                                <span class="block text-xs text-gray-400">{{ $notification->email_attempts }} attempts</span>
                            @endif
                        </td>
                        <td class="text-xs">
                            @if ($notification->isUnread())
                                <span class="text-amber-600">Unread</span>
                            @else
                                <span class="text-gray-400">Read</span>
                            @endif
                        </td>
                        <td class="text-xs text-gray-500">{{ $notification->created_at->format('M j, g:i A') }}</td>
                        <td class="text-right whitespace-nowrap">
                            <form action="{{ route('admin.notifications.resend', $notification) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                                    Resend
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <p class="text-sm font-medium text-gray-900">No notifications yet</p>
                            <p class="mt-1 text-sm text-gray-500">Delivery records appear here as students receive updates.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-portal.data-table>

        @if ($notifications->hasPages())
            <div>{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
