#!/usr/bin/env bash
set -euo pipefail

# Sync Editor V5 footer/settings/chatbot work from cms_r4software_demo
# to other local CMS distributions.
#
# Run from the source project root:
#   cd /Users/manuelericci/Sites/cms_r4software_demo
#   bash scripts/sync-editor-v5-footer-chatbot-to-distributions.sh
#
# Optional:
#   SITES_ROOT=/Users/manuelericci/Sites bash scripts/sync-editor-v5-footer-chatbot-to-distributions.sh
#   TARGETS="cms_crewcorepro cms_garcone cms_memoriamica cms_r4software" bash scripts/sync-editor-v5-footer-chatbot-to-distributions.sh
#   DRY_RUN=1 bash scripts/sync-editor-v5-footer-chatbot-to-distributions.sh

SOURCE_ROOT="$(pwd)"
SITES_ROOT="${SITES_ROOT:-/Users/manuelericci/Sites}"
TARGETS="${TARGETS:-cms_crewcorepro cms_garcone cms_memoriamica cms_r4software}"
BRANCH_NAME="${BRANCH_NAME:-feature/sync-editor-v5-footer-chatbot}"
DRY_RUN="${DRY_RUN:-0}"
AUTO_COMMIT="${AUTO_COMMIT:-1}"

log() { printf '\033[1;34m[SYNC]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[WARN]\033[0m %s\n' "$*"; }
err() { printf '\033[1;31m[ERR ]\033[0m %s\n' "$*"; }

run() {
  if [[ "$DRY_RUN" == "1" ]]; then
    printf 'DRY_RUN: %q ' "$@"; printf '\n'
  else
    "$@"
  fi
}

need_file() {
  local file="$1"
  if [[ ! -e "$SOURCE_ROOT/$file" ]]; then
    err "File sorgente mancante: $file"
    exit 1
  fi
}

copy_file() {
  local rel="$1"
  local target="$2"
  need_file "$rel"
  run mkdir -p "$(dirname "$target/$rel")"
  run cp "$SOURCE_ROOT/$rel" "$target/$rel"
  log "Copiato: $rel"
}

inject_before_marker() {
  local file="$1"
  local marker="$2"
  local line="$3"

  if [[ ! -f "$file" ]]; then
    warn "File non trovato per injection: $file"
    return 0
  fi

  if grep -Fq "$line" "$file"; then
    return 0
  fi

  if grep -Fq "$marker" "$file"; then
    if [[ "$DRY_RUN" == "1" ]]; then
      echo "DRY_RUN: inserire '$line' prima di '$marker' in $file"
    else
      LINE="$line" MARKER="$marker" python3 - "$file" <<'PY'
import os, sys
path = sys.argv[1]
line = os.environ['LINE']
marker = os.environ['MARKER']
text = open(path, 'r', encoding='utf-8').read()
if line in text:
    raise SystemExit(0)
idx = text.find(marker)
if idx >= 0:
    text = text[:idx] + line + "\n" + text[idx:]
else:
    text += "\n" + line + "\n"
open(path, 'w', encoding='utf-8').write(text)
PY
    fi
  else
    if [[ "$DRY_RUN" == "1" ]]; then
      echo "DRY_RUN: appendere '$line' in $file"
    else
      printf '\n%s\n' "$line" >> "$file"
    fi
  fi
}

create_routes_file() {
  local target="$1"
  local path="$target/routes/r4-footer-chatbot.php"
  run mkdir -p "$(dirname "$path")"

  if [[ "$DRY_RUN" == "1" ]]; then
    echo "DRY_RUN: creare $path"
    return 0
  fi

  cat > "$path" <<'PHP'
<?php

use App\Http\Controllers\Admin\ChatbotSettingsController;
use App\Http\Controllers\Admin\FooterBrandSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active', 'role:admin,superadmin'])
    ->prefix('admin/settings')
    ->as('admin.settings.')
    ->group(function () {
        Route::middleware('perm:settings.view')
            ->get('/footer-brand', [FooterBrandSettingsController::class, 'edit'])
            ->name('footer-brand.edit');

        Route::middleware('perm:settings.manage')
            ->put('/footer-brand', [FooterBrandSettingsController::class, 'update'])
            ->name('footer-brand.update');

        Route::middleware('perm:settings.view')
            ->get('/chatbot/status', [ChatbotSettingsController::class, 'status'])
            ->name('chatbot.status');

        Route::middleware('perm:settings.manage')
            ->put('/chatbot', [ChatbotSettingsController::class, 'update'])
            ->name('chatbot.update');
    });
PHP
}

create_settings_enhancer() {
  local target="$1"
  local path="$target/public/assets/admin/r4-settings-footer-chatbot.js"
  run mkdir -p "$(dirname "$path")"

  if [[ "$DRY_RUN" == "1" ]]; then
    echo "DRY_RUN: creare $path"
    return 0
  fi

  cat > "$path" <<'JS'
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function isSettingsIndex() {
        return window.location.pathname === '/admin/settings' || window.location.pathname === '/admin/settings/';
    }

    function isFooterBrandPage() {
        return window.location.pathname === '/admin/settings/footer-brand' || window.location.pathname === '/admin/settings/footer-brand/';
    }

    function isSettingsArea() {
        return isSettingsIndex() || isFooterBrandPage();
    }

    function currentTab() {
        return new URLSearchParams(window.location.search).get('tab') || 'branding';
    }

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function tabs() {
        return document.querySelector('.nav.nav-tabs');
    }

    function injectModernStyle() {
        if (!isSettingsArea() || document.getElementById('r4-settings-modern-style')) return;
        var style = document.createElement('style');
        style.id = 'r4-settings-modern-style';
        style.textContent = `
            body.r4-settings-modern{background:radial-gradient(circle at top left,rgba(13,110,253,.08),transparent 34rem),linear-gradient(180deg,#f8fafc 0%,#eef2f7 100%)!important;}
            body.r4-settings-modern main.col.p-4{padding:1.35rem!important;}
            body.r4-settings-modern .page-topbar{position:relative!important;top:auto!important;z-index:1!important;margin:0 0 1rem!important;border:0!important;background:transparent!important;backdrop-filter:none!important;}
            body.r4-settings-modern .page-topbar .container-fluid{padding:0!important;}
            body.r4-settings-modern .page-topbar .d-flex.align-items-center.justify-content-between{padding:1.15rem 1.25rem;border:1px solid rgba(148,163,184,.26);border-radius:1.25rem;background:rgba(255,255,255,.9);box-shadow:0 18px 48px rgba(15,23,42,.07);}
            body.r4-settings-modern .page-topbar h1,body.r4-settings-modern .page-topbar .h4{font-size:1.35rem;letter-spacing:-.025em;font-weight:850;color:#0f172a;}
            body.r4-settings-modern .page-topbar i.text-primary{width:2.25rem;height:2.25rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.9rem;background:rgba(13,110,253,.10);}
            body.r4-settings-modern .nav.nav-tabs{gap:.45rem;padding:.55rem;margin-bottom:1rem!important;border:1px solid rgba(148,163,184,.25)!important;border-radius:1.25rem;background:rgba(255,255,255,.82);box-shadow:0 16px 44px rgba(15,23,42,.06);overflow-x:auto;flex-wrap:nowrap;scrollbar-width:thin;}
            body.r4-settings-modern .nav.nav-tabs .nav-item{flex:0 0 auto;}
            body.r4-settings-modern .nav.nav-tabs .nav-link{border:0!important;border-radius:999px!important;padding:.62rem .95rem;color:#475569;font-size:.92rem;font-weight:750;background:transparent;white-space:nowrap;transition:background .16s ease,color .16s ease,box-shadow .16s ease,transform .16s ease;}
            body.r4-settings-modern .nav.nav-tabs .nav-link i{color:#0d6efd;opacity:.9;}
            body.r4-settings-modern .nav.nav-tabs .nav-link:hover{background:#eef5ff;color:#0f172a;transform:translateY(-1px);}
            body.r4-settings-modern .nav.nav-tabs .nav-link.active{background:linear-gradient(135deg,#0d6efd 0%,#2563eb 100%)!important;color:#fff!important;box-shadow:0 14px 28px rgba(13,110,253,.24);}
            body.r4-settings-modern .nav.nav-tabs .nav-link.active i{color:#fff;opacity:1;}
            body.r4-settings-modern .card-soft,body.r4-settings-modern .card,.r4-card{border:1px solid rgba(148,163,184,.24)!important;border-radius:1.25rem!important;background:rgba(255,255,255,.94)!important;box-shadow:0 18px 54px rgba(15,23,42,.075)!important;}
            body.r4-settings-modern .card-soft.p-3,body.r4-settings-modern .card.p-3{padding:1.15rem!important;}
            body.r4-settings-modern .form-label{font-size:.86rem;font-weight:760;color:#172033;margin-bottom:.35rem;}
            body.r4-settings-modern .form-control,body.r4-settings-modern .form-select,body.r4-settings-modern .input-group-text{border-color:#dbe3ee;border-radius:.8rem;min-height:2.55rem;}
            body.r4-settings-modern .form-control:focus,body.r4-settings-modern .form-select:focus{border-color:#0d6efd;box-shadow:0 0 0 .22rem rgba(13,110,253,.12);}
            body.r4-settings-modern .btn{border-radius:.82rem;font-weight:720;}
            body.r4-settings-modern .btn-primary{background:linear-gradient(135deg,#0d6efd 0%,#2563eb 100%);border-color:#0d6efd;box-shadow:0 12px 24px rgba(13,110,253,.18);}
            body.r4-settings-modern .alert-info{border-color:#b8edf8;background:linear-gradient(135deg,#ecfeff 0%,#cffafe 100%);color:#0f5563;border-radius:1rem;}
        `;
        document.head.appendChild(style);
        document.body.classList.add('r4-settings-modern');
    }

    function addSettingsLink(href, key, icon, label, beforePattern) {
        var nav = tabs();
        if (!nav || nav.querySelector('[data-r4-settings-link="' + key + '"]')) return;
        var active = window.location.pathname === href || (key === 'chatbot' && currentTab() === 'chatbot');
        var li = document.createElement('li');
        li.className = 'nav-item';
        li.innerHTML = '<a class="nav-link ' + (active ? 'active' : '') + '" data-r4-settings-link="' + key + '" href="' + href + '"><i class="bi ' + icon + '"></i> ' + label + '</a>';
        var before = Array.prototype.slice.call(nav.querySelectorAll('a')).find(function (a) {
            return beforePattern && beforePattern.test(a.getAttribute('href') || '');
        });
        if (before && before.parentElement) nav.insertBefore(li, before.parentElement);
        else nav.appendChild(li);
    }

    function addExtraTabs() {
        if (!isSettingsIndex()) return;
        addSettingsLink('?tab=chatbot', 'chatbot', 'bi-chat-dots', 'ChatBot', /tab=calendar/);
        addSettingsLink('/admin/settings/footer-brand', 'footer-brand', 'bi-window-dock', 'Footer Brand', /tab=calendar/);
    }

    function hideOtherPanels() {
        if (currentTab() !== 'chatbot') return;
        var nav = tabs();
        if (!nav) return;
        var node = nav.nextElementSibling;
        while (node) {
            if (node.id !== 'r4ChatbotSettingsPanel') node.style.display = 'none';
            node = node.nextElementSibling;
        }
    }

    function renderChatbotPanel(enabled) {
        var nav = tabs();
        if (!nav || document.getElementById('r4ChatbotSettingsPanel')) return;
        var panel = document.createElement('form');
        panel.id = 'r4ChatbotSettingsPanel';
        panel.method = 'POST';
        panel.action = '/admin/settings/chatbot';
        panel.className = 'card card-soft p-3';
        panel.innerHTML = '' +
            '<input type="hidden" name="_token" value="' + csrf() + '">' +
            '<input type="hidden" name="_method" value="PUT">' +
            '<div class="row g-3 align-items-start">' +
                '<div class="col-md-6"><div class="form-check form-switch">' +
                    '<input type="hidden" name="chatbot_enabled" value="0">' +
                    '<input class="form-check-input" type="checkbox" role="switch" id="chatbot_enabled" name="chatbot_enabled" value="1" ' + (enabled ? 'checked' : '') + '>' +
                    '<label class="form-check-label fw-semibold" for="chatbot_enabled">Mostra ChatBot sul sito pubblico</label>' +
                '</div><div class="form-hint mt-1">Se disattivato, il widget ChatBot non verrà caricato nelle pagine pubbliche del sito.</div></div>' +
                '<div class="col-md-6"><div class="alert alert-info mb-0"><div class="fw-semibold mb-1">Stato attuale</div>' +
                    (enabled ? '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i> ChatBot visibile</span>' : '<span class="badge text-bg-secondary"><i class="bi bi-eye-slash me-1"></i> ChatBot nascosto</span>') +
                    '<div class="small mt-2">Questa impostazione agisce solo sul frontend pubblico.</div></div></div>' +
            '</div><div class="mt-3 d-flex justify-content-end gap-2">' +
                '<a href="?tab=chatbot" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Annulla</a>' +
                '<button class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Salva impostazioni ChatBot</button>' +
            '</div>';
        nav.insertAdjacentElement('afterend', panel);
    }

    function loadChatbotPanel() {
        if (!isSettingsIndex() || currentTab() !== 'chatbot') return;
        hideOtherPanels();
        fetch('/admin/settings/chatbot/status', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : { enabled: false }; })
            .then(function (json) { renderChatbotPanel(!!json.enabled); })
            .catch(function () { renderChatbotPanel(false); });
    }

    ready(function () {
        if (!isSettingsArea()) return;
        injectModernStyle();
        addExtraTabs();
        loadChatbotPanel();
    });
})();
JS
}

inject_admin_layout_asset() {
  local target="$1"
  local file="$target/resources/views/admin/layout.blade.php"
  local line='<script src="{{ asset('\''assets/admin/r4-settings-footer-chatbot.js'\'') }}?v={{ time() }}" defer></script>'

  if [[ ! -f "$file" ]]; then
    warn "admin/layout.blade.php non trovato: salto injection asset settings"
    return 0
  fi

  if grep -Fq "r4-settings-footer-chatbot.js" "$file"; then
    log "Asset settings già collegato in admin/layout.blade.php"
    return 0
  fi

  if grep -Fq "</body>" "$file"; then
    if [[ "$DRY_RUN" == "1" ]]; then
      echo "DRY_RUN: inserire asset settings prima di </body> in $file"
    else
      ASSET_LINE="$line" python3 - "$file" <<'PY'
import os, sys
path = sys.argv[1]
line = os.environ['ASSET_LINE']
text = open(path, 'r', encoding='utf-8').read()
if 'r4-settings-footer-chatbot.js' not in text:
    text = text.replace('</body>', '    ' + line + '\n</body>')
open(path, 'w', encoding='utf-8').write(text)
PY
    fi
  else
    warn "</body> non trovato in admin/layout.blade.php: aggiungi manualmente $line"
  fi
}

copy_payload() {
  local target="$1"

  # Controllers and views
  copy_file "app/Http/Controllers/Admin/ChatbotSettingsController.php" "$target"
  copy_file "app/Http/Controllers/Admin/FooterBrandSettingsController.php" "$target"
  copy_file "resources/views/admin/settings/footer-brand.blade.php" "$target"
  copy_file "resources/views/partials/footer.blade.php" "$target"
  copy_file "resources/views/vendor/crm/public/chatbot-widget.blade.php" "$target"

  # Editor V5 assets
  copy_file "public/assets/admin/visual-editor-v5/panels/footer-builder.js" "$target"
  copy_file "public/assets/admin/visual-editor-v5/ui/insert-slots.js" "$target"
  copy_file "public/assets/admin/visual-editor-v5/ui/left-sidebar.js" "$target"
  copy_file "public/assets/admin/visual-editor-v5/widgets/base.js" "$target"
  copy_file "public/assets/admin/visual-editor-v5/widgets/layout.js" "$target"

  # Documentation, if docs folder exists or can be created
  copy_file "docs/editor-v5-ai-html-css-js-prompt.md" "$target"
  copy_file "docs/editor-v5-public-animations-fix-note-2026-05-06.md" "$target"

  # Routes and settings enhancer
  create_routes_file "$target"
  create_settings_enhancer "$target"
  inject_before_marker "$target/routes/web.php" "require __DIR__ . '/auth.php';" "require __DIR__ . '/r4-footer-chatbot.php';"
  inject_admin_layout_asset "$target"
}

check_laravel() {
  local target="$1"
  log "Pulizia cache Laravel in $(basename "$target")"
  (
    cd "$target"
    [[ "$DRY_RUN" == "1" ]] && exit 0
    php artisan optimize:clear || true
    php artisan view:clear || true
    php artisan route:clear || true
  )
}

process_target() {
  local name="$1"
  local target="$SITES_ROOT/$name"

  if [[ ! -d "$target/.git" ]]; then
    warn "Distribuzione non trovata o non è una repo Git: $target"
    return 0
  fi

  log "========================================"
  log "Distribuzione: $name"
  log "Path: $target"

  (
    cd "$target"
    if [[ -n "$(git status --porcelain)" ]]; then
      warn "$name ha modifiche locali non committate. Salto per sicurezza."
      git status --short
      exit 20
    fi

    run git fetch origin
    run git checkout main
    run git pull origin main

    if git rev-parse --verify "$BRANCH_NAME" >/dev/null 2>&1; then
      run git checkout "$BRANCH_NAME"
    else
      run git checkout -b "$BRANCH_NAME"
    fi
  ) || {
    local code=$?
    if [[ "$code" == "20" ]]; then return 0; fi
    return "$code"
  }

  copy_payload "$target"
  check_laravel "$target"

  (
    cd "$target"
    if [[ "$DRY_RUN" == "1" ]]; then
      git status --short
      exit 0
    fi

    git add \
      app/Http/Controllers/Admin/ChatbotSettingsController.php \
      app/Http/Controllers/Admin/FooterBrandSettingsController.php \
      resources/views/admin/settings/footer-brand.blade.php \
      resources/views/partials/footer.blade.php \
      resources/views/vendor/crm/public/chatbot-widget.blade.php \
      public/assets/admin/r4-settings-footer-chatbot.js \
      public/assets/admin/visual-editor-v5/panels/footer-builder.js \
      public/assets/admin/visual-editor-v5/ui/insert-slots.js \
      public/assets/admin/visual-editor-v5/ui/left-sidebar.js \
      public/assets/admin/visual-editor-v5/widgets/base.js \
      public/assets/admin/visual-editor-v5/widgets/layout.js \
      docs/editor-v5-ai-html-css-js-prompt.md \
      docs/editor-v5-public-animations-fix-note-2026-05-06.md \
      routes/r4-footer-chatbot.php \
      routes/web.php \
      resources/views/admin/layout.blade.php

    if git diff --cached --quiet; then
      log "$name: nessuna modifica da committare"
    elif [[ "$AUTO_COMMIT" == "1" ]]; then
      git commit -m "Sync Editor V5 footer brand and ChatBot settings"
      log "$name: commit creato su $BRANCH_NAME"
    else
      git status --short
      warn "$name: AUTO_COMMIT=0, commit non creato"
    fi
  )
}

main() {
  log "Source: $SOURCE_ROOT"
  log "Sites root: $SITES_ROOT"
  log "Targets: $TARGETS"
  log "Branch: $BRANCH_NAME"
  log "Dry run: $DRY_RUN"

  for rel in \
    "app/Http/Controllers/Admin/ChatbotSettingsController.php" \
    "app/Http/Controllers/Admin/FooterBrandSettingsController.php" \
    "resources/views/admin/settings/footer-brand.blade.php" \
    "resources/views/partials/footer.blade.php" \
    "resources/views/vendor/crm/public/chatbot-widget.blade.php" \
    "public/assets/admin/visual-editor-v5/panels/footer-builder.js" \
    "public/assets/admin/visual-editor-v5/ui/insert-slots.js" \
    "public/assets/admin/visual-editor-v5/ui/left-sidebar.js" \
    "public/assets/admin/visual-editor-v5/widgets/base.js" \
    "public/assets/admin/visual-editor-v5/widgets/layout.js" \
    "docs/editor-v5-ai-html-css-js-prompt.md" \
    "docs/editor-v5-public-animations-fix-note-2026-05-06.md"; do
    need_file "$rel"
  done

  for target in $TARGETS; do
    process_target "$target"
  done

  log "Completato. Controlla ogni repo, testa in locale e poi fai push del branch."
  log "Push esempio: cd $SITES_ROOT/cms_crewcorepro && git push -u origin $BRANCH_NAME"
}

main "$@"
