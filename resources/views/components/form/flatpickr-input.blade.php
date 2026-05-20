@props([
    'name',
    'value' => null,
    'placeholder' => '',
    'icon' => 'calendar',
    'options' => [],
])

@php
    $id = 'fp-' . uniqid();

    $defaults = [
        'allowInput' => false,
        'static' => true,
        'monthSelectorType' => 'static',
        'disableMobile' => true,
    ];

    $mergedOptions = array_replace($defaults, $options);
    $jsOptions = json_encode($mergedOptions, JSON_UNESCAPED_SLASHES);

    $inputClasses = $attributes->get('class') ?: 'h-10 w-full rounded-lg border border-gray-300 px-3 pr-10 text-sm focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-200';

    $altInputClasses = $inputClasses;
@endphp

<div
    class="relative custom-datepicker"
    x-data="{
        instance: null,
        init() {
            const el = this.$refs.input;
            const opts = {{ $jsOptions }};
            opts.altInputClass = @js($altInputClasses);
            opts.onReady = (selectedDates, dateStr, fp) => {
                if (fp.calendarContainer) {
                    fp.calendarContainer.classList.add('campus-picker');
                }
            };
            this.instance = window.flatpickr(el, opts);
        },
        destroy() {
            if (this.instance) {
                this.instance.destroy();
                this.instance = null;
            }
        }
    }"
    x-init="init()"
    x-destroy="destroy()"
>
    <input
        x-ref="input"
        type="text"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        {{ $attributes->merge(['class' => $inputClasses]) }}
    />
    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
        @if ($icon === 'clock')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" />
                <polyline points="12 7 12 12 15.5 14" />
            </svg>
        @elseif ($icon)
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4.5" width="18" height="16" rx="2" />
                <line x1="3" y1="9.5" x2="21" y2="9.5" />
                <line x1="8" y1="3" x2="8" y2="6" />
                <line x1="16" y1="3" x2="16" y2="6" />
            </svg>
        @endif
    </span>
</div>
