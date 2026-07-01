@props([
    'code' => '404',
    'codeLabel' => null,
    'title' => 'Page not found',
    'message' => '',
    'note' => null,
    'showLogin' => true,
    'showRetry' => false,
])

@php
    $brand = config('brand.name', 'New Life Campus');
    $supportEmail = config('brand.support.email');
@endphp

<div class="relative flex min-h-screen flex-col lg:flex-row">
    {{-- Left: message --}}
    <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-10 lg:px-14">
        <div class="mx-auto w-full max-w-md text-center lg:text-left">
            <a href="{{ url('/') }}" class="inline-block">
                <img src="{{ asset('images/logo/new-life-campus-logo.png') }}" alt="{{ $brand }}"
                    class="mx-auto h-20 w-auto lg:mx-0" />
            </a>

            <p class="mt-8 text-sm font-semibold uppercase tracking-[0.2em] text-brand-500">{{ $codeLabel ?? $code }}</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl">{{ $title }}</h1>

            @if ($message)
                <p class="mt-4 text-sm leading-relaxed text-gray-600 sm:text-base">{{ $message }}</p>
            @endif

            @if ($note)
                <p class="mt-4 inline-flex rounded-full bg-brand-50 px-4 py-2 text-sm font-medium text-brand-700 ring-1 ring-inset ring-brand-200">
                    {{ $note }}
                </p>
            @endif

            <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row lg:justify-start">
                @if ($showLogin)
                    <a href="{{ route('login') }}"
                        class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">
                        Back to sign in
                    </a>
                @endif

                @if ($showRetry)
                    <button type="button" onclick="window.location.reload()"
                        class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:w-auto">
                        Try again
                    </button>
                @endif

                @if ($supportEmail && $code === '503')
                    <a href="mailto:{{ $supportEmail }}"
                        class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100 sm:w-auto">
                        Contact support
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Right: brand panel (matches login page) --}}
    <div class="relative hidden min-h-[280px] flex-1 overflow-hidden lg:block">
        <img src="{{ asset('images/login/background-image.jpg') }}" alt=""
            class="absolute inset-0 h-full w-full object-cover object-top" />

        <div class="absolute -right-20 -bottom-64 h-96 w-96 rounded-full border-[60px] border-[#0112EF]"></div>

        <div class="relative z-10 flex h-full flex-col justify-end p-12 xl:p-14">
            <div class="max-w-md">
                <svg class="mb-5 text-brand-400" width="36" height="28" viewBox="0 0 40 32" fill="currentColor"
                    aria-hidden="true">
                    <path
                        d="M0 32V19.2C0 11.52 2.24 5.54667 6.72 1.28C11.3067 -0.426667 16.0533 0.64 20.96 4.48L17.28 9.6C14.5067 7.25333 11.7333 6.61333 8.96 7.68C6.18667 8.74667 4.8 11.0933 4.8 14.72V16H16V32H0ZM20 32V19.2C20 11.52 22.24 5.54667 26.72 1.28C31.3067 -0.426667 36.0533 0.64 40.96 4.48L37.28 9.6C34.5067 7.25333 31.7333 6.61333 28.96 7.68C26.1867 8.74667 24.8 11.0933 24.8 14.72V16H36V32H20Z" />
                </svg>

                <p class="text-lg font-semibold leading-snug text-white sm:text-xl">
                    {{ config('brand.tagline', 'Campus Move-In Portal') }}
                </p>

                <span class="mt-6 inline-flex rounded-full border border-white/50 px-4 py-1.5 text-sm font-medium text-white">
                    {{ $brand }}
                </span>
            </div>
        </div>
    </div>
</div>
