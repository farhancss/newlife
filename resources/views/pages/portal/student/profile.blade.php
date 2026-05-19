@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">My Profile</h1>
                    <p class="mt-1 text-sm text-gray-600">Step 2 of 6</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">Onboarding</span>
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-6">
                @for ($i = 1; $i <= 6; $i++)
                    <div class="h-2 rounded-full {{ $i <= 2 ? 'bg-brand-600' : 'bg-gray-200' }}"></div>
                @endfor
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Student Information</h2>
            <form class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" value="John" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" value="Adams" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">University</label>
                    <input type="text" value="ODU" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Move-in Date</label>
                    <input type="text" value="May 27, 2026" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <button type="button" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white">Save & Continue</button>
                </div>
            </form>
        </div>
    </div>
@endsection
