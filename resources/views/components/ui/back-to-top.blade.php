<div x-data="{ show: false }" x-init="window.addEventListener('scroll', () => { show = window.scrollY > 400 })"
    x-show="show" x-transition.opacity class="fixed right-4 bottom-6 z-40 sm:right-6">
    <button type="button" @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-500 text-slate-950 shadow-lg shadow-black/30 hover:bg-emerald-400"
        aria-label="Voltar ao topo">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
        </svg>
    </button>
</div>
