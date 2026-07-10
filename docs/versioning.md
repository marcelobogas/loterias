# Versionamento

O projeto usa **Semantic Versioning** ([SemVer](https://semver.org/lang/pt-BR/)): `MAJOR.MINOR.PATCH`.

A fonte de verdade da versão atual é o arquivo `VERSION` na raiz do repositório.

---

## Política de bump

| Tipo | Quando usar | Exemplos |
|------|-------------|---------|
| **MAJOR** | Quebra de compatibilidade: mudança de schema destrutiva, redesign total de um fluxo | Reestruturação do modelo "pick K de N", remoção de rotas |
| **MINOR** | Nova loteria suportada, nova tela, nova estratégia de geração | Ativar Mega-Sena, adicionar fechamento reduzido |
| **PATCH** | Bugfix, ajuste visual, correção de preço/regra, atualização de dependência não-breaking | Correção do valor da aposta, exclusão de jogos |

---

## Comandos Artisan

```bash
# Ver versão atual e informações do build
php artisan version show

# Atualizar apenas o número de versão
php artisan version bump:patch     # 1.0.0 → 1.0.1
php artisan version bump:minor     # 1.0.0 → 1.1.0
php artisan version bump:major     # 1.0.0 → 2.0.0

# Criar uma release (bump + CHANGELOG + instruções de tag)
php artisan version release --message="Descrição das mudanças"
```

O comando `release`:
1. Pergunta o tipo de bump (patch/minor/major)
2. Atualiza `VERSION`
3. Prepende a entrada no `CHANGELOG.md` com data e mensagem
4. Exibe os passos seguintes para commit e tag Git

> O comando `release` **não executa** git automaticamente — é o desenvolvedor quem commita e cria a tag.

---

## Bump automático no fluxo Git

O script `scripts/git_flow.sh` roda **`php artisan version bump:patch` obrigatoriamente antes de cada commit** (a confirmação interativa é respondida via stdin) e adiciona o arquivo `VERSION` ao stage. Se o bump falhar por qualquer motivo, o script **aborta e não commita** — diferente de um bump opcional, ele é uma etapa obrigatória do fluxo.

- Para um bump **minor/major** (nova loteria, feature grande, breaking change), rode o comando manualmente **antes** de iniciar o fluxo: `php artisan version bump:minor` — o `git_flow.sh` ainda incrementará o patch sobre ele. Se preferir controlar exatamente o número final, edite o arquivo `VERSION` manualmente antes de rodar o script.
- O bump é pulado apenas se não houver nada staged (sem commit, sem bump).

---

## Workflow de commit do dia a dia

```bash
# Faz stage, versiona (patch obrigatório), roda Pint e comita
scripts/git_flow.sh

# Comita apenas arquivos específicos
scripts/git_flow.sh app/Livewire/Lottery/MyGames.php

# Aceita a mensagem sugerida automaticamente, sem prompts
scripts/git_flow.sh --yes

# Mostra o que seria feito, sem alterar nada
scripts/git_flow.sh --dry-run

# Faz o commit mas não dá push
scripts/git_flow.sh --no-push
```

## Workflow de release

```bash
# 1. Implementar e testar as mudanças normalmente

# 2. Criar a release
php artisan version release --message="Adiciona suporte à Mega-Sena"

# 3. Commitar os arquivos de versão (pode usar o próprio git_flow.sh)
git add VERSION CHANGELOG.md
git commit -m "release: v1.1.0 — Adiciona suporte à Mega-Sena"

# 4. Criar a tag e publicar
git tag v1.1.0
git push origin main --tags
```

---

## CHANGELOG.md

O arquivo `CHANGELOG.md` na raiz documenta todas as releases.

- Entradas são adicionadas no topo pelo comando `version release`
- Formato: `## [X.Y.Z] — YYYY-MM-DD` seguido da descrição das mudanças
