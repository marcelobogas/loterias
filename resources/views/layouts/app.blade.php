<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? "{$title} · " : '' }}{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex h-full min-h-screen flex-col bg-slate-950 text-slate-100 antialiased">
    <header class="border-b border-white/10 bg-slate-900/60 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-y-2 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 text-lg font-semibold text-white">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-slate-950">L</span>
                Loterias
            </a>

            <div class="flex flex-wrap items-center gap-3 text-sm sm:gap-4">
                @auth
                    <a href="{{ route('profile.edit') }}" wire:navigate class="max-w-[10rem] truncate text-slate-300 hover:text-white">
                        {{ auth()->user()->name }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-300 hover:text-white">Sair</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-slate-300 hover:text-white">Entrar</a>
                    <a href="{{ route('register') }}" wire:navigate class="rounded-lg bg-emerald-500 px-3 py-1.5 font-medium text-slate-950 hover:bg-emerald-400">
                        Criar conta
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    <x-ui.responsible-gaming-banner />

    @livewireScripts
</body>
</html>
