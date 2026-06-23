@props([
    'name' => 'package',
])

@php
    $icons = [
        'check-badge' => '<circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.5"/><path d="M8.5 12.25l2.25 2.25 4.75-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'clipboard' => '<path d="M9 5.5h6a1.5 1.5 0 011.5 1.5V19a1.5 1.5 0 01-1.5 1.5H9A1.5 1.5 0 017.5 19V7A1.5 1.5 0 019 5.5z" stroke="currentColor" stroke-width="1.5"/><path d="M10 5.5V4.75A1.25 1.25 0 0111.25 3.5h1.5A1.25 1.25 0 0114 4.75V5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'profile' => '<circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 19.5c.75-2.75 3-4 5.5-4s4.75 1.25 5.5 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'containers' => '<path d="M4.5 9.5l7.5-4 7.5 4-7.5 4-7.5-4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M4.5 9.5V16l7.5 4 7.5-4V9.5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 13.5V20.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'package' => '<path d="M5 8.5l7-3.5 7 3.5v7L12 19l-7-3.5v-7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 12v7M5 8.5l7 3.5 7-3.5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
        'house' => '<path d="M4.5 11.5L12 5l7.5 6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.5 10.5V18.5h11V10.5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 18.5v-4h4v4" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
        'building' => '<path d="M7 19.5V9.5l5-3 5 3v10" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10.5 19.5v-3.5h3v3.5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9.5 12h1.25M13.25 12H14.5M9.5 14.75h1.25M13.25 14.75H14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
    ];

    $paths = $icons[$name] ?? $icons['package'];
    $isDome = $name === 'dome';
@endphp

@if ($isDome)
    <img src="{{ asset('images/dashboard/dome.svg') }}" alt=""
        {{ $attributes->merge(['aria-hidden' => 'true']) }} />
@else
<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'xmlns' => 'http://www.w3.org/2000/svg', 'aria-hidden' => 'true']) }}>
    {!! $paths !!}
</svg>
@endif
