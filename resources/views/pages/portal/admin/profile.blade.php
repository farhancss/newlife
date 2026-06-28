@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My profile</h1>
            <p class="mt-1 text-sm text-gray-600">Manage your account details, photo, and password.</p>
        </div>

        {{-- Profile photo --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-gray-900">Profile photo</h2>
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="shrink-0">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->initials() }}"
                            class="h-20 w-20 rounded-full object-cover ring-1 ring-gray-200" />
                    @else
                        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-brand-50 text-xl font-semibold text-brand-700 ring-1 ring-gray-200">
                            {{ $user->initials() }}
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('admin.profile.avatar.update') }}" enctype="multipart/form-data"
                        class="flex flex-wrap items-center gap-3"
                        x-data="{ name: '' }">
                        @csrf
                        <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V18a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 18v-1.5M16.5 7.5L12 3m0 0L7.5 7.5M12 3v13.5"/></svg>
                            <span x-text="name ? name : 'Choose photo'"></span>
                            <input type="file" name="avatar" accept="image/*" class="hidden"
                                x-on:change="name = $event.target.files[0]?.name ?? ''; $el.form.requestSubmit()" />
                        </label>
                        <span class="text-xs text-gray-400">JPG, PNG or WEBP up to {{ (int) (config('portal.avatars.max_size_kb', 4096) / 1024) }}MB</span>
                    </form>

                    @if ($user->avatarUrl())
                        <form method="POST" action="{{ route('admin.profile.avatar.destroy') }}"
                            onsubmit="return confirm('Remove your profile photo?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                                Remove
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Account details --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-gray-900">Account details</h2>
            <form method="POST" action="{{ route('admin.profile.update') }}" class="mt-4 grid gap-4 md:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Full name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror" />
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email" type="email" value="{{ $user->email }}" disabled
                        class="h-11 w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-500" />
                    <p class="mt-1 text-xs text-gray-400">Email is linked to your account and can't be changed here.</p>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        Save changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Security --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Password</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        @if ($user->password_changed_at)
                            Last changed {{ $user->password_changed_at->diffForHumans() }}.
                        @else
                            Keep your account secure with a strong password.
                        @endif
                    </p>
                </div>
                <a href="{{ route('admin.change-password') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Change password
                </a>
            </div>
        </div>
    </div>
@endsection
