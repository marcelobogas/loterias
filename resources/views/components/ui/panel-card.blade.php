@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->class(['rounded-2xl border border-white/10 bg-slate-900/60 p-5 shadow-sm']) }}>
    @if ($title || $subtitle || isset($actions))
        <header class="mb-4 flex items-start justify-between gap-4">
            <div>
                @if ($title)
                    <h2 class="text-base font-semibold text-white">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    {{ $slot }}
</section>
