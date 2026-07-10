@props([
    'name',
    'label',
])

<label for="{{ $name }}" class="flex items-center gap-2 text-sm text-slate-400">
    <input id="{{ $name }}" type="checkbox"
        {{ $attributes->class(['rounded border-white/10 bg-slate-800 text-emerald-500 focus:ring-emerald-500']) }}>
    {{ $label }}
</label>
