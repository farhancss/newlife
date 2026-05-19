@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Profile</h1>
            <p class="mt-1 text-sm text-gray-600">Manage account profile details.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <form class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" value="John" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" value="Wilson" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" value="john@newlifecampus.com" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" value="+1 (555) 324-9987" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <button type="button" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
@endsection
