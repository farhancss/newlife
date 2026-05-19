@php
    use App\Helpers\MenuHelper;

    $menuGroups = MenuHelper::getMenuGroups();
    $user = auth()->user();
    $userName = $user->name ?? 'Student User';
    $userEmail = $user->email ?? '';
    $initials = collect(explode(' ', $userName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('') ?: 'NL';
@endphp

<div class="flex h-full min-h-0 flex-col">
{{-- Brand block --}}
<div class="border-b border-gray-200/80 px-4 pb-5 pt-6"
    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:px-3' : ''">
    <a href="{{ route('student.dashboard') }}" class="block">
        <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
            <img src="/images/logo/new-life-logistix-logo.jpg" alt="New Life Logistix" class="h-auto w-full max-w-[200px] rounded-sm" />
            <div class="mt-4 rounded-xl border border-brand-100 bg-gradient-to-br from-brand-50 to-white p-3.5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Student Portal</p>
                <span class="mt-2 inline-flex items-center rounded-full bg-brand-800 px-2.5 py-0.5 text-xs font-medium text-white">
                    Essential Package
                </span>
            </div>
        </div>
        <div x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" class="hidden xl:flex xl:justify-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-800 text-sm font-bold text-white shadow-theme-sm">
                NL
            </span>
        </div>
    </a>
</div>

{{-- Navigation --}}
<div class="flex flex-1 flex-col overflow-y-auto px-3 py-5 no-scrollbar">
    <nav class="flex flex-1 flex-col">
        <ul class="flex flex-col gap-1">
            @foreach ($menuGroups as $menuGroup)
                @foreach ($menuGroup['items'] as $item)
                    <li>
                            <a href="{{ $item['path'] }}"
                                class="portal-nav-item group"
                                :class="[
                                    isActive('{{ $item['path'] }}') ? 'portal-nav-item-active' : 'portal-nav-item-inactive',
                                    (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : ''
                                ]">
                                <span class="portal-nav-icon"
                                    :class="isActive('{{ $item['path'] }}') ? 'portal-nav-icon-active' : 'portal-nav-icon-inactive'">
                                    {!! MenuHelper::getIconSvg($item['icon']) !!}
                                </span>
                                <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                    class="flex min-w-0 flex-1 items-center justify-between gap-2">
                                    <span class="truncate">{{ $item['name'] }}</span>
                                    @if (!empty($item['badge']))
                                        <span class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-brand-500 px-1.5 text-[10px] font-semibold text-white"
                                            :class="isActive('{{ $item['path'] }}') ? 'bg-white/25 text-white' : ''">
                                            {{ $item['badge'] }}
                                        </span>
                                    @endif
                                </span>
                            </a>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </nav>
</div>

{{-- User footer --}}
<div class="mt-auto border-t border-gray-200 bg-gray-50/80 p-3"
    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:p-2' : ''">
    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-2.5 shadow-theme-xs"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:p-2' : ''">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">
            {{ $initials }}
        </span>
        <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-gray-900">{{ $userName }}</p>
            <p class="truncate text-xs text-gray-500">{{ $userEmail }}</p>
        </div>
        <a href="{{ route('logout') }}"
            x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-brand-700"
            title="Logout"
            aria-label="Logout">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
        </a>
    </div>
</div>
</div>
