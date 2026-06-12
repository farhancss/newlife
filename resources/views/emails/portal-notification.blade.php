@php
    $brandName = config('brand.name', 'New Life Campus');
@endphp

<x-email.layout
    :title="$brandName . ' — ' . $heading"
    :preheader="\Illuminate\Support\Str::limit(strip_tags($bodyText), 120)"
    :heading="$heading"
>
    @if ($greetingName)
        <p style="margin:0 0 12px 0;">Hi {{ $greetingName }},</p>
    @endif

    <p style="margin:0 0 12px 0;">{!! nl2br(e($bodyText)) !!}</p>

    @if ($actionUrl)
        <x-email.button :href="$actionUrl">
            {{ $actionLabel }}
        </x-email.button>
    @endif
</x-email.layout>
