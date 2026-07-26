@props([
    'type' => 'button',
    'full' => false,
])

<button type="{{ $type }}" wire:loading.attr="disabled"
    {{ $attributes->class([
        'rounded-lg bg-[var(--lottery-accent)] px-3 py-1.5 text-sm font-medium text-[var(--lottery-on-accent)] hover:bg-[var(--lottery-accent-hover)] disabled:opacity-60',
        'w-full' => $full,
    ]) }}>
    {{ $slot }}
</button>
