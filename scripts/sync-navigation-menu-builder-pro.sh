#!/usr/bin/env bash
set -euo pipefail

# -----------------------------------------------------------------------------
# R4Software CMS - Sync Navigation Menu Builder Pro
# -----------------------------------------------------------------------------
# Scopo:
#   Copiare l'aggiornamento Navigation Menu Builder Pro / Editor V5 Menu Pro
#   da un progetto sorgente verso una o più installazioni Laravel derivate.
#
# Modalità sicura:
#   Di default NON copia i template pubblici più delicati:
#     - resources/views/layouts/app.blade.php
#     - resources/views/partials/navbar.blade.php
#     - resources/views/page/show.blade.php
#
#   Questi file possono contenere personalizzazioni per singola installazione.
#   Per includerli esplicitamente usare:
#     --include-public-templates
#
# Uso consigliato:
#   bash scripts/sync-navigation-menu-builder-pro.sh --local-defaults --dry-run
#   bash scripts/sync-navigation-menu-builder-pro.sh --local-defaults
#
# Uso con template pubblici inclusi, solo quando sai cosa stai facendo:
#   bash scripts/sync-navigation-menu-builder-pro.sh --local-defaults --include-public-templates
# -----------------------------------------------------------------------------

SOURCE=""
TARGETS=()
DRY_RUN=0
USE_LOCAL_DEFAULTS=0
INCLUDE_PUBLIC_TEMPLATES=0
BRANCH_PREFIX="sync/navigation-menu-builder-pro"
COMMIT_MESSAGE="Sync Navigation Menu Builder Pro update"
MAIN_BRANCH="main"
PHP_BIN="php"
SKIP_ARTISAN=0

DEFAULT_SOURCE="/Users/manuelericci/Sites/cms_r4software_demo"
DEFAULT_PHP_BIN="/Applications/MAMP/bin/php/php8.4.2/bin/php"
DEFAULT_TARGETS=(
  "/Users/manuelericci/Sites/cms_r4software"
  "/Users/manuelericci/Sites/cms_memoriamica"
  "/Users/manuelericci/Sites/cms_crewcorepro"
  "/Users/manuelericci/Sites/crm_garcone"
)

# File core sicuri: builder admin, servizi, asset e documentazione.
FILES_CORE=(
  "app/Http/Controllers/Admin/PageVisualEditorV5Controller.php"
  "app/Services/Navigation/MenuBuilderService.php"

  "public/assets/admin/navigation-menu-builder-pro/menu-builder.css"
  "public/assets/admin/navigation-menu-builder-pro/menu-builder.js"
  "public/assets/admin/visual-editor-v5/ui/menu-pro.js"
  "public/assets/admin/visual-editor-v5/ui/left-sidebar.js"
  "public/assets/admin/visual-editor-v5/panels/panels.css"

  "resources/views/admin/menus/edit.blade.php"
  "resources/views/admin/menus/_form.blade.php"
  "resources/views/admin/menus/partials/_builder-settings-panels.blade.php"
  "resources/views/admin/menus/partials/_item-modal.blade.php"

  "docs/navigation-menu-builder-pro-produzione-2026-05-08.md"
  "docs/editor-v5-menu-pro-recap-2026-05-08.md"
  "docs/navigation-menu-builder-pro-recap-2026-05-08.md"
)

# File pubblici sensibili: da copiare solo con --include-public-templates.
FILES_PUBLIC_TEMPLATES=(
  "resources/views/layouts/app.blade.php"
  "resources/views/partials/navbar.blade.php"
  "resources/views/partials/navigation/site-menu.blade.php"
  "resources/views/page/show.blade.php"
)

FILES=()

log() { printf '\033[1;34m[SYNC]\033[0m %s\n' "$1"; }
ok() { printf '\033[1;32m[OK]\033[0m %s\n' "$1"; }
warn() { printf '\033[1;33m[WARN]\033[0m %s\n' "$1"; }
fail() { printf '\033[1;31m[ERROR]\033[0m %s\n' "$1" >&2; exit 1; }

run() {
  if [[ "$DRY_RUN" == "1" ]]; then
    printf '\033[0;36m[DRY-RUN]\033[0m %s\n' "$*"
  else
    "$@"
  fi
}

usage() {
  cat <<EOF
Uso:
  $0 --source PATH --target PATH [--target PATH ...] [opzioni]

Opzioni:
  --local-defaults              Usa i percorsi locali predefiniti.
  --source PATH                 Progetto sorgente aggiornato.
  --target PATH                 Progetto target da aggiornare. Puoi indicarne più di uno.
  --include-public-templates    Include anche layout/navbar/show. Usare solo dopo verifica.
  --dry-run                     Mostra cosa farebbe senza modificare file/git.
  --php-bin PATH                Binario PHP da usare per artisan. Default: php
  --skip-artisan                Non esegue i comandi php artisan clear.
  --main-branch NAME            Branch principale. Default: main
  --branch-prefix NAME          Prefisso branch temporaneo. Default: sync/navigation-menu-builder-pro
  --help                        Mostra questa guida.

Esempio sicuro:
  $0 --local-defaults --dry-run
  $0 --local-defaults

Esempio con template pubblici inclusi:
  $0 --local-defaults --include-public-templates --dry-run
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --local-defaults) USE_LOCAL_DEFAULTS=1; shift ;;
    --source) SOURCE="${2:-}"; shift 2 ;;
    --target) TARGETS+=("${2:-}"); shift 2 ;;
    --include-public-templates) INCLUDE_PUBLIC_TEMPLATES=1; shift ;;
    --dry-run) DRY_RUN=1; shift ;;
    --php-bin) PHP_BIN="${2:-php}"; shift 2 ;;
    --skip-artisan) SKIP_ARTISAN=1; shift ;;
    --main-branch) MAIN_BRANCH="${2:-main}"; shift 2 ;;
    --branch-prefix) BRANCH_PREFIX="${2:-sync/navigation-menu-builder-pro}"; shift 2 ;;
    --help|-h) usage; exit 0 ;;
    *) fail "Argomento non riconosciuto: $1" ;;
  esac
done

if [[ "$USE_LOCAL_DEFAULTS" == "1" ]]; then
  [[ -n "$SOURCE" ]] || SOURCE="$DEFAULT_SOURCE"
  [[ "$PHP_BIN" == "php" ]] && PHP_BIN="$DEFAULT_PHP_BIN"
  if [[ ${#TARGETS[@]} -eq 0 ]]; then
    TARGETS=("${DEFAULT_TARGETS[@]}")
  fi
fi

FILES=("${FILES_CORE[@]}")
if [[ "$INCLUDE_PUBLIC_TEMPLATES" == "1" ]]; then
  FILES+=("${FILES_PUBLIC_TEMPLATES[@]}")
fi

[[ -n "$SOURCE" ]] || fail "Devi indicare --source PATH oppure usare --local-defaults"
[[ ${#TARGETS[@]} -gt 0 ]] || fail "Devi indicare almeno un --target PATH oppure usare --local-defaults"
[[ -d "$SOURCE" ]] || fail "Source non trovato: $SOURCE"
[[ -d "$SOURCE/.git" ]] || fail "Source non è un repository Git: $SOURCE"

SOURCE_ABS="$(cd "$SOURCE" && pwd)"
SYNC_BRANCH="${BRANCH_PREFIX}-$(date +%Y%m%d-%H%M%S)"

log "Source: $SOURCE_ABS"
log "Branch temporaneo: $SYNC_BRANCH"
log "PHP bin: $PHP_BIN"
[[ "$DRY_RUN" == "1" ]] && warn "Modalità dry-run attiva: nessuna modifica verrà applicata."
if [[ "$INCLUDE_PUBLIC_TEMPLATES" == "1" ]]; then
  warn "Template pubblici inclusi: layout/navbar/show verranno copiati."
else
  warn "Modalità sicura: template pubblici esclusi. Usa --include-public-templates solo dopo verifica."
fi

log "Target selezionati:"
for TARGET in "${TARGETS[@]}"; do log "- $TARGET"; done

log "Verifico file sorgenti..."
for FILE in "${FILES[@]}"; do
  if [[ ! -f "$SOURCE_ABS/$FILE" ]]; then
    warn "File sorgente mancante, verrà saltato: $FILE"
  fi
done

sync_target() {
  local TARGET="$1"
  [[ -d "$TARGET" ]] || fail "Target non trovato: $TARGET"
  [[ -d "$TARGET/.git" ]] || fail "Target non è un repository Git: $TARGET"

  local TARGET_ABS
  TARGET_ABS="$(cd "$TARGET" && pwd)"

  log "------------------------------------------------------------"
  log "Aggiorno target: $TARGET_ABS"
  cd "$TARGET_ABS"

  local CURRENT_STATUS
  CURRENT_STATUS="$(git status --porcelain)"
  if [[ -n "$CURRENT_STATUS" ]]; then
    git status --short
    fail "Il target ha modifiche locali non committate. Commit/stash prima di procedere: $TARGET_ABS"
  fi

  log "Fetch origin"
  run git fetch origin
  log "Checkout $MAIN_BRANCH"
  run git checkout "$MAIN_BRANCH"
  log "Pull origin/$MAIN_BRANCH"
  run git pull origin "$MAIN_BRANCH"
  log "Creo branch $SYNC_BRANCH"
  run git checkout -b "$SYNC_BRANCH"

  local COPIED=0
  for FILE in "${FILES[@]}"; do
    if [[ -f "$SOURCE_ABS/$FILE" ]]; then
      log "Copio $FILE"
      run mkdir -p "$(dirname "$TARGET_ABS/$FILE")"
      run cp "$SOURCE_ABS/$FILE" "$TARGET_ABS/$FILE"
      COPIED=$((COPIED + 1))
    else
      warn "Skip file mancante nel source: $FILE"
    fi
  done

  [[ "$COPIED" -gt 0 ]] || fail "Nessun file copiato per $TARGET_ABS"

  if [[ "$DRY_RUN" == "1" ]]; then
    run git status --short
    log "Dry-run completato per $TARGET_ABS"
    return 0
  fi

  if [[ -z "$(git status --porcelain)" ]]; then
    warn "Nessuna modifica rilevata su $TARGET_ABS. Elimino branch temporaneo e passo oltre."
    git checkout "$MAIN_BRANCH"
    git branch -D "$SYNC_BRANCH"
    return 0
  fi

  log "Aggiungo file modificati"
  git add "${FILES[@]}" 2>/dev/null || true
  log "Commit aggiornamento"
  git commit -m "$COMMIT_MESSAGE"
  log "Merge su $MAIN_BRANCH"
  git checkout "$MAIN_BRANCH"
  git merge --no-ff "$SYNC_BRANCH" -m "Merge Navigation Menu Builder Pro sync"
  log "Push origin/$MAIN_BRANCH"
  git push origin "$MAIN_BRANCH"
  log "Elimino branch locale temporaneo"
  git branch -d "$SYNC_BRANCH" || warn "Branch temporaneo non eliminato automaticamente: $SYNC_BRANCH"

  if [[ "$SKIP_ARTISAN" == "0" && -f "$TARGET_ABS/artisan" ]]; then
    log "Pulizia cache Laravel"
    "$PHP_BIN" artisan view:clear || warn "view:clear non riuscito"
    "$PHP_BIN" artisan optimize:clear || warn "optimize:clear non riuscito"
    "$PHP_BIN" artisan config:clear || warn "config:clear non riuscito"
    "$PHP_BIN" artisan route:clear || warn "route:clear non riuscito"
    "$PHP_BIN" artisan cache:clear || warn "cache:clear non riuscito"
  fi

  ok "Aggiornamento completato per $TARGET_ABS"
}

for TARGET in "${TARGETS[@]}"; do sync_target "$TARGET"; done

ok "Sync completato su tutti i target."
