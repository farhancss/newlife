
@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();
    $isStudentPortal = request()->segment(1) === 'student';
    $currentPath = request()->path();
@endphp

<aside id="sidebar"
    class="portal-sidebar fixed top-0 z-99999 flex h-dvh max-h-dvh min-h-0 w-[min(290px,100vw)] flex-col overflow-hidden border-r border-gray-200 bg-white shadow-theme-md transition-[left,width] duration-300 ease-in-out {{ $isStudentPortal ? 'student-portal-sidebar' : 'admin-portal-sidebar' }}"
    x-data="{
        openSubmenus: {},
        init() {
            this.initializeActiveMenus();
        },
        initializeActiveMenus() {
            const currentPath = '{{ $currentPath }}';
            @foreach ($menuGroups as $groupIndex => $menuGroup)
                @foreach ($menuGroup['items'] as $itemIndex => $item)
                    @if (isset($item['subItems']))
                        @foreach ($item['subItems'] as $subItem)
                            if (currentPath === '{{ ltrim($subItem['path'], '/') }}' ||
                                window.location.pathname === '{{ $subItem['path'] }}') {
                                this.openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] = true;
                            }
                        @endforeach
                    @endif
                @endforeach
            @endforeach
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            const newState = !this.openSubmenus[key];
            if (newState) {
                this.openSubmenus = {};
            }
            this.openSubmenus[key] = newState;
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return this.openSubmenus[key] || false;
        },
        isActive(path) {
            return window.location.pathname === path || '{{ $currentPath }}' === path.replace(/^\//, '');
        }
    }"
    :class="{
        'left-0': $store.sidebar.isMobileOpen,
        '-left-full xl:left-0': ! $store.sidebar.isMobileOpen,
        'xl:w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
        'xl:w-[90px]': ! $store.sidebar.isExpanded && ! $store.sidebar.isHovered,
    }"
    @mouseenter="if (window.innerWidth >= 1280 && ! $store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    @if ($isStudentPortal)
        @include('layouts.partials.student-sidebar')
    @else
        @include('layouts.partials.admin-sidebar')
    @endif
</aside>

<div
    x-show="$store.sidebar.isMobileOpen"
    x-cloak
    @click="$store.sidebar.setMobileOpen(false)"
    class="fixed inset-0 z-[99998] bg-gray-900/50 backdrop-blur-sm xl:hidden"
></div>
