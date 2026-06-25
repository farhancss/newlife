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
        <div>
            <h1 class="text-xl font-semibold text-gray-600">My Profile</h1>
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

        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            @if ($hasIncompleteSections)
                <div class="mb-6 flex w-full flex-col items-stretch gap-2 sm:ml-auto sm:max-w-[280px]">
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
            @endif

            <div class="rounded-xl px-4 py-4 sm:px-8">
                <x-profile.section-stepper :sections="$completion['sections']" :active-section="$activeSection" />
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            @if ($activeSection === 1)
                {{-- Optional profile photo, grouped with Student Information --}}
                <div
                    x-data="{
                        avatarUrl: @js($user->avatarUrl()),
                        initials: @js($user->initials()),
                        preview: null,
                        uploading: false,
                        error: null,
                        async uploadAvatar(file) {
                            this.error = null;
                            this.preview = URL.createObjectURL(file);
                            this.uploading = true;

                            const formData = new FormData();
                            formData.append('avatar', file);
                            formData.append('_token', @js(csrf_token()));

                            try {
                                const response = await fetch(@js(route('student.profile.avatar.update')), {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        Accept: 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                });

                                const data = await response.json();

                                if (!response.ok) {
                                    throw new Error(data.errors?.avatar?.[0] ?? data.message ?? 'Upload failed.');
                                }

                                this.avatarUrl = data.avatar_url;
                                this.preview = null;
                            } catch (error) {
                                this.preview = null;
                                this.error = error.message ?? 'Upload failed.';
                            } finally {
                                this.uploading = false;
                                if (this.$refs.avatarInput) {
                                    this.$refs.avatarInput.value = '';
                                }
                            }
                        },
                        async removeAvatar() {
                            if (!confirm('Remove your profile photo?')) {
                                return;
                            }

                            this.error = null;
                            this.uploading = true;

                            try {
                                const response = await fetch(@js(route('student.profile.avatar.destroy')), {
                                    method: 'DELETE',
                                    headers: {
                                        Accept: 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': @js(csrf_token()),
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                });

                                const data = await response.json();

                                if (!response.ok) {
                                    throw new Error(data.message ?? 'Remove failed.');
                                }

                                this.avatarUrl = null;
                                this.preview = null;
                            } catch (error) {
                                this.error = error.message ?? 'Remove failed.';
                            } finally {
                                this.uploading = false;
                            }
                        },
                    }"
                    class="mb-6 border-b border-gray-100 pb-6">
                    <h2 class="text-lg font-semibold text-gray-600">Student Information</h2>
                    <p class="mt-1 text-sm text-gray-500">Your contact and school details.</p>

                    <div class="mt-4 flex items-start gap-4">
                        <div class="shrink-0">
                            <template x-if="preview">
                                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white ring-2 ring-brand-50"
                                    :class="uploading ? 'opacity-60' : ''">
                                    <img :src="preview" alt="Preview" class="h-full w-full object-cover" />
                                </div>
                            </template>
                            <template x-if="!preview && avatarUrl">
                                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white ring-2 ring-brand-50"
                                    :class="uploading ? 'opacity-60' : ''">
                                    <img :src="avatarUrl" alt="Profile photo" class="h-full w-full object-cover" />
                                </div>
                            </template>
                            <template x-if="!preview && !avatarUrl">
                                <div :class="uploading ? 'opacity-60' : ''">
                                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-500 ring-2 ring-brand-50"
                                        x-text="initials"></span>
                                </div>
                            </template>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-700">
                                Profile photo <span class="font-normal text-gray-500">(optional)</span>
                                <span class="font-normal text-gray-400">· JPG, PNG or WEBP up to {{ (int) (config('portal.avatars.max_size_kb', 4096) / 1024) }}MB</span>
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label
                                    :class="uploading ? 'pointer-events-none opacity-50' : ''"
                                    class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">
                                    <span class="sr-only">Choose a profile photo</span>
                                    <input
                                        x-ref="avatarInput"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        :disabled="uploading"
                                        @change="const file = $event.target.files[0]; if (file) uploadAvatar(file);"
                                        class="sr-only" />
                                    <span x-text="avatarUrl ? 'Change photo' : 'Upload photo'"></span>
                                </label>
                                <p x-show="uploading" x-cloak class="text-xs font-medium text-brand-600">Uploading...</p>
                                <button
                                    type="button"
                                    x-show="avatarUrl"
                                    x-cloak
                                    @click="removeAvatar()"
                                    :disabled="uploading"
                                    class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50">
                                    Remove
                                </button>
                            </div>
                            <p x-show="error" x-cloak x-text="error" class="mt-2 text-xs text-error-600"></p>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('student.profile.update') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <input type="hidden" name="section" value="{{ $activeSection }}" />

                @if ($activeSection === 1)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">First Name <span class="text-error-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $profile->first_name) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Last Name <span class="text-error-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $profile->last_name) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $profile->phone) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">School</label>
                        <input type="text" name="school" value="{{ old('school', $profile->school) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Incoming Year / Class</label>
                        <input type="text" name="incoming_year" value="{{ old('incoming_year', $profile->incoming_year) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" value="{{ $user->email }}" disabled
                            class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm text-gray-600" />
                    </div>
                @endif

                @if ($activeSection === 2)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-medium text-gray-600">Parent / Guardian</h2>
                        <p class="mt-1 text-sm text-gray-500">Emergency contact for move communications.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">First Name <span class="text-error-500">*</span></label>
                        <input type="text" name="parent_name" value="{{ old('parent_name', $parent?->name) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Relationship</label>
                        <input type="text" name="parent_relationship" value="{{ old('parent_relationship', $parent?->relationship) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="parent_email" value="{{ old('parent_email', $parent?->email) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="parent_phone" value="{{ old('parent_phone', $parent?->phone) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                @endif

                @if ($activeSection === 3)
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-medium text-gray-600">Street Address</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Where you live now — we pick up your items here before your move to campus.
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Street Address</label>
                        <input type="text" name="line1" value="{{ old('line1', $shipping?->line1) }}"
                            placeholder="123 Main St"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Apt, Suite, or Unit <span class="font-normal text-gray-500">(optional)</span></label>
                        <input type="text" name="line2" value="{{ old('line2', $shipping?->line2) }}"
                            placeholder="Apt 4B"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">City</label>
                        <input type="text" name="city" value="{{ old('city', $shipping?->city) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">State</label>
                        <input type="text" name="region" value="{{ old('region', $shipping?->region) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ZIP Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $shipping?->postal_code) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" name="country_code" value="{{ old('country_code', $shipping?->country_code ?? 'US') }}" maxlength="2"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
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
                        <h2 class="text-lg font-medium text-gray-600">University Dorm</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Where you are moving on campus — your dorm is where we deliver your belongings.
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">University</label>
                        <input type="text" name="university" value="{{ old('university', $housing?->university) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Dorm / Residence Hall</label>
                        <input type="text" name="residence_hall" value="{{ old('residence_hall', $housing?->residence_hall) }}"
                            placeholder="e.g. Gresham Hall"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Building <span class="font-normal text-gray-500">(optional)</span></label>
                        <input type="text" name="building" value="{{ old('building', $housing?->building) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Room Number <span class="font-normal text-gray-500">(optional)</span></label>
                        <input type="text" name="room" value="{{ old('room', $housing?->room) }}"
                            placeholder="e.g. 204"
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm" />
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
                            class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm text-gray-600 hover:bg-gray-50">
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
                            Save and Continue
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
