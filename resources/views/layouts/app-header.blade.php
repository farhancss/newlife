@php
    use App\Helpers\MenuHelper;

    $portal = request()->segment(1) === 'admin' ? 'admin' : 'student';
    $portalLabel = $portal === 'admin' ? 'Admin' : 'Student';
    $user = auth()->user();
    $userName = $user->name ?? ucfirst($portal) . ' User';
    $pageHeading = $pageHeading ?? ($title ?? 'Dashboard');
    $initials = collect(explode(' ', $userName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('') ?: 'NL';
    $hasUnreadNotifications = ($notificationUnreadCount ?? 0) > 0;
@endphp

<header class="sticky top-0 z-40 border-b border-gray-200 bg-white">
    <div class="flex h-16 items-center justify-between gap-4 pr-4 sm:pr-6">
        <div class="flex h-full items-center gap-4 border-l border-gray-200 pl-4 sm:pl-6">
            <button
                type="button"
                class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:bg-gray-50 xl:flex"
                @click="$store.sidebar.toggleExpanded()"
                aria-label="Toggle Sidebar"
            >
                <svg width="18" height="14" viewBox="0 0 16 12" fill="none" aria-hidden="true">
                    <path d="M1 1H15M1 6H9M1 11H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>
            <button
                type="button"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:bg-gray-50 xl:hidden"
                @click="$store.sidebar.toggleMobileOpen()"
                aria-label="Toggle Mobile Menu"
            >
                <svg width="18" height="14" viewBox="0 0 16 12" fill="none" aria-hidden="true">
                    <path d="M1 1H15M1 6H9M1 11H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>
            <div class="min-w-0">
                <p class="truncate text-xs text-gray-900 font-semibold">{{ $portalLabel }} Portal</p>
                <h1 class="truncate text-base text-xs text-gray-500">{{ $pageHeading }}</h1>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-3 sm:gap-4">
            @if ($portal === 'student')
                <x-portal.profile-completion-badge :completion="$profileCompletion ?? null" compact />

                <a href="{{ route('student.notifications') }}"
                    class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:bg-gray-50"
                    aria-label="Notifications">
                    <span class="flex h-5 w-5 items-center justify-center [&_svg]:h-5 [&_svg]:w-5" aria-hidden="true">
                        {!! MenuHelper::getIconSvg('bell') !!}
                    </span>
                    @if ($hasUnreadNotifications)
                        <span class="absolute top-1 right-0.5 h-2.5 w-2.5 translate-x-1/2 -translate-y-1/2 rounded-full bg-orange-500 ring-2 ring-white" aria-hidden="true"></span>
                    @endif
                </a>
            @endif

            <x-header.user-dropdown
                :user-name="$userName"
                :initials="$initials"
                :portal="$portal"
                :avatar-src="$user?->avatarUrl()"
            />
        </div>
    </div>
</header>
