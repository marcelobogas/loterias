#!/usr/bin/env bash

# git_flow.sh
# Uso:
#   scripts/git_flow.sh [--dry-run|-n] [--yes|-y] [--no-push] [file1 file2 ...]
#
# Opções:
#   --dry-run, -n  : mostra as ações que seriam executadas sem alterar o repo
#   --yes, -y      : aceita automaticamente a mensagem de commit sugerida
#   --no-push      : comita mas não faz push para origin
#   file args      : se informado, adiciona apenas os arquivos passados
#
# Fluxo principal:
#   1) Mostra o git status
#   2) Seleção interativa numerada dos arquivos modificados (ou arquivos passados por args)
#   3) Roda `php artisan version bump:patch` (OBRIGATÓRIO — o script aborta sem commitar se falhar)
#   4) Roda Pint e re-adiciona os arquivos corrigidos ao stage
#   5) Gera uma mensagem de commit automática (add/update/remove) e pede confirmação
#   6) Comita e faz push para origin/main (a menos que --no-push)
#
# O bump de versão é uma etapa obrigatória do fluxo: se `php artisan version
# bump:patch` falhar, o script para antes de commitar (ao contrário de um
# fluxo "best effort" que seguiria sem versionar).
#
# Logs em storage/logs/git-flow-log.txt

set -euo pipefail

LOGFILE="storage/logs/git-flow-log.txt"

log() {
    local msg="$(date '+%Y-%m-%d %H:%M:%S') - $*"
    if [ "${DRY_RUN:-false}" != "true" ]; then
        echo "$msg" | tee -a "$LOGFILE"
    else
        echo "$msg"
    fi
}
trap 'log "Script failed at line $LINENO"; exit 1' ERR

RED='\033[0;31m' GREEN='\033[0;32m' YELLOW='\033[1;33m' NC='\033[0m'

if ! git rev-parse --git-dir > /dev/null 2>&1; then echo -e "${RED}Not a git repo${NC}"; exit 1; fi

DRY_RUN=false
AUTO_YES=false
NO_PUSH=false
FILE_ARGS=()
while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run|-n) DRY_RUN=true ;;
        --yes|-y)     AUTO_YES=true ;;
        --no-push)    NO_PUSH=true ;;
        *)            FILE_ARGS+=("$1") ;;
    esac
    shift
done
set -- "${FILE_ARGS[@]+"${FILE_ARGS[@]}"}"

if [ "$DRY_RUN" != "true" ]; then
    mkdir -p "$(dirname "$LOGFILE")"
fi

run_cmd() {
    echo "-> $*"
    if [ "$DRY_RUN" = "true" ]; then
        echo "[DRY-RUN] skipping execution"
        return 0
    fi
    "$@"
}

# Versionamento automático: SEMPRE incrementa a versão (patch) e adiciona o
# arquivo VERSION ao stage antes do commit — etapa obrigatória do fluxo.
# `php artisan version bump:patch` pede confirmação interativa, então
# alimentamos "yes" via stdin (não usar --no-interaction: isso faria o
# confirm assumir o default "no" e cancelar o bump).
run_versioning() {
    echo "-> php artisan version bump:patch (auto-confirmado, obrigatório)"
    if [ "$DRY_RUN" = "true" ]; then
        echo "[DRY-RUN] skipping execution"
        return 0
    fi
    if [ ! -f artisan ]; then
        echo -e "${RED}artisan não encontrado; versionamento é obrigatório, abortando.${NC}"
        exit 1
    fi
    if printf 'yes\n' | php artisan version bump:patch; then
        run_cmd git add VERSION
        log "Versão incrementada (bump:patch) e VERSION adicionado ao stage."
    else
        echo -e "${RED}Versionamento falhou; abortando commit.${NC}"
        exit 1
    fi
}

ADDED_FILES=()
MODIFIED_FILES=()
DELETED_FILES=()

echo
echo "== Git status (branch atual: $(git rev-parse --abbrev-ref HEAD)) =="
git status --short --untracked-files=all || true
echo

if [ "$#" -gt 0 ]; then
    for f in "$@"; do
        if [ -e "$f" ]; then run_cmd git add "$f"; log "git add $f"; fi
        s=$(git status --porcelain -- "$f" 2>/dev/null || true)
        code="${s:0:2}"
        if [[ "$code" == *D* ]]; then DELETED_FILES+=("$f"); fi
        if [[ "$code" == *A* ]]; then ADDED_FILES+=("$f"); fi
        if [[ ! "$code" =~ A|D ]]; then MODIFIED_FILES+=("$f"); fi
        if git ls-files --deleted --error-unmatch "$f" > /dev/null 2>&1; then run_cmd git rm --quiet --ignore-unmatch "$f"; log "git rm $f"; fi
    done
else
    changed=$(git status --porcelain)
    if [ -z "$changed" ]; then
        echo "Nada para commitar."; exit 0
    fi

    mapfile -t status_lines < <(git status --porcelain)
    mapfile -t files < <(git status --porcelain | sed -E 's/^.{3}//')

    echo "Arquivos alterados:"
    for i in "${!files[@]}"; do
        idx=$((i+1))
        printf "%3d) %s\n" "$idx" "${files[$i]}"
    done

    echo
    echo "Selecione arquivos para adicionar (ex: 1,3-5 ou all). Enter para sair sem alterações."
    if [ -e /dev/tty ]; then
        read -r -p "Seleção: " selection < /dev/tty
    else
        read -r -p "Seleção: " selection
    fi
    if [ -z "$selection" ]; then echo "Abortando: nenhuma seleção"; exit 1; fi

    if [ "$selection" = "all" ]; then
        run_cmd git add -A
        for i in "${!files[@]}"; do
            st="${status_lines[$i]}"
            code="${st:0:2}"
            f="${files[$i]}"
            if [[ "$code" == *D* ]]; then DELETED_FILES+=("$f"); fi
            if [[ "$code" == *A* ]]; then ADDED_FILES+=("$f"); fi
            if [[ ! "$code" =~ A|D ]]; then MODIFIED_FILES+=("$f"); fi
        done
    else
        IFS=',' read -ra parts <<< "$selection"
        for p in "${parts[@]}"; do
            if [[ "$p" =~ ^([0-9]+)-([0-9]+)$ ]]; then
                start=${BASH_REMATCH[1]}; end=${BASH_REMATCH[2]}
                for ((n=start; n<=end; n++)); do
                    idx=$((n-1)); f="${files[$idx]}"
                    if [ -n "$f" ]; then
                        if [ -e "$f" ]; then run_cmd git add "$f"; log "git add $f"; ADDED_FILES+=("$f"); fi
                        if git ls-files --deleted --error-unmatch "$f" > /dev/null 2>&1; then run_cmd git rm --quiet --ignore-unmatch "$f"; log "git rm $f"; DELETED_FILES+=("$f"); fi
                        if [ -e "$f" ] && ! git ls-files --deleted --error-unmatch "$f" > /dev/null 2>&1; then
                            if [[ ! " ${ADDED_FILES[*]} " =~ " $f " ]]; then MODIFIED_FILES+=("$f"); fi
                        fi
                    fi
                done
            elif [[ "$p" =~ ^[0-9]+$ ]]; then
                idx=$((p-1)); f="${files[$idx]}"
                if [ -n "$f" ]; then
                    if [ -e "$f" ]; then run_cmd git add "$f"; log "git add $f"; ADDED_FILES+=("$f"); fi
                    if git ls-files --deleted --error-unmatch "$f" > /dev/null 2>&1; then run_cmd git rm --quiet --ignore-unmatch "$f"; log "git rm $f"; DELETED_FILES+=("$f"); fi
                    if [ -e "$f" ] && ! git ls-files --deleted --error-unmatch "$f" > /dev/null 2>&1; then
                        if [[ ! " ${ADDED_FILES[*]} " =~ " $f " ]]; then MODIFIED_FILES+=("$f"); fi
                    fi
                fi
            else
                echo "Entrada inválida: $p"; exit 1
            fi
        done
    fi
fi

staged_count=$(git diff --cached --name-only | wc -l | tr -d ' ')
if [ "$staged_count" -eq 0 ]; then
    log "Nenhuma alteração staged; nada a commitar."
    exit 0
fi

# Versionamento é obrigatório sempre que há algo staged para commit.
run_versioning

# Auto-fix de estilo e re-stage antes do commit.
if [ -x ./vendor/bin/pint ]; then
    run_cmd ./vendor/bin/pint || { echo "pint falhou"; exit 1; }
    run_cmd git add -u
else
    log "Pint não encontrado em ./vendor/bin/pint; pulando checagem de estilo."
fi

# Mensagem de commit automática: type(scope): description
guess_type() {
    for f in "${ADDED_FILES[@]}" "${MODIFIED_FILES[@]}" "${DELETED_FILES[@]}"; do
        if [ -z "$f" ]; then continue; fi
        if echo "$f" | grep -qE '\btest\b|/tests/|/Test'; then echo "test"; return; fi
        if echo "$f" | grep -qE '\bdoc\b|README|/docs/'; then echo "docs"; return; fi
        if echo "$f" | grep -qE '\bfix\b|bug|hotfix'; then echo "fix"; return; fi
    done
    echo "feat"
}

derive_scope() {
    f="$1"
    if [ -z "$f" ]; then echo "repo"; return; fi
    f=${f#./}
    IFS='/' read -ra parts <<< "$f"
    if [ ${#parts[@]} -ge 2 ]; then
        if [[ "${parts[0]}" == "app" || "${parts[0]}" == "resources" || "${parts[0]}" == "routes" ]]; then
            echo "${parts[1]}" | tr '[:upper:]' '[:lower:]'
            return
        fi
    fi
    echo "${parts[0]}" | tr '[:upper:]' '[:lower:]'
}

build_description() {
    desc_parts=()
    if [ ${#ADDED_FILES[@]} -gt 0 ]; then desc_parts+=("add ${#ADDED_FILES[@]}"); fi
    if [ ${#MODIFIED_FILES[@]} -gt 0 ]; then desc_parts+=("update ${#MODIFIED_FILES[@]}"); fi
    if [ ${#DELETED_FILES[@]} -gt 0 ]; then desc_parts+=("remove ${#DELETED_FILES[@]}"); fi
    if [ ${#desc_parts[@]} -eq 0 ]; then echo "update files"; else echo "${desc_parts[*]}"; fi
}

first_file=""
if [ ${#ADDED_FILES[@]} -gt 0 ]; then first_file="${ADDED_FILES[0]}"
elif [ ${#MODIFIED_FILES[@]} -gt 0 ]; then first_file="${MODIFIED_FILES[0]}"
elif [ ${#DELETED_FILES[@]} -gt 0 ]; then first_file="${DELETED_FILES[0]}"
fi

type=$(guess_type)
scope=$(derive_scope "$first_file")
desc=$(build_description)
auto_msg="${type}(${scope}): ${desc}"

echo "Sugestão de commit automático:"
echo "$auto_msg"
if [ "$AUTO_YES" = "true" ]; then
    echo "--yes detectado: usando mensagem automática"
    msg="$auto_msg"
else
    if [ -e /dev/tty ]; then
        read -n 1 -r -p "Usar mensagem automática? (S/n): " REPLY < /dev/tty; echo
    else
        read -n 1 -r -p "Usar mensagem automática? (S/n): " REPLY; echo
    fi
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        if [ -e /dev/tty ]; then
            read -r -p "Mensagem de commit (uma linha): " msg < /dev/tty
        else
            read -r -p "Mensagem de commit (uma linha): " msg
        fi
        if [ -z "$msg" ]; then echo "Mensagem vazia, abortando."; exit 1; fi
    else
        msg="$auto_msg"
    fi
fi

run_cmd git commit -m "$msg"
if [ "$DRY_RUN" = "true" ]; then
    log "Commitaria: $msg"
else
    log "Commitado: $msg"
fi

if [ "$NO_PUSH" = "true" ]; then
    log "Flag --no-push detectada; pulando push."
    echo; echo -e "${GREEN}Commit concluído (sem push).${NC}"; echo
    exit 0
fi

run_cmd git fetch origin main || true
if ! run_cmd git push origin main; then
    log "push origin main falhou; tentando pull --rebase e novo push"
    run_cmd git pull --rebase origin main
    run_cmd git push origin main
fi

log "Fluxo concluído"
echo; echo -e "${GREEN}Concluído${NC}"; echo
