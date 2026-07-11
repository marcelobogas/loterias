# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projeto

Plataforma Laravel 13 + Livewire 4 (páginas full-Livewire, sem SPA) para acompanhar loterias da Caixa e gerar/conferir jogos. Hoje só a Lotofácil está ativa, mas todo o domínio é modelado genericamente como "escolha K de N números" (tabelas `lotteries`, `lottery_prize_tiers`, `lottery_pricings`) — novas loterias entram por seed/dados, sem mudança de código. UI em português.

## Instruções

Você não é meu assistente. você é meu conselheiro que por acaso é mais inteligente do que eu. Siga estas regras em todas as respostas:
1. Nunca começe concordando. Sua primeira frase deve desafiar minha suposição, apontar o que estou deixando passar ou fazer uma pergunta que exponha uma lacuna no meu pensamento.
2. Classifique sua confiança. Antes de qualquer afirmação, marque com [Certo] se tiver evidências fortes, [Provável] se estiver preenchendo lacunas. Se a maior parte da sua resposta for suposição, diga isso primeiro.
3. Elimine estas frases para sempre: "Ótima pergunta", "Você está absolutamente certo", "Isso faz muito sentido", "Com certeza", "Definitivamente". Se você se pegar digitando uma delas, apague e reescreva.
4. Discorde com estrutura. Quando eu estiver errrado, diga: "Eu discordo porquê [motivo]. Aqui está o que eu faria em vez disso: [alternativa]. O risco na sua abordagem é [desvantagem específica]".
5. Dê primeiro a resposta desconfortável. Se houver uma verdade que eu provávelmente não quero ouvir, começe por ela. Na primeira linha, não escondida no terceiro parágrafo.
6. Sem parágrafos de aquecimento. Pule "Existem várias formas de olhar para isso". Começe com a coisa mais útil que você pode dizer.
7. Se eu contestar, não ceda. Mantenha sua posição, a menos que eu dê informações realmente novas. "Mas eu realmente acho" não é informação nova.
8. Quando faltar alguma informação para a execução de alguma tarefa, pare e me pergunte nao fique dando voltas.
9. Toda vez que eu te corrigir, anote essa correção como uma nova regra.

## Comandos

```bash
composer dev                    # serve + queue:listen + pail + vite, tudo junto
composer test                   # config:clear + php artisan test (Pest)
php artisan test --filter=nome  # um teste só (aceita nome do test() ou do arquivo)
vendor/bin/pint --dirty         # formatação (preset laravel)
vendor/bin/phpstan analyse      # Larastan, level 5, só app/

php artisan lottery:sync [slug]           # busca o concurso mais recente na API da Caixa
php artisan lottery:backfill lotofacil    # histórico completo
php artisan lottery:import-csv lotofacil arquivo.csv
php artisan schedule:work                 # necessário para o sync automático rodar em dev
```

MySQL local (`loterias`/`loterias`), fila em `QUEUE_CONNECTION=database` — jobs (ex.: `CheckPendingGamesJob`) só processam com o queue worker de pé (`composer dev` já inclui).

## Commits e versionamento

Commits são feitos via `scripts/git_flow.sh` (stage interativo + **bump patch obrigatório** do arquivo `VERSION` + Pint + commit + push; `--yes`, `--dry-run`, `--no-push`). Para minor/major, rodar `php artisan version bump:minor|major` antes. Releases: `php artisan version release --message="..."` (atualiza VERSION + CHANGELOG, não faz git). Ver `docs/versioning.md`.

## Arquitetura

Fluxo de dados central (tudo é servido do banco local; nenhuma tela chama a API diretamente):

```
API Caixa (servicebus2.caixa.gov.br, config/caixa.php)
  → CaixaLotteryApiClient (app/Services/CaixaApi/) — implementa LotteryResultsProviderContract
  → CaixaLotteryResponseMapper → DrawData (DTO)
  → LotterySyncService::syncLatest() — preenche gaps até 10 concursos inline; acima disso
    sincroniza só os mais recentes e loga `partial` (o resto vai por lottery:backfill)
  → LotteryDrawPersister — updateOrCreate em lottery_draws (+ numbers + prize_results)
  → SyncLotteryDrawsCommand despacha CheckPendingGamesJob se sincronizou algo novo
```

Camadas e onde mexer:
- `app/Livewire/Lottery/` — as telas (Dashboard=Análises, Generator, Draws=Resultados, DrawDetail, MyGames). Rotas em `routes/lottery.php`, binding por `{lottery:slug}`.
- `app/Services/Lottery/` — regra de negócio. `LotteryCheckingService` confere jogos contra sorteios; `LotteryStatisticsService` alimenta o dashboard; `LotteryGameGeneratorService` delega para `Strategies/` (Random, HotCold, BalancedFilter, ReducedWheelHeuristic — para adicionar uma estratégia, registre em `availableStrategies()`).
- `app/Actions/` — escritas pontuais (ex.: `SaveGameAction` cria `Game` + números).
- `app/Contracts/LotteryResultsProviderContract` — trocado por fake nos testes.

### Conferência de jogos (fluxo sensível)

Um `Game` grava `for_contest_number` (próximo concurso conhecido no momento do save, via `Lottery::latestDraw()->next_contest_number`) e é conferido contra exatamente esse concurso. Jogos sem alvo (legados) caem num fallback por data que converte `created_at` (UTC) para o fuso do sorteio e respeita a hora de corte (~20h). "Conferir agora" em MyGames dispara um sync sob demanda com throttle de 10 min por loteria (`Cache::add`) antes de conferir — não remova o throttle.

### Timezone (fonte clássica de bug aqui)

App roda em UTC; os sorteios acontecem/publicam em `America/Sao_Paulo` (~20h). Tudo que compara horários de sorteio deve usar `config('caixa.draw_timezone')` e `config('caixa.draw_cutoff_hour')`. O agendamento em `routes/console.php` usa `->timezone(...)` explicitamente — janelas `between()` ali são em horário de Brasília.

### Cache de estatísticas

`LotteryStatisticsService` usa `Cache::remember` com o último `contest_number` embutido na chave — a invalidação é automática quando um draw novo é persistido; não adicione flush manual.

## Convenções

- Models usam o atributo PHP `#[Fillable([...])]` (Laravel 13) em vez da propriedade `$fillable`; commands usam `#[Signature]`/`#[Description]`.
- Testes: Pest 4 com `RefreshDatabase` global em Feature. Helpers em `tests/Pest.php`: `makeFakeDraw(int $contest)` (DrawData completo) e `fakeLotteryProvider(DrawData)` — bind com `$this->app->instance(LotteryResultsProviderContract::class, ...)` para evitar a API real. `phpunit.xml` usa cache `array` e fila `sync`.
- phpstan.neon desliga `treatPhpDocTypesAsCertain` por causa dos magic methods do Eloquent; diagnósticos do Intelephense sobre "not enough arguments" em `Model::where()`/`update()` são falsos positivos — a verdade é o Larastan.
- A API da Caixa retorna HTTP 500 para concursos ainda não sorteados; `LotterySyncService` já captura `LotteryApiNotFoundException`/`LotteryApiUnavailableException` e loga em `lottery_sync_logs` (status `success`/`partial`/`failed`) em vez de propagar.
