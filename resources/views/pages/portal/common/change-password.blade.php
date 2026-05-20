@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Change Password</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if ($mustReset ?? false)
                    You must set a new password before continuing.
                @else
                    Update your account password.
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
            <form method="POST" action="{{ route($portal . '.change-password.submit') }}" class="grid max-w-xl gap-4">
                @csrf
                @unless ($mustReset ?? false)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" name="current_password" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                @endunless
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                        class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <button type="submit" class="w-fit rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Update Password
                </button>
            </form>
        </div>
    </div>
@endsection
