@props([
    'name',
    'label',
    'rows' => 3,
    'hint' => null,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-300">{{ $label }}</label>
    <textarea id="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->class(['mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-sm text-slate-100 focus:border-emerald-500 focus:ring-emerald-500']) }}></textarea>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name) <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
</div>
