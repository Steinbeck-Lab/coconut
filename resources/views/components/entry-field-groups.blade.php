@props([
    'groups' => [],
    'linkPrefix' => null,
    'linkAsUrl' => false,
])

@php
    use App\Services\EntryFieldFormatter;
@endphp

<div {{ $attributes->merge(['class' => 'text-sm text-gray-500']) }}>
    {!! EntryFieldFormatter::toHtml($groups, $linkPrefix, $linkAsUrl) !!}
</div>
