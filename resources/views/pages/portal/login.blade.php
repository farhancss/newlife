@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-10 bg-white p-6 sm:p-0">
        <div class="relative flex h-screen w-full flex-col justify-center sm:p-0 lg:flex-row">
            <div class="flex w-full flex-1 flex-col lg:w-1/2">

                <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
                    <div class="mb-5 sm:mb-8">
                        <h1 class="mb-2 text-2xl font-semibold text-gray-900 sm:text-3xl">Sign In</h1>
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
                            <label for="login-email" class="mb-1.5 block text-sm font-medium text-gray-700">Email Address</label>
                            <input id="login-email" name="email" type="email" value="{{ old('email') }}"
                                placeholder="example@demo.com"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10" />
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="login-password" class="text-sm font-medium text-gray-700">Password</label>
                                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                                    Forgot password?
                                </a>
                            </div>
                            <input id="login-password" name="password" type="password" placeholder="Enter your password"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10" />
                        </div>
                        <button type="submit"
                            class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white transition hover:bg-brand-600">
                            Sign In
                        </button>
                    </form>

                    <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                        <p class="font-medium">Demo credentials</p>
                        <p class="mt-1">Student: <span class="font-semibold">student@demo.com</span></p>
                        <p>Admin: <span class="font-semibold">admin@demo.com</span></p>
                        <p>Password: <span class="font-semibold">Admin@123</span></p>
                    </div>
                </div>
            </div>

            <div class="relative hidden h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-950 lg:flex lg:w-1/2">
                <div class="absolute -top-24 -left-16 h-72 w-72 rounded-full bg-brand-500/20 blur-3xl"></div>
                <div class="absolute -right-20 bottom-10 h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.12)_0,transparent_40%),radial-gradient(circle_at_80%_80%,rgba(255,255,255,0.10)_0,transparent_40%)]"></div>
                <x-common.common-grid-shape />
                <div class="relative z-10 mx-auto max-w-lg px-8 text-center">
                    <img src="/images/logo/new-life-logistix-logo.jpg" alt="New Life Logistix"
                        class="mx-auto mb-8 h-auto w-full max-w-[360px] rounded-sm bg-white p-2" />
                    <h2 class="mb-3 text-2xl font-semibold text-white">Secure access for one unified platform.</h2>
                </div>
            </div>
        </div>
    </div>
@endsection
