@props([
    'lottery',
    'active',
])

@php
    $tabs = [
        'dashboard' => ['label' => 'Análises', 'route' => route('lottery.dashboard', $lottery)],
        'generator' => ['label' => 'Gerar jogos', 'route' => route('lottery.generator', $lottery)],
        'backtest' => ['label' => 'Simulador', 'route' => route('lottery.backtest', $lottery)],
        'draws' => ['label' => 'Resultados', 'route' => route('lottery.draws', $lottery)],
    ];

    $linkClass = fn (string $key) => $active === $key
        ? 'rounded-lg bg-[var(--lottery-accent)]/10 px-3 py-1.5 font-medium text-[var(--lottery-accent)]'
        : 'rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white';
@endphp

<nav class="flex flex-wrap gap-2 text-sm">
    @foreach ($tabs as $key => $tab)
        <a href="{{ $tab['route'] }}" wire:navigate class="{{ $linkClass($key) }}">{{ $tab['label'] }}</a>
    @endforeach
    @auth
        <a href="{{ route('lottery.my-games', $lottery) }}" wire:navigate class="{{ $linkClass('my-games') }}">Meus jogos</a>
    @endauth
</nav>
