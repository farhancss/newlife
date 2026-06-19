@php
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
@endphp

<header class="sticky top-0 z-40 border-b border-gray-200 bg-white">
    <div class="flex h-14 items-center justify-between gap-3 px-4 lg:px-6">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button
                type="button"
                class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 xl:flex"
                @click="$store.sidebar.toggleExpanded()"
                aria-label="Toggle Sidebar"
            >
                <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                    <path d="M1 1H15M1 6H9M1 11H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>
            <button
                type="button"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-50 xl:hidden"
                @click="$store.sidebar.toggleMobileOpen()"
                aria-label="Toggle Mobile Menu"
            >
                <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                    <path d="M1 1H15M1 6H9M1 11H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>

            <div class="min-w-0">
                <p class="truncate text-xs text-gray-500">{{ $portalLabel }} Portal</p>
                <h1 class="truncate text-base font-semibold text-gray-900">{{ $pageHeading }}</h1>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            @if ($portal === 'student')
                <x-portal.profile-completion-badge :completion="$profileCompletion ?? null" compact />

                <a href="{{ route('student.notifications') }}"
                    class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 p-2 text-gray-600 hover:bg-gray-50"
                    aria-label="Notifications">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                    </svg>
                    <span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full border border-white bg-error-500"></span>
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
