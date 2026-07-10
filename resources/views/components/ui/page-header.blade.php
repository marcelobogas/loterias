@props([
    'title',
    'subtitle' => null,
])

<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-white">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-sm text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        {{ $actions }}
    @endisset
</div>
