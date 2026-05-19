@php
    $portalLabel = request()->segment(1) === 'admin' ? 'Admin' : 'Student';
@endphp

<header class="sticky top-0 z-99999 flex w-full border-b border-gray-200 bg-white">
    <div class="flex w-full items-center justify-between gap-3 px-4 py-3 lg:px-6">
        <div class="flex items-center gap-3">
            <button
                class="hidden h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 xl:flex"
                :class="{ 'bg-gray-100': !$store.sidebar.isExpanded }"
                @click="$store.sidebar.toggleExpanded()"
                aria-label="Toggle Sidebar"
            >
                <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                    <path d="M1 1H15M1 6H9M1 11H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>
            <button
                class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 xl:hidden"
                :class="{ 'bg-gray-100': $store.sidebar.isMobileOpen }"
                @click="$store.sidebar.toggleMobileOpen()"
                aria-label="Toggle Mobile Menu"
            >
                <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                    <path d="M1 1H15M1 6H9M1 11H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">New Life Campus</p>
                <h2 class="text-sm font-semibold text-gray-900">{{ $portalLabel }} Portal</h2>
            </div>
        </div>

        <x-header.user-dropdown />
    </div>
</header>
