@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-10 h-screen overflow-hidden bg-white">
        <div class="flex h-full w-full flex-col lg:flex-row">
            {{-- Left: Reset password form --}}
            <div class="flex h-full w-full flex-1 flex-col overflow-y-auto lg:w-1/2">
                <div class="flex flex-1 flex-col justify-center px-6 pb-10 sm:px-10 lg:px-14 lg:pb-12">
                    <div class="mx-auto w-full max-w-md">
                        <div class="mb-4 pt-8 sm:pt-10 lg:pt-12">
                            <a href="{{ route('login') }}" class="inline-block">
                                <img src="{{ asset('images/logo/new-life-campus-logo.png') }}" alt="New Life Campus"
                                    class="h-10 w-auto sm:h-12 lg:h-16" />
                            </a>
                        </div>

                        <div class="mb-8">
                            <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Create your new password</h1>
                            <p class="mt-2 text-sm text-gray-500 sm:text-base">
                                Set a new, secure password to protect your account, you can use it to log in once updated.
                            </p>
                        </div>

                        @if ($errors->any())
                            <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-3 py-2 text-sm text-error-700">
                                <ul class="list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}" />
                            <input type="hidden" name="email" value="{{ old('email', $email) }}" />

                            <x-form.password-strength
                                variant="auth"
                                :show-current="false"
                                password-label="Password"
                                confirm-label="Retype Password"
                            >
                                <button
                                    type="submit"
                                    class="flex h-12 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-500 focus:outline-hidden focus:ring-3 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                                    x-bind:disabled="!canSubmit"
                                >
                                    Reset Password
                                </button>

                                <a href="{{ route('login') }}"
                                    class="flex h-12 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-brand-500 transition hover:bg-gray-50 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10">
                                    Back
                                </a>
                            </x-form.password-strength>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Right: Image + testimonial overlay --}}
            <div class="relative hidden h-full w-full shrink-0 overflow-hidden lg:block lg:w-1/2">
                <img src="{{ asset('images/login/background-image.jpg') }}" alt=""
                    class="absolute inset-0 h-full w-full object-cover object-top" />

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
