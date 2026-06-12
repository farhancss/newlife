@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <x-ui.alert variant="success" :message="session('status')" />
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h1 class="text-xl font-semibold text-gray-900">Settings</h1>
            <p class="mt-1 text-sm text-gray-600">Choose how we keep you and your family updated.</p>
        </div>

        <form action="{{ route('student.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Channels</h2>
                <div class="mt-4 space-y-3 text-sm text-gray-700">
                    <label class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3">
                        <span>
                            <span class="block font-medium text-gray-900">Email notifications</span>
                            <span class="block text-xs text-gray-500">Move, retail, and account updates by email.</span>
                        </span>
                        <input type="checkbox" name="email_enabled" value="1" @checked($preference->email_enabled)
                            class="h-5 w-9 rounded-full border-gray-300 text-brand-600" />
                    </label>

                    <label class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3">
                        <span>
                            <span class="block font-medium text-gray-900">SMS notifications
                                <span class="ml-1 rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-600">Coming soon</span>
                            </span>
                            <span class="block text-xs text-gray-500">Text alerts for time-sensitive updates.</span>
                        </span>
                        <input type="checkbox" name="sms_enabled" value="1" @checked($preference->sms_enabled)
                            class="h-5 w-9 rounded-full border-gray-300 text-brand-600" />
                    </label>

                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <label for="sms_number" class="block text-sm font-medium text-gray-900">Mobile number</label>
                        <input id="sms_number" name="sms_number" type="text" value="{{ old('sms_number', $preference->sms_number) }}"
                            placeholder="(555) 123-4567"
                            class="mt-1.5 h-11 w-full rounded-xl border border-gray-300 px-3 text-sm sm:max-w-xs" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Family</h2>
                <label class="mt-4 flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700">
                    <span>
                        <span class="block font-medium text-gray-900">Copy my parent/guardian</span>
                        <span class="block text-xs text-gray-500">CC the guardian on your profile for key shipment and retail updates.</span>
                    </span>
                    <input type="checkbox" name="parent_cc_enabled" value="1" @checked($preference->parent_cc_enabled)
                        class="h-5 w-9 rounded-full border-gray-300 text-brand-600" />
                </label>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Save preferences
                </button>
            </div>
        </form>
    </div>
@endsection
