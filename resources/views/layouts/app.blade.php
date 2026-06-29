<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | New Life Campus</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo/new-life-campus-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/new-life-campus-logo.png') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: window.innerWidth >= 1280, // true for desktop, false for mobile
                isMobileOpen: false,
                isHovered: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>
    
</head>

<body
    class="min-h-screen"
    @auth
        data-user-avatar-url="{{ auth()->user()->avatarUrl() ?? '' }}"
        data-user-initials="{{ auth()->user()->initials() }}"
    @endauth
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    window.addEventListener('resize', checkMobile);">

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    <div class="min-h-screen">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div
            class="flex min-h-screen min-w-0 flex-col transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px] xl:w-[calc(100%-290px)]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px] xl:w-[calc(100%-90px)]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
            }"
        >
            @unless (!empty($hideAppHeader))
                @include('layouts.app-header')
            @endunless
            <main @class([
                'mx-auto w-full min-w-0 max-w-(--breakpoint-2xl) flex-1 p-4 md:p-5',
            ])>
                @yield('content')
            </main>
            @unless (!empty($hideAppFooter))
                <footer class="mt-auto shrink-0 border-t border-gray-200 bg-white px-6 py-4">
                    <p class="text-center text-sm text-gray-500">
                        &copy; {{ date('Y') }} New Life Logistix. All rights reserved.
                    </p>
                </footer>
            @endunless
        </div>

    </div>

    @stack('modals')

    {{-- Surface server-side flash + validation messages as global SweetAlert popups --}}
    @php
        $flashPayload = json_encode([
            'success' => session('status'),
            'error' => session('error'),
            'warning' => session('warning'),
            'errors' => $errors->all(),
        ]);
    @endphp
    <script>
        window.flashMessages = {!! $flashPayload !!};
    </script>

</body>

@stack('scripts')

</html>
