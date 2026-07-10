@props([
    'tone' => 'success',
])

@php
    $tones = [
        'success' => 'bg-emerald-500/10 text-emerald-400',
        'error' => 'bg-rose-500/10 text-rose-400',
    ];
@endphp

<div {{ $attributes->class(['rounded-lg px-4 py-2 text-sm', $tones[$tone] ?? $tones['success']]) }}>
    {{ $slot }}
</div>
