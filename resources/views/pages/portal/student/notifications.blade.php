@extends('layouts.app')

@php
    use App\Enums\NotificationCategory;

    $categoryBadge = function (string $category): string {
        return match ($category) {
            NotificationCategory::SHIPMENT => 'bg-brand-50 text-brand-700',
            NotificationCategory::RETAIL => 'bg-indigo-50 text-indigo-700',
            NotificationCategory::ACCOUNT => 'bg-amber-50 text-amber-700',
            NotificationCategory::DEADLINE => 'bg-rose-50 text-rose-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };
@endphp

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success" :message="session('status')" />
        @endif

        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Notifications</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Updates from your move, retail packages, and account.
                    @if ($unreadCount > 0)
                        <span class="font-semibold text-gray-900">{{ $unreadCount }} unread</span>
                    @endif
                </p>
            </div>
            @if ($unreadCount > 0)
                <form action="{{ route('student.notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <ul class="divide-y divide-gray-100">
                @forelse ($notifications as $notification)
                    <li @class([
                        'flex flex-col gap-3 px-5 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center sm:gap-4',
                        'bg-brand-50/40' => $notification->isUnread(),
                    ])>
                        <div class="flex min-w-0 flex-1 items-start gap-4">
                            @if ($notification->isUnread())
                                <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-600" aria-hidden="true"></span>
                            @else
                                <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-transparent" aria-hidden="true"></span>
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $categoryBadge($notification->category) }}">
                                        {{ $notification->categoryLabel() }}
                                    </span>
                                    <p @class([
                                        'text-sm text-gray-900',
                                        'font-semibold' => $notification->isUnread(),
                                        'font-medium' => ! $notification->isUnread(),
                                    ])>{{ $notification->title }}</p>
                                </div>
                                @if ($notification->body)
                                    <p class="mt-0.5 text-sm text-gray-600">{{ $notification->body }}</p>
                                @endif
                                <p class="mt-1 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        @if ($notification->isUnread() || $notification->url)
                            <div class="flex shrink-0 items-center justify-end gap-2 sm:pl-0">
                                @if ($notification->isUnread())
                                    <form action="{{ route('student.notifications.read', $notification) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center justify-center whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                                            Read
                                        </button>
                                    </form>
                                @endif

                                @if ($notification->url)
                                    @if ($notification->isUnread())
                                        <form action="{{ route('student.notifications.read', $notification) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="follow" value="1">
                                            <button type="submit"
                                                class="inline-flex items-center justify-center whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                                                View
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ $notification->url }}"
                                            class="inline-flex items-center justify-center whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                                            View
                                        </a>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-16 text-center">
                        <p class="text-sm font-medium text-gray-900">You're all caught up</p>
                        <p class="mt-1 text-sm text-gray-500">Notifications about your move and packages will appear here.</p>
                    </li>
                @endforelse
            </ul>
        </div>

        @if ($notifications->hasPages())
            <div>{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
