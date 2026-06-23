@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-9 w-9 flex-none items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    {!! \App\Helpers\MenuHelper::getIconSvg('lock') !!}
                </span>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Developer Tools</h1>
                    <p class="mt-1 text-sm text-amber-800">
                        Temporary utilities for running maintenance commands.
                        Disable via <code class="rounded bg-amber-100 px-1">DEV_TOOLS_ENABLED</code> before going to production.
                    </p>
                </div>
            </div>
        </div>

        {{-- Last command result --}}
        @if (session('dev_result'))
            @php($result = session('dev_result'))
            <div class="flex items-start gap-3 rounded-xl border {{ $result['success'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} px-4 py-3">
                <span class="mt-0.5 flex-none {{ $result['success'] ? 'text-green-600' : 'text-red-600' }}">
                    @if ($result['success'])
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.3 3.3 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                    @else
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 7.3a1 1 0 00-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 101.4 1.4L10 11.4l1.3 1.3a1 1 0 001.4-1.4L11.4 10l1.3-1.3a1 1 0 00-1.4-1.4L10 8.6 8.7 7.3z" clip-rule="evenodd"/></svg>
                    @endif
                </span>
                <p class="text-sm {{ $result['success'] ? 'text-green-800' : 'text-red-800' }}">{{ $result['message'] }}</p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Invite / onboard student --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Onboard student</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Provisions a student account and sends the branded invitation email
                    (<code class="rounded bg-gray-100 px-1 text-xs">portal:invite-student</code>).
                </p>

                <form method="POST" action="{{ route('admin.dev-tools.invite-student') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500" />
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">First name</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}"
                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Last name</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}"
                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Package <span class="text-red-500">*</span></label>
                        <select name="package" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Select a package…</option>
                            @foreach ($packages as $package)
                                <option value="{{ $package->slug }}" @selected(old('package') === $package->slug)>
                                    {{ $package->name }} — {{ $package->container_count }} {{ \Illuminate\Support\Str::plural('container', $package->container_count) }}
                                </option>
                            @endforeach
                        </select>
                        @error('package')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Run onboarding
                    </button>
                </form>
            </div>

            {{-- Buy add-on --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Buy add-on</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Records an add-on purchase for a student
                    (<code class="rounded bg-gray-100 px-1 text-xs">portal:buy-addon</code>).
                    Additional Container also provisions a trackable container.
                </p>

                <form method="POST" action="{{ route('admin.dev-tools.buy-addon') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Student <span class="text-red-500">*</span></label>
                        <select name="student" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Select a student…</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->user?->email }}" @selected(old('student') === $student->user?->email)>
                                    {{ $student->fullName() ?: $student->user?->name }} — {{ $student->new_life_id }}
                                </option>
                            @endforeach
                        </select>
                        @error('student')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @if ($students->isEmpty())
                            <p class="mt-1 text-xs text-gray-500">No students yet — onboard one first.</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Add-on <span class="text-red-500">*</span></label>
                        <select name="slug" required
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Select an add-on…</option>
                            @foreach ($catalog as $item)
                                <option value="{{ $item['slug'] }}" @selected(old('slug') === $item['slug'])>
                                    {{ $item['name'] }} — {{ $item['formatted_price'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Run purchase
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
