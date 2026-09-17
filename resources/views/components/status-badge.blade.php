@props(['status'])

{{-- $status is een CarStatus-enum; label en kleuren komen uit de enum zelf. --}}
<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset '
        . $status->badgeClasses(),
]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $status->label() }}
</span>
