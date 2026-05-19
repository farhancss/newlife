@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Change Password</h1>
            <p class="mt-1 text-sm text-gray-600">Update your account password.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <form class="grid max-w-xl gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Current Password</label>
                    <input type="password" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <button type="button" class="w-fit rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white">Update Password</button>
            </form>
        </div>
    </div>
@endsection
