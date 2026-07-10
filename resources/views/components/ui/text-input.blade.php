@props([
    'name',
    'label',
    'type' => 'text',
    'hint' => null,
    'compact' => false,
])

<div>
    <label for="{{ $name }}" class="block {{ $compact ? 'text-xs text-slate-400' : 'text-sm font-medium text-slate-300' }}">{{ $label }}</label>
    <input id="{{ $name }}" type="{{ $type }}"
        {{ $attributes->class([
            'mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500',
            'text-sm' => $compact,
        ]) }}>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name) <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
</div>
