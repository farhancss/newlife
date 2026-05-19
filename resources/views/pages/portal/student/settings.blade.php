@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Settings</h1>
            <p class="mt-1 text-sm text-gray-600">Manage your portal preferences.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="space-y-3 text-sm text-gray-700">
                <label class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                    <span>Email Notifications</span>
                    <input type="checkbox" checked class="rounded border-gray-300" />
                </label>
                <label class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                    <span>Move Reminder Alerts</span>
                    <input type="checkbox" checked class="rounded border-gray-300" />
                </label>
                <label class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                    <span>Package Updates</span>
                    <input type="checkbox" class="rounded border-gray-300" />
                </label>
            </div>
        </div>
    </div>
@endsection
