@props([
    'src' => null,
    'initials' => 'NL',
    'reactive' => false,
])

@if ($reactive)
    <span class="contents" x-data>
        <template x-if="$store.userAvatar.url">
            <img
                :src="$store.userAvatar.url"
                :alt="$store.userAvatar.initials"
                {{ $attributes->class(['object-cover']) }}
            />
        </template>
        <template x-if="!$store.userAvatar.url">
            <span {{ $attributes->class(['inline-flex items-center justify-center']) }} x-text="$store.userAvatar.initials"></span>
        </template>
    </span>
@elseif ($src)
    <img src="{{ $src }}" alt="{{ $initials }}" {{ $attributes->class(['object-cover']) }} />
@else
    <span {{ $attributes->class(['inline-flex items-center justify-center']) }}>{{ $initials }}</span>
@endif
