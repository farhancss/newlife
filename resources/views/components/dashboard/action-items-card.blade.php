@props([
    'profile',
    'primaryContainer' => null,
    'deadlines',
])

@php
    use App\Enums\DeadlineType;

    $deadlineFor = fn (string $type) => $deadlines->first(fn ($deadline) => $deadline->type === $type);

    $actionItems = [
        [
            'label' => 'Complete Profile',
            'done' => $profile->isOnboardingComplete(),
            'date' => $deadlineFor(DeadlineType::PROFILE_COMPLETION)?->due_at ?? $profile->onboarding_completed_at,
        ],
        [
            'label' => 'Select Move-in Window',
            'done' => $profile->housingInfo?->move_in_date !== null,
            'date' => $profile->housingInfo?->move_in_date,
        ],
        [
            'label' => 'Containers assigned',
            'done' => $primaryContainer !== null,
            'date' => $primaryContainer?->created_at,
        ],
        [
            'label' => 'Add Retail Packages',
            'done' => $profile->retailPackages->isNotEmpty(),
            'date' => $deadlineFor(DeadlineType::RETAIL_ARRIVAL)?->due_at
                ?? $profile->retailPackages->sortBy('created_at')->first()?->created_at,
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex min-h-[220px] flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs']) }}>
    <x-dashboard.summary-card-header title="Action Item" :href="route('student.profile')" />

    <ul class="mt-4 flex-1 divide-y divide-gray-100">
        @foreach ($actionItems as $item)
            <li class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0">
                <span class="flex min-w-0 items-center gap-3">
                    @if ($item['done'])
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-300 text-white">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    @else
                        <span class="h-5 w-5 shrink-0 rounded-full border-2 border-gray-300 bg-white" aria-hidden="true"></span>
                    @endif
                    <span class="truncate text-sm text-gray-800">{{ $item['label'] }}</span>
                </span>

                @if ($item['date'])
                    <span class="shrink-0 text-xs text-gray-500">
                        {{ $item['date']->format('M j, Y') }}
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
