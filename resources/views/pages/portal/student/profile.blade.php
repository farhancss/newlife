@extends('layouts.app')

@section('content')
    @php
        $shipping = $profile->shippingAddress;
        $parent = $profile->parentGuardian;
        $housing = $profile->housingInfo;
        $incompleteSections = collect($completion['sections'])->filter(fn (array $section): bool => !$section['complete']);
        $hasIncompleteSections = $incompleteSections->isNotEmpty();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold text-gray-900">My Profile</h1>
                        @if ($completion['is_complete'])
                            <span class="rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-700">Complete</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        @if ($completion['is_complete'])
                            Your profile is complete. Select a section below to update your details.
                        @elseif ($hasIncompleteSections)
                            Complete the remaining sections below to unlock your portal. You can switch between sections anytime.
                        @else
                            Update any section below.
                        @endif
                    </p>
                </div>
                @if ($hasIncompleteSections)
                    <div class="flex w-full flex-col items-stretch gap-2 sm:w-auto sm:min-w-[220px]">
                        <div class="flex items-center justify-between text-sm font-medium text-gray-700">
                            <span>Overall progress</span>
                            <span>{{ $completion['percent'] }}%</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full rounded-full bg-brand-500 transition-all duration-300" style="width: {{ $completion['percent'] }}%"></div>
                        </div>
                        @if ($profile->package)
                            <span class="text-center text-xs font-semibold text-brand-700">{{ $profile->package->name }}</span>
                        @elseif ($profile->package_tier)
                            <span class="text-center text-xs font-semibold text-brand-700">Package: {{ ucfirst($profile->package_tier) }}</span>
                        @endif
                    </div>
                @elseif ($profile->package)
                    <span class="text-sm font-semibold text-brand-700">{{ $profile->package->name }}</span>
                @elseif ($profile->package_tier)
                    <span class="text-sm font-semibold text-brand-700">Package: {{ ucfirst($profile->package_tier) }}</span>
                @endif
            </div>

            <div class="mt-6">
                @if ($hasIncompleteSections)
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Sections to complete</p>
                @endif
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    @foreach ($completion['sections'] as $index => $section)
                        @php
                            $isCurrent = $activeSection === $section['step'];
                            $isComplete = $section['complete'];
                            $isIncomplete = !$isComplete;
                            $previousComplete = $index > 0 && $completion['sections'][$index - 1]['complete'];
                        @endphp
                        @if ($index > 0)
                            <div
                                @class([
                                    'hidden h-0.5 flex-1 sm:block',
                                    'bg-brand-500' => $previousComplete,
                                    'bg-gray-200' => !$previousComplete,
                                ])
                                aria-hidden="true"
                            ></div>
                        @endif
                        <a
                            href="{{ route('student.profile', ['section' => $section['step']]) }}"
                            @class([
                                'flex min-w-0 flex-1 items-center gap-2 rounded-xl border px-3 py-2.5 transition sm:flex-col sm:px-2 sm:py-3 sm:text-center',
                                'border-brand-300 bg-brand-50 ring-1 ring-brand-200' => $isCurrent && !$isIncomplete,
                                'border-brand-300 bg-brand-50 ring-2 ring-brand-300' => $isCurrent && $isIncomplete,
                                'border-warning-200 bg-warning-50 hover:border-warning-300' => $isIncomplete && !$isCurrent,
                                'border-gray-200 bg-white hover:border-brand-200 hover:bg-brand-25' => $isComplete && !$isCurrent,
                            ])
                            aria-current="{{ $isCurrent ? 'step' : 'false' }}"
                        >
                            @if ($isComplete)
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-success-100 text-success-700" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                            @elseif ($isCurrent)
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">
                                    {{ $section['step'] }}
                                </span>
                            @else
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-warning-100 text-xs font-bold text-warning-800">
                                    {{ $section['step'] }}
                                </span>
                            @endif
                            <span @class([
                                'min-w-0 text-xs font-semibold leading-tight sm:text-[11px]',
                                'text-brand-500' => $isCurrent || $isComplete,
                                'text-warning-800' => $isIncomplete && !$isCurrent,
                            ])>{{ $section['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            @if ($activeSection === 1)
                {{-- Optional profile photo, grouped with Student Information --}}
                <div x-data="{ preview: null, fileName: '' }" class="mb-6 border-b border-gray-100 pb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Student Information</h2>
                    <p class="mt-1 text-sm text-gray-500">Your contact and school details.</p>

                    <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">
                        <div class="shrink-0">
                            <template x-if="preview">
                                <img :src="preview" alt="Preview" class="h-20 w-20 rounded-2xl object-cover ring-2 ring-brand-100" />
                            </template>
                            <div x-show="!preview">
                                <x-ui.avatar :src="$user->avatarUrl()" :initials="$user->initials()"
                                    class="flex h-20 w-20 items-center justify-center rounded-2xl bg-brand-100 text-xl font-bold text-brand-500 ring-2 ring-brand-50" />
                            </div>
                        </div>

                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-700">Profile photo <span class="font-normal text-gray-500">(optional)</span></p>
                            <p class="mt-0.5 text-xs text-gray-500">We'll use your initials until you add one.</p>
                            <form method="POST" action="{{ route('student.profile.avatar.update') }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                                @csrf
                                <label class="block">
                                    <span class="sr-only">Choose a profile photo</span>
                                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" required
                                        @change="fileName = $event.target.files[0]?.name || ''; preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100" />
                                </label>
                                <p class="text-xs text-gray-400">JPG, PNG or WEBP up to {{ (int) (config('portal.avatars.max_size_kb', 4096) / 1024) }}MB.</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                                        Save photo
                                    </button>
                                    @if ($user->avatarUrl())
                                        <button type="submit" form="remove-avatar-form"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                            Remove photo
                                        </button>
                                    @endif
                                </div>
                            </form>
                            @if ($user->avatarUrl())
                                <form id="remove-avatar-form" method="POST" action="{{ route('student.profile.avatar.destroy') }}" class="hidden"
                                    onsubmit="return confirm('Remove your profile photo?');">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('student.profile.update') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <input type="hidden" name="section" value="{{ $activeSection }}" />

                @if ($activeSection === 1)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">Contact details</h2>
                        <p class="mt-1 text-sm text-gray-500">Your contact and school details.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $profile->first_name) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $profile->last_name) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $profile->phone) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">School</label>
                        <input type="text" name="school" value="{{ old('school', $profile->school) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Incoming Year / Class</label>
                        <input type="text" name="incoming_year" value="{{ old('incoming_year', $profile->incoming_year) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" value="{{ $user->email }}" disabled
                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-600" />
                    </div>
                @endif

                @if ($activeSection === 2)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">Parent / Guardian</h2>
                        <p class="mt-1 text-sm text-gray-500">Emergency contact for move communications.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="parent_name" value="{{ old('parent_name', $parent?->name) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Relationship</label>
                        <input type="text" name="parent_relationship" value="{{ old('parent_relationship', $parent?->relationship) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="parent_email" value="{{ old('parent_email', $parent?->email) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="parent_phone" value="{{ old('parent_phone', $parent?->phone) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                @endif

                @if ($activeSection === 3)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">Home Address</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Where you live now — we pick up your items here before your move to campus.
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Street Address</label>
                        <input type="text" name="line1" value="{{ old('line1', $shipping?->line1) }}"
                            placeholder="123 Main St"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Apt, Suite, or Unit <span class="font-normal text-gray-500">(optional)</span></label>
                        <input type="text" name="line2" value="{{ old('line2', $shipping?->line2) }}"
                            placeholder="Apt 4B"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">City</label>
                        <input type="text" name="city" value="{{ old('city', $shipping?->city) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">State</label>
                        <input type="text" name="region" value="{{ old('region', $shipping?->region) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ZIP Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $shipping?->postal_code) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" name="country_code" value="{{ old('country_code', $shipping?->country_code ?? 'US') }}" maxlength="2"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Home Pickup Notes <span class="font-normal text-gray-500">(optional)</span></label>
                        <textarea name="shipping_notes" rows="3"
                            placeholder="Gate code, driveway access, best time to pick up from home, etc."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('shipping_notes', $shipping?->shipping_notes) }}</textarea>
                    </div>
                @endif

                @if ($activeSection === 4)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">University Dorm</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Where you are moving on campus — your dorm is where we deliver your belongings.
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">University</label>
                        <input type="text" name="university" value="{{ old('university', $housing?->university) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Dorm / Residence Hall</label>
                        <input type="text" name="residence_hall" value="{{ old('residence_hall', $housing?->residence_hall) }}"
                            placeholder="e.g. Gresham Hall"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Building <span class="font-normal text-gray-500">(optional)</span></label>
                        <input type="text" name="building" value="{{ old('building', $housing?->building) }}"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Room Number <span class="font-normal text-gray-500">(optional)</span></label>
                        <input type="text" name="room" value="{{ old('room', $housing?->room) }}"
                            placeholder="e.g. 204"
                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="move_in_date">Move-in Date</label>
                        <x-form.flatpickr-input
                            name="move_in_date"
                            :value="old('move_in_date', $housing?->move_in_date?->format('Y-m-d'))"
                            placeholder="Select a date"
                            icon="calendar"
                            :options="[
                                'dateFormat' => 'Y-m-d',
                                'altInput' => true,
                                'altFormat' => 'F j, Y',
                                'minDate' => 'today',
                            ]"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="move_in_window">
                            Move-in Time Window <span class="font-normal text-gray-500">(optional)</span>
                        </label>
                        <x-form.flatpickr-input
                            name="move_in_window"
                            :value="old('move_in_window', $housing?->move_in_window)"
                            placeholder="Select a time"
                            icon="clock"
                            :options="[
                                'enableTime' => true,
                                'noCalendar' => true,
                                'dateFormat' => 'h:i K',
                                'time_24hr' => false,
                                'minuteIncrement' => 15,
                                'defaultHour' => 10,
                                'defaultMinute' => 0,
                            ]"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Dorm Delivery Notes <span class="font-normal text-gray-500">(optional)</span></label>
                        <textarea name="delivery_notes" rows="3"
                            placeholder="Loading dock, dorm access code, parking for delivery, etc."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('delivery_notes', $housing?->delivery_notes) }}</textarea>
                    </div>
                @endif

                <div class="flex items-center justify-between gap-3 md:col-span-2">
                    {{-- Back (left) --}}
                    @if ($activeSection > 1)
                        <a href="{{ route('student.profile', ['section' => $activeSection - 1]) }}"
                            class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Back
                        </a>
                    @else
                        <a href="{{ route('student.dashboard') }}"
                            class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Back to Dashboard
                        </a>
                    @endif

                    {{-- Primary action (right) --}}
                    @if ($activeSection === 4)
                        <button type="submit" name="action" value="{{ $completion['is_complete'] ? 'save' : 'next' }}"
                            class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            Save
                        </button>
                    @else
                        <button type="submit" name="action" value="next"
                            class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            Save &amp; Continue
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
