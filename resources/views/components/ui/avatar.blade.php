@props([
    'src' => null,
    'initials' => 'NL',
])

@if ($src)
    <img src="{{ $src }}" alt="{{ $initials }}" {{ $attributes->class(['object-cover']) }} />
@else
    <span {{ $attributes }}>{{ $initials }}</span>
@endif
