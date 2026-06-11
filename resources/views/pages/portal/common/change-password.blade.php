@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Change Password</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if ($mustReset ?? false)
                    You must set a strong new password before continuing.
                @else
                    Choose a strong password to keep your account secure.
                @endif
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <form method="POST" action="{{ route($portal . '.change-password.submit') }}">
                @csrf
                <x-form.password-strength
                    :show-current="!($mustReset ?? false)"
                    password-label="New Password"
                    confirm-label="Confirm Password"
                >
                    <button
                        type="submit"
                        class="w-fit rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
                        x-bind:disabled="!canSubmit"
                    >
                        Update Password
                    </button>
                </x-form.password-strength>
            </form>
        </div>
    </div>
@endsection
