@props([
    'name',
    'label',
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-300">{{ $label }}</label>
    <select id="{{ $name }}"
        {{ $attributes->class(['mt-1 h-10 w-full rounded-lg border-white/10 bg-slate-800 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500']) }}>
        {{ $slot }}
    </select>
    @error($name) <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
</div>
