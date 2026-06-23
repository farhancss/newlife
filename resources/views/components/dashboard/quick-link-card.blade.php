@props([
    'href',
    'title',
    'subtitle',
    'icon',
    'arrowClass' => 'h-4 w-4',
])

<a href="{{ $href }}"
    {{ $attributes->merge(['class' => 'group flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs transition hover:border-brand-200 hover:shadow-theme-sm']) }}>
    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-300">
        <x-dashboard.quick-link-icon :name="$icon" @class([
            'h-10 w-10' => $icon !== 'phone',
            'h-7 w-7' => $icon === 'phone',
        ]) />
    </span>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-gray-600">{{ $title }}</p>
        <p class="truncate text-xs text-gray-500">{{ $subtitle }}</p>
    </div>

    <span @class([
        'flex shrink-0 items-center justify-center text-brand-300 transition group-hover:text-brand-400',
        'h-8 w-8' => $arrowClass === 'h-4 w-4',
        'h-10 w-10' => $arrowClass !== 'h-4 w-4',
    ])>
        <x-dashboard.quick-link-icon name="arrow" :class="$arrowClass" />
    </span>
</a>
