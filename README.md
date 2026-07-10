# Loterias

Plataforma para acompanhar e apostar nas loterias da Caixa Econômica Federal. Hoje cobre a **Lotofácil**; o restante do domínio (regras, preços, faixas de prêmio) já é modelado de forma genérica ("escolha K de N números") para receber Mega-Sena, Quina e demais loterias apenas com dados novos, sem mudança de código.

Stack: Laravel 13, Livewire 4 (páginas full-Livewire), Tailwind CSS 4, Alpine.js (empacotado pelo próprio Livewire), MySQL, Chart.js, Fortify, Pest, Pint, Larastan.

## Funcionalidades

- **Sincronização automática** dos resultados via API pública da Caixa, com fallback de importação manual por CSV.
- **Dashboard de análises**: frequência, atraso, distribuição de soma/paridade e pares mais frequentes, por janela configurável.
- **Gerador de jogos** com estratégias (aleatório, quente/frio, balanceado por filtros, fechamento reduzido heurístico) e preço calculado pela tabela oficial.
- **Meus jogos**: histórico salvo por usuário, com conferência automática após cada sincronização e resumo de ROI.

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# ajuste DB_* no .env para seu MySQL local

php artisan migrate --seed
npm run build
```

## Comandos artisan específicos do domínio

```bash
php artisan lottery:sync [slug]              # busca o concurso mais recente (todas as loterias ativas se slug omitido)
php artisan lottery:backfill lotofacil       # carrega o histórico completo de concursos
php artisan lottery:import-csv lotofacil arquivo.csv  # fallback manual (colunas: concurso,data,dezenas)
```

O `lottery:sync` já está agendado (`routes/console.php`) para rodar de hora em hora na janela real de sorteio da Lotofácil (18h–22h, seg–sáb), e dispara a conferência automática dos jogos salvos assim que sincroniza um concurso novo.

## Testes e qualidade

```bash
php artisan test
./vendor/bin/pint
./vendor/bin/phpstan analyse
```
