@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-10 h-screen overflow-hidden bg-white">
        <div class="flex h-full w-full flex-col lg:flex-row">
            {{-- Left: Success message --}}
            <div class="flex h-full w-full flex-1 flex-col overflow-y-auto lg:w-1/2">
                <div class="flex flex-1 flex-col items-center justify-center px-6 pb-10 text-center sm:px-10 lg:px-14 lg:pb-12">
                    <div class="mx-auto w-full max-w-md">
                        <div class="mx-auto flex justify-center">
                            <img src="{{ asset('images/login/success-tick.png') }}" alt=""
                                class="h-48 w-48 object-contain" aria-hidden="true" />
                        </div>

                        <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Successfully updated!</h1>
                        <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-gray-500 sm:text-base">
                            Your password has been changed. You can now log in with your new password.
                        </p>

                        <button
                            type="button"
                            onclick="window.location.replace('{{ route('login') }}')"
                            class="mt-8 flex h-12 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/30"
                        >
                            Login
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right: Image + testimonial overlay --}}
            <div class="relative hidden h-full w-full shrink-0 overflow-hidden lg:block lg:w-1/2">
                <img src="{{ asset('images/login/background-image.jpg') }}" alt=""
                    class="absolute inset-0 h-full w-full object-cover object-center" />

                <div class="absolute -right-20 -bottom-100 h-80 w-80 rounded-full border-[32px] border-[#0112EF] lg:-right-24 lg:-bottom-64 lg:h-96 lg:w-96 lg:border-[60px]"></div>

                <div class="relative z-10 flex h-full flex-col justify-end p-8 sm:p-10 lg:p-12 xl:p-14">
                    <div class="max-w-md">
                        <svg class="mb-4 text-brand-400 sm:mb-5" width="36" height="28" viewBox="0 0 40 32"
                            fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path
                                d="M0 32V19.2C0 11.52 2.24 5.54667 6.72 1.28C11.3067 -0.426667 16.0533 0.64 20.96 4.48L17.28 9.6C14.5067 7.25333 11.7333 6.61333 8.96 7.68C6.18667 8.74667 4.8 11.0933 4.8 14.72V16H16V32H0ZM20 32V19.2C20 11.52 22.24 5.54667 26.72 1.28C31.3067 -0.426667 36.0533 0.64 40.96 4.48L37.28 9.6C34.5067 7.25333 31.7333 6.61333 28.96 7.68C26.18667 8.74667 24.8 11.0933 24.8 14.72V16H36V32H20Z" />
                        </svg>

                        <blockquote class="text-lg leading-snug font-semibold text-white sm:text-xl lg:text-2xl">
                            Best of the best! If you're looking for a reliable moving company, look no further! Shelton,
                            Rian, and the rest of their crew are seriously amazing and super easy to work with.
                        </blockquote>

                        <span
                            class="mt-5 inline-flex rounded-full border border-white/50 px-4 py-1.5 text-sm font-medium text-white sm:mt-6">
                            Client Testimonial
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
