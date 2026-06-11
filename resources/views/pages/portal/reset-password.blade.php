@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-10 bg-white p-6 sm:p-0">
        <div class="relative flex min-h-screen w-full flex-col justify-center sm:p-0 lg:flex-row">
            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-10 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900 sm:text-3xl">Choose a new password</h1>
                    <p class="mt-2 text-sm text-gray-600">Use a strong password for your portal account.</p>
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

                <form action="{{ route('password.update') }}" method="POST" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}" />
                    <input type="hidden" name="email" value="{{ old('email', $email) }}" />

                    <x-form.password-strength
                        :show-current="false"
                        password-label="New password"
                        confirm-label="Confirm password"
                    >
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                            x-bind:disabled="!canSubmit"
                        >
                            Reset password
                        </button>
                    </x-form.password-strength>
                </form>
            </div>

            <div class="relative hidden min-h-screen w-full items-center justify-center overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-950 lg:flex lg:w-1/2">
                <x-common.common-grid-shape />
            </div>
        </div>
    </div>
@endsection
