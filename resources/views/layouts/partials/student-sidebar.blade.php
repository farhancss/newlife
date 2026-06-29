@php
    use App\Helpers\MenuHelper;

    $menuGroups = MenuHelper::getMenuGroups();
    $user = auth()->user();
    $userName = $user->name ?? 'Student User';
    $userEmail = $user->email ?? '';
    $profile = $user?->studentProfile;
    $newLifeId = $profile?->new_life_id;
    $packageLabel = $profile?->package?->name
        ?? ($profile?->package_tier ? ucfirst($profile->package_tier) . ' Package' : null);
    $sidebarExpanded = '$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen';
@endphp

<div class="flex h-full min-h-0 flex-col">
    {{-- Logo --}}
    <div class="px-5 pt-6 pb-4"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:px-3 xl:pb-3' : ''">
        <a href="{{ route('student.dashboard') }}" class="block">
            <div x-show="{{ $sidebarExpanded }}">
                <img src="{{ asset('images/logo/new-life-campus-logo.png') }}" alt="New Life Campus"
                    class="h-auto w-full max-w-[210px]" />
            </div>
            <div x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen"
                class="hidden xl:flex xl:justify-center">
                <img src="{{ asset('images/logo/new-life-campus-mark.png') }}" alt="New Life Campus"
                    class="h-11 w-11 object-contain" />
            </div>
        </a>
    </div>

        {{-- Student portal + package card --}}
    <div class="px-4" x-show="{{ $sidebarExpanded }}">
        <div class="rounded-xl border border-brand-100 bg-gradient-to-br from-brand-50 to-white p-3.5 text-center shadow-theme-xs">
            <p class="text-sm font-semibold text-gray-900">Student Portal</p>
            @if ($packageLabel)
                <span
                    class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-brand-400 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs">
                    {{ $packageLabel }}
                </span>
            @else
                <span
                    class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-500">
                    No package assigned
                </span>
            @endif
        </div>
    </div>

    @if (!empty($profileCompletion) && !$profileCompletion['is_complete'])
        <div class="mx-4 mt-4 rounded-xl border border-warning-200 bg-warning-50 p-3"
            x-show="{{ $sidebarExpanded }}">
            <p class="text-xs font-semibold text-warning-800">Profile {{ $profileCompletion['percent'] }}% complete</p>
            <p class="mt-1 text-xs text-warning-700">Finish your profile to unlock all features.</p>
            <a href="{{ route('student.profile') }}"
                class="mt-2 inline-block text-xs font-semibold text-brand-700 hover:text-brand-500">
                Complete now →
            </a>
        </div>
    @endif

    {{-- Navigation --}}
    <div class="flex flex-1 flex-col overflow-y-auto px-4 py-5 no-scrollbar">
        <nav class="flex flex-1 flex-col gap-5">
            @foreach ($menuGroups as $groupIndex => $menuGroup)
                @if ($groupIndex > 0)
                    <div class="border-t border-gray-200" x-show="{{ $sidebarExpanded }}"></div>
                @endif

                <div>
                    <p class="mb-2 px-2 text-[11px] font-semibold tracking-[0.08em] text-gray-400 uppercase"
                        x-show="{{ $sidebarExpanded }}">
                        {{ $menuGroup['title'] }}
                    </p>

                    <ul class="flex flex-col gap-1">
                        @foreach ($menuGroup['items'] as $item)
                            @php
                                $itemBadge = str_ends_with($item['path'], '/notifications')
                                    ? (($notificationUnreadCount ?? 0) > 0
                                        ? ($notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount)
                                        : null)
                                    : ($item['badge'] ?? null);
                            @endphp
                            <li>
                                <a href="{{ $item['path'] }}"
                                    class="student-nav-item group"
                                    :class="[
                                        isActive('{{ $item['path'] }}') ? 'student-nav-item-active' : 'student-nav-item-inactive',
                                        (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:px-2' : ''
                                    ]">
                                    <span class="student-nav-icon"
                                        :class="isActive('{{ $item['path'] }}') ? 'student-nav-icon-active' : 'student-nav-icon-inactive'">
                                        {!! MenuHelper::getIconSvg($item['icon']) !!}
                                    </span>
                                    <span x-show="{{ $sidebarExpanded }}"
                                        class="flex min-w-0 flex-1 items-center justify-between gap-2">
                                        <span class="truncate">{{ $item['name'] }}</span>
                                        @if (!empty($itemBadge))
                                            <span
                                                class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-brand-500 px-1.5 text-[10px] font-semibold text-white">
                                                {{ $itemBadge }}
                                            </span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>
    </div>

    {{-- User footer --}}
    <div class="mt-auto px-4 pb-5 pt-3"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:px-2 xl:pb-4' : ''">
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-xs"
            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center xl:p-2' : ''">
            <x-ui.avatar :src="$user->avatarUrl()" :initials="$user->initials()"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700" />
            <div x-show="{{ $sidebarExpanded }}" class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-900">{{ $userName }}</p>
                <p class="truncate text-xs text-gray-500">{{ $userEmail }}</p>
                @if ($newLifeId)
                    <p class="mt-0.5 truncate text-xs font-semibold text-brand-500">{{ $newLifeId }}</p>
                @endif
            </div>
            <a href="{{ route('logout') }}" x-show="{{ $sidebarExpanded }}"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                title="Logout" aria-label="Logout">
                {!! MenuHelper::getIconSvg('logout') !!}
            </a>
        </div>
    </div>
</div>
