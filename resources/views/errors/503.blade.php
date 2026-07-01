@php
    $customMessage = ($exception ?? null)?->getMessage();
    $hasCustomMessage = filled($customMessage) && $customMessage !== 'Service Unavailable';

    $title = $hasCustomMessage
        ? 'We\'ll be right back'
        : config('brand.maintenance.title', 'Opening soon');

    $message = $hasCustomMessage
        ? $customMessage
        : config('brand.maintenance.message');

    $note = $hasCustomMessage ? null : config('brand.maintenance.note');

    $codeLabel = $hasCustomMessage ? '503' : config('brand.maintenance.code_label', 'Coming soon');
@endphp

<x-layouts.error :title="$title">
    <x-errors.page
        code="503"
        :code-label="$codeLabel"
        :title="$title"
        :message="$message"
        :note="$note"
        :show-login="false"
        :show-retry="true"
    />
</x-layouts.error>
