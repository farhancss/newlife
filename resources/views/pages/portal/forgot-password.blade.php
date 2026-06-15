@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-10 bg-white p-6 sm:p-0">
        <div class="relative flex min-h-screen w-full flex-col justify-center sm:p-0 lg:flex-row">
            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-10 lg:px-8">
                <div class="mb-6">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">&larr; Back to sign in</a>
                    <h1 class="mt-4 text-2xl font-semibold text-gray-900 sm:text-3xl">Forgot password</h1>
                    <p class="mt-2 text-sm text-gray-600">Enter your email and we will send reset instructions if an account exists.</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm text-success-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-3 py-2 text-sm text-error-700">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10" />
                    </div>
                    <button type="submit"
                        class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white transition hover:bg-brand-600">
                        Send reset link
                    </button>
                </form>
            </div>

            <div class="relative hidden min-h-screen w-full items-center justify-center overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-950 lg:flex lg:w-1/2">
                <x-common.common-grid-shape />
                <div class="relative z-10 mx-auto max-w-lg px-8 text-center">
                    <img src="{{ asset('images/logo/new-life-campus-logo.png') }}" alt="New Life Campus"
                        class="mx-auto mb-8 h-auto w-full max-w-[360px] rounded-2xl bg-white px-6 py-5 shadow-lg" />
                    <h2 class="text-2xl font-semibold text-white">Account recovery</h2>
                    <p class="mt-2 text-sm text-brand-100">Secure self-service password reset for the portal.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
