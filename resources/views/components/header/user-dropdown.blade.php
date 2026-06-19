<div class="relative" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false">
    <button
        type="button"
        class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white py-1 pl-1 pr-2 transition hover:border-brand-200 sm:gap-2.5 sm:pr-3"
        @click.prevent="dropdownOpen = !dropdownOpen"
    >
        @if ($avatarSrc)
            <img
                src="{{ $avatarSrc }}"
                alt="{{ $initials }}"
                class="h-9 w-9 shrink-0 rounded-lg object-cover"
            />
        @else
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-800 text-xs font-bold text-white">
                {{ $initials }}
            </span>
        @endif
        <span class="hidden min-w-0 text-left sm:block">
            <span class="block truncate text-sm font-semibold text-gray-900">{{ $userName }}</span>
            <span class="block text-xs text-gray-500">{{ ucfirst($portal) }} account</span>
        </span>
        <svg class="hidden h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 sm:block" :class="{ 'rotate-180': dropdownOpen }" viewBox="0 0 20 20" fill="none">
            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
    </button>

    <div
        x-show="dropdownOpen"
        x-transition
        class="absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-lg"
        style="display: none;"
    >
        <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-sm font-semibold text-gray-900">{{ $userName }}</p>
            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email ?? '' }}</p>
        </div>
        <div class="p-2">
            <a href="/{{ $portal }}/profile" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                My Profile
            </a>
            <a href="/{{ $portal }}/change-password" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Change Password
            </a>
        </div>
        <div class="border-t border-gray-100 p-2">
            <a href="{{ route('logout') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-error-600 hover:bg-error-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </a>
        </div>
    </div>
</div>
