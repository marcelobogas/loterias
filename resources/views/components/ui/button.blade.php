@props([
    'type' => 'button',
    'full' => false,
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'px-4 py-2 text-sm font-medium',
        default => 'px-4 py-2 font-medium',
    };
@endphp

<button type="{{ $type }}" wire:loading.attr="disabled"
    {{ $attributes->class([
        'rounded-lg bg-emerald-500 text-slate-950 hover:bg-emerald-400 disabled:opacity-60',
        $sizeClasses,
        'w-full' => $full,
    ]) }}>
    {{ $slot }}
</button>
