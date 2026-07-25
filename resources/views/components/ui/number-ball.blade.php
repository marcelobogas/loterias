@props([
    'number',
    'variant' => 'default',
    'size' => 'md',
])

@php
    $variants = [
        'default' => 'bg-slate-800 text-slate-100 ring-1 ring-white/10',
        'selected' => 'bg-[var(--lottery-accent)] text-[var(--lottery-on-accent)] font-semibold',
        'hit' => 'bg-[var(--lottery-accent)] text-[var(--lottery-on-accent)] font-semibold ring-2 ring-[var(--lottery-accent-hover)]',
        'miss' => 'bg-slate-800/60 text-slate-500 ring-1 ring-white/5',
        'hot' => 'bg-rose-500/20 text-rose-300 ring-1 ring-rose-400/40',
        'cold' => 'bg-sky-500/20 text-sky-300 ring-1 ring-sky-400/40',
    ];

    $sizes = [
        'sm' => 'h-7 w-7 text-xs',
        'md' => 'h-9 w-9 text-sm',
        'lg' => 'h-11 w-11 text-base',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center rounded-full tabular-nums',
    $variants[$variant] ?? $variants['default'],
    $sizes[$size] ?? $sizes['md'],
]) }}>
    {{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}
</span>
