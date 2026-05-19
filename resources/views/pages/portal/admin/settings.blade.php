@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Admin Settings</h1>
            <p class="mt-1 text-sm text-gray-600">Organization and notification preferences.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Company Information</h2>
                <form class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Company Name</label>
                        <input type="text" value="New Life Campus" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Support Email</label>
                        <input type="email" value="support@newlifecampus.com" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                </form>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">System Settings</h2>
                <div class="space-y-3 text-sm text-gray-700">
                    <label class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span>Email Alerts</span>
                        <input type="checkbox" checked class="rounded border-gray-300" />
                    </label>
                    <label class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span>Delivery Reminder Notifications</span>
                        <input type="checkbox" checked class="rounded border-gray-300" />
                    </label>
                    <label class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span>Report Summaries</span>
                        <input type="checkbox" class="rounded border-gray-300" />
                    </label>
                    <a href="{{ route('admin.change-password') }}" class="mt-3 inline-block text-sm font-semibold text-brand-600 hover:text-brand-700">Change password</a>
                </div>
            </div>
        </div>
    </div>
@endsection
