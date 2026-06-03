#!/bin/bash
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# LOCAL PRE-DEPLOY VALIDATION
# Run this BEFORE any SSH/SCP. Never upload broken code.
# Usage: bash scripts/pre-deploy-local.sh
# Exits with non-zero code if ANY check fails.
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

set -e
cd "$(dirname "$0")/.."

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  LOCAL PRE-DEPLOY VALIDATION"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

ERRORS=0
WARNINGS=0

fail()  { echo "  ❌ $1"; ERRORS=$((ERRORS + 1)); }
warn()  { echo "  ⚠️  $1"; WARNINGS=$((WARNINGS + 1)); }
ok()    { echo "  ✅ $1"; }

# ─────────────────────────────────────────────
# Step 1: Git status — no uncommitted work
# ─────────────────────────────────────────────
echo ""
echo "─── [1/8] Git State ───"
if ! git diff --quiet; then
  warn "Uncommitted changes present — commit or stash first"
  git diff --stat | head -10
else
  ok "Working tree clean"
fi

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" = "main" ]; then
  ok "On main branch"
else
  warn "Not on main branch (on: $CURRENT_BRANCH) — make sure this is intentional"
fi

# ─────────────────────────────────────────────
# Step 2: PHP syntax on all changed files
# ─────────────────────────────────────────────
echo ""
echo "─── [2/8] PHP Syntax ───"
CHANGED_PHP=$(git diff --name-only HEAD~1 HEAD -- "*.php" 2>/dev/null || find app/ routes/ config/ -name "*.php" -newer vendor/ 2>/dev/null | head -50)
if [ -n "$CHANGED_PHP" ]; then
  for f in $CHANGED_PHP; do
    if [ -f "$f" ]; then
      if php -l "$f" > /dev/null 2>&1; then
        : # silent success
      else
        fail "Syntax error in $f"
        php -l "$f"
      fi
    fi
  done
  if [ $ERRORS -eq 0 ]; then
    ok "All changed PHP files have valid syntax ($(echo $CHANGED_PHP | wc -w) files)"
  fi
else
  ok "No PHP files changed"
fi

# ─────────────────────────────────────────────
# Step 3: Blade view compilation
# ─────────────────────────────────────────────
echo ""
echo "─── [3/8] Blade Compilation ───"
if php artisan view:clear > /dev/null 2>&1 && php artisan view:cache > /dev/null 2>&1; then
  ok "All Blade views compile"
  php artisan view:clear > /dev/null 2>&1
else
  fail "Blade compilation failed"
  php artisan view:cache 2>&1 | head -10
fi

# ─────────────────────────────────────────────
# Step 4: Route validation
# ─────────────────────────────────────────────
echo ""
echo "─── [4/8] Routes ───"
if php artisan route:list > /tmp/routes_check.txt 2>&1; then
  ROUTE_COUNT=$(wc -l < /tmp/routes_check.txt)
  ok "$ROUTE_COUNT route lines — routes compile"
else
  fail "Route compilation error"
  tail -10 /tmp/routes_check.txt
fi
rm -f /tmp/routes_check.txt

# ─────────────────────────────────────────────
# Step 5: Config validation
# ─────────────────────────────────────────────
echo ""
echo "─── [5/8] Config ───"
if php artisan config:clear > /dev/null 2>&1 && php artisan config:cache > /dev/null 2>&1; then
  ok "Config compiles"
  php artisan config:clear > /dev/null 2>&1
else
  fail "Config compilation failed"
fi

# ─────────────────────────────────────────────
# Step 6: Tenant-specific checks
# ─────────────────────────────────────────────
echo ""
echo "─── [6/8] Multi-Tenant Safety ───"

# No hardcoded Jikra defaults in Setting::get()
HARDCODED=$(grep -rn "Setting::get('[a-z_]*',\s*'[^']*[Jj]ikra" app/ resources/ frontends/ 2>/dev/null | grep -v "/test\|/backup\|outofgit" || true)
if [ -n "$HARDCODED" ]; then
  fail "Jikra-hardcoded Setting defaults found:"
  echo "$HARDCODED" | head -5
else
  ok "No tenant-specific hardcoded defaults"
fi

# No hardcoded pixel IDs in blade
PIXEL_HARDCODE=$(grep -rn "fbq(.init.,\s*.[0-9]" resources/views/ frontends/*/views/ 2>/dev/null | grep -v "{{ \$" || true)
if [ -n "$PIXEL_HARDCODE" ]; then
  warn "Hardcoded FB pixel IDs found in views:"
  echo "$PIXEL_HARDCODE" | head -3
else
  ok "All pixel inits are dynamic"
fi

# ─────────────────────────────────────────────
# Step 7: Blade directive balance
# ─────────────────────────────────────────────
echo ""
echo "─── [7/8] Blade Directive Balance (changed files only) ───"
# Only check blade files changed in current branch vs main
# (CI runs full-tree check on PRs — legacy files are tracked separately)
CHANGED_BLADE=""
if git rev-parse main >/dev/null 2>&1; then
  CHANGED_BLADE=$(git diff --name-only main...HEAD -- "*.blade.php" 2>/dev/null | grep -v '^$' || true)
fi
# Also include unstaged/staged changes
STAGED_BLADE=$(git diff --name-only HEAD -- "*.blade.php" 2>/dev/null | grep -v '^$' || true)
ALL_BLADE=$(printf '%s\n%s\n' "$CHANGED_BLADE" "$STAGED_BLADE" | sort -u | grep -v '^$' || true)

UNBALANCED=0
CHECKED=0
if [ -n "$ALL_BLADE" ]; then
  while IFS= read -r f; do
    [ -z "$f" ] && continue
    [ ! -f "$f" ] && continue
    # @empty\s*\( matches standalone @empty($var); bare @empty inside @forelse is excluded
    IF_COUNT=$(grep -cE "@if\b|@hasSection\b|@unless\b|@isset\b|@empty\s*\(|@auth\b|@guest\b|@can\b|@cannot\b|@canany\b|@error\b|@sectionMissing\b" "$f" 2>/dev/null || echo 0)
    ENDIF_COUNT=$(grep -cE "@endif|@endunless|@endisset|@endempty|@endauth|@endguest|@endcan|@endcannot|@endcanany|@enderror" "$f" 2>/dev/null || echo 0)
    CHECKED=$((CHECKED + 1))
    if [ "$IF_COUNT" != "$ENDIF_COUNT" ]; then
      fail "$f: @if/$IF_COUNT vs @endif/$ENDIF_COUNT"
      UNBALANCED=$((UNBALANCED + 1))
    fi
  done <<< "$ALL_BLADE"
  if [ $UNBALANCED -eq 0 ]; then
    ok "Changed Blade files ($CHECKED) all balanced"
  fi
else
  ok "No Blade files changed in this branch"
fi

# ─────────────────────────────────────────────
# Step 8: Secrets / .env safety
# ─────────────────────────────────────────────
echo ""
echo "─── [8/8] Secret Safety ───"
if git log --all --diff-filter=A --name-only 2>/dev/null | grep -q "^\.env$"; then
  fail ".env was committed at some point — rotate secrets!"
else
  ok "No .env in commit history"
fi

SECRETS=$(grep -rnE "(api_key|api_secret|access_token|bearer_token)\s*=\s*['\"][A-Za-z0-9]{30,}" app/ config/ 2>/dev/null | grep -v "Setting::get\|env(\|config(" || true)
if [ -n "$SECRETS" ]; then
  fail "Possible hardcoded secrets:"
  echo "$SECRETS" | head -3
else
  ok "No hardcoded secrets in code"
fi

# ─────────────────────────────────────────────
# Summary
# ─────────────────────────────────────────────
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -gt 0 ]; then
  echo "  ❌ VALIDATION FAILED — $ERRORS error(s), $WARNINGS warning(s)"
  echo "  DO NOT deploy. Fix errors first."
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  exit 1
elif [ $WARNINGS -gt 0 ]; then
  echo "  ⚠️  VALIDATION PASSED with $WARNINGS warning(s)"
  echo "  Review warnings, then proceed with caution."
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  exit 0
else
  echo "  ✅ ALL CHECKS PASSED — safe to deploy"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  exit 0
fi
