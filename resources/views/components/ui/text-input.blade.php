@props([
    'name',
    'label' => null,
    'type' => 'text',
    'hint' => null,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-slate-300">{{ $label }}</label>
    @endif
    <input id="{{ $name }}" type="{{ $type }}"
        {{ $attributes->class(['mt-1 h-10 w-full rounded-lg border-white/10 bg-slate-800 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500']) }}>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name) <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
</div>
