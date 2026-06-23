@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-10 h-screen overflow-hidden bg-white">
        <div class="flex h-full w-full flex-col lg:flex-row">
            {{-- Left: Sign-in form --}}
            <div class="flex h-full w-full flex-1 flex-col overflow-y-auto lg:w-1/2">
                <div class="flex flex-1 flex-col justify-center px-6 pb-10 sm:px-10 lg:px-14 lg:pb-12">
                    <div class="mx-auto w-full max-w-md">
                    <div class="pt-8 sm:pt-10 lg:pt-12 mb-4">
                        <a href="{{ route('login') }}" class="inline-block">
                            <img src="{{ asset('images/logo/new-life-campus-logo.png') }}" alt="New Life Campus"
                                class="h-10 w-auto sm:h-12" />
                        </a>
                    </div>
                        <div class="mb-8">
                            <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Sign In</h1>
                            <p class="mt-2 text-sm text-gray-500 sm:text-base">
                                Enter your email and password to sign in!
                            </p>
                        </div>

                        @if (session('status'))
                            <div class="mb-4 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm text-success-700">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->has('login'))
                            <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-3 py-2 text-sm text-error-700">
                                {{ $errors->first('login') }}
                            </div>
                        @endif

                        <form action="{{ route('login.submit') }}" method="POST" class="space-y-5">
                            @csrf

                            <div>
                                <label for="login-email" class="mb-1.5 block text-sm font-medium text-gray-700">
                                    Email <span class="text-error-500">*</span>
                                </label>
                                <input id="login-email" name="email" type="email" value="{{ old('email') }}" required
                                    placeholder="example@demo.com"
                                    class="h-12 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-hidden transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10" />
                            </div>

                            <div x-data="{ showPassword: false }">
                                <label for="login-password" class="mb-1.5 block text-sm font-medium text-gray-700">
                                    Password <span class="text-error-500">*</span>
                                </label>
                                <div class="relative">
                                    <input id="login-password" name="password" :type="showPassword ? 'text' : 'password'"
                                        required placeholder="Enter your password"
                                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-4 pr-11 text-sm text-gray-800 outline-hidden transition placeholder:text-gray-400 focus:border-brand-400 focus:ring-3 focus:ring-brand-500/10" />
                                    <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                                        class="absolute top-1/2 right-4 -translate-y-1/2 text-gray-500 transition hover:text-gray-700"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'">
                                        <svg x-show="!showPassword" x-cloak class="fill-current" width="20" height="20"
                                            viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" />
                                        </svg>
                                        <svg x-show="showPassword" x-cloak class="fill-current" width="20" height="20"
                                            viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label class="inline-flex cursor-pointer items-center gap-2.5">
                                    <input type="checkbox" name="remember" value="1"
                                        {{ old('remember') ? 'checked' : '' }}
                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20" />
                                    <span class="text-sm text-gray-600">Keep me logged in</span>
                                </label>
                                <a href="{{ route('password.request') }}"
                                    class="text-sm font-medium text-brand-500 transition hover:text-brand-600">
                                    Forgot password?
                                </a>
                            </div>

                            <button type="submit"
                                class="flex h-12 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/30">
                                Sign In
                            </button>
                        </form>
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
                                d="M0 32V19.2C0 11.52 2.24 5.54667 6.72 1.28C11.3067 -0.426667 16.0533 0.64 20.96 4.48L17.28 9.6C14.5067 7.25333 11.7333 6.61333 8.96 7.68C6.18667 8.74667 4.8 11.0933 4.8 14.72V16H16V32H0ZM20 32V19.2C20 11.52 22.24 5.54667 26.72 1.28C31.3067 -0.426667 36.0533 0.64 40.96 4.48L37.28 9.6C34.5067 7.25333 31.7333 6.61333 28.96 7.68C26.1867 8.74667 24.8 11.0933 24.8 14.72V16H36V32H20Z" />
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
