@php
    $portal = request()->segment(1) === 'admin' ? 'admin' : 'student';
@endphp

<div class="relative" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false">
    <button
        class="flex items-center gap-3 text-gray-700"
        @click.prevent="dropdownOpen = !dropdownOpen"
        type="button"
    >
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
            NL
        </span>
        <span class="hidden text-sm font-medium sm:block">{{ ucfirst($portal) }} User</span>
        <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': dropdownOpen }" viewBox="0 0 20 20" fill="none">
            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
    </button>

    <div
        x-show="dropdownOpen"
        x-transition
        class="absolute right-0 z-50 mt-3 w-56 rounded-xl border border-gray-200 bg-white p-2 shadow-theme-sm"
        style="display: none;"
    >
        <a href="/{{ $portal }}/profile" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
        <a href="/{{ $portal }}/change-password" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Change Password</a>
        <a href="{{ route('logout') }}" class="mt-1 block rounded-lg px-3 py-2 text-sm font-medium text-brand-700 hover:bg-brand-50">Logout</a>
    </div>
</div>
