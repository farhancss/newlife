@extends('layouts.app')

@php
    use App\Enums\NotificationCategory;
@endphp

@section('content')
    <div class="mx-auto w-full max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Send Notification</h1>
                <p class="mt-1 text-sm text-gray-600">Send a custom in-app notification and email to any student.</p>
            </div>
            <a href="{{ route('admin.notifications') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">← Back to log</a>
        </div>

        <form method="POST" action="{{ route('admin.notifications.send') }}"
            class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            @csrf

            <div>
                <label for="user_id" class="mb-1.5 block text-sm font-medium text-gray-700">Student <span class="text-red-500">*</span></label>
                <select id="user_id" name="user_id" required
                    class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Select a student…</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>
                            {{ $student->name }}@if ($student->studentProfile) — {{ $student->studentProfile->new_life_id }}@endif ({{ $student->email }})
                        </option>
                    @endforeach
                </select>
                @if ($students->isEmpty())
                    <p class="mt-1 text-xs text-gray-500">No students available yet.</p>
                @endif
            </div>

            <div>
                <label for="category" class="mb-1.5 block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                <select id="category" name="category" required
                    class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', NotificationCategory::ACCOUNT) === $category)>
                            {{ NotificationCategory::label($category) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="title" class="mb-1.5 block text-sm font-medium text-gray-700">Subject / title <span class="text-red-500">*</span></label>
                <input id="title" name="title" type="text" required maxlength="120" value="{{ old('title') }}"
                    placeholder="e.g. Action needed: confirm your move-in date"
                    class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500" />
            </div>

            <div>
                <label for="body" class="mb-1.5 block text-sm font-medium text-gray-700">Message <span class="text-red-500">*</span></label>
                <textarea id="body" name="body" rows="6" required maxlength="2000"
                    placeholder="Write your message to the student…"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">{{ old('body') }}</textarea>
            </div>

            <div>
                <label for="url" class="mb-1.5 block text-sm font-medium text-gray-700">Action link <span class="text-gray-400">(optional)</span></label>
                <input id="url" name="url" type="url" value="{{ old('url') }}" placeholder="https://…"
                    class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500" />
                <p class="mt-1 text-xs text-gray-500">Adds a button in the email and links the in-app notification.</p>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                <a href="{{ route('admin.notifications') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Send notification
                </button>
            </div>
        </form>
    </div>
@endsection
