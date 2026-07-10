@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'default',
])

@php
    $tones = [
        'default' => 'text-white',
        'positive' => 'text-emerald-400',
        'negative' => 'text-rose-400',
    ];
@endphp

<div {{ $attributes->class(['rounded-2xl border border-white/10 bg-slate-900/60 p-4']) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold {{ $tones[$tone] ?? $tones['default'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
