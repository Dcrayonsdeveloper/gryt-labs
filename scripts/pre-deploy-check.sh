#!/bin/bash
# Pre-Deploy Validation Script
# Run on production AFTER uploading files, BEFORE clearing cache
# Usage: bash scripts/pre-deploy-check.sh

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  PRE-DEPLOY VALIDATION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

cd /var/www/jikra || { echo "ERROR: /var/www/jikra not found"; exit 1; }
ERRORS=0
WARNINGS=0

# 1. PHP syntax check
echo "[1/7] PHP syntax check..."
SYNTAX_ERRORS=0
for f in $(find app/ -name "*.php" -newer storage/framework/views/.gitkeep 2>/dev/null | head -50); do
    if ! php -l "$f" > /dev/null 2>&1; then
        echo "  ❌ SYNTAX ERROR: $f"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
    fi
done
if [ $SYNTAX_ERRORS -eq 0 ]; then echo "  ✅ All PHP files valid"; else ERRORS=$((ERRORS + SYNTAX_ERRORS)); fi

# 2. Blade compilation
echo "[2/7] Blade view compilation..."
OUTPUT=$(php artisan view:cache 2>&1)
if [ $? -ne 0 ]; then
    echo "  ❌ BLADE COMPILATION FAILED"
    echo "  $OUTPUT" | head -5
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ All Blade views compile"
fi

# 3. Route validation
echo "[3/7] Route validation..."
OUTPUT=$(php artisan route:list --compact 2>&1)
if [ $? -ne 0 ]; then
    echo "  ❌ ROUTE COMPILATION FAILED"
    echo "  $OUTPUT" | head -5
    ERRORS=$((ERRORS + 1))
else
    ROUTE_COUNT=$(echo "$OUTPUT" | wc -l)
    echo "  ✅ $ROUTE_COUNT routes registered"
fi

# 4. Config compilation
echo "[4/7] Config validation..."
OUTPUT=$(php artisan config:cache 2>&1)
if [ $? -ne 0 ]; then
    echo "  ❌ CONFIG COMPILATION FAILED"
    echo "  $OUTPUT" | head -5
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ Config compiles"
fi

# 5. Homepage check (all tenants)
echo "[5/7] Homepage availability..."
for url in "https://jikra.in" "https://getsetnova.com" "https://ayurvexa.com"; do
    STATUS=$(curl -sI -o /dev/null -w "%{http_code}" --max-time 10 "$url" 2>/dev/null)
    if [ "$STATUS" = "200" ]; then
        echo "  ✅ $url → $STATUS"
    elif [ "$STATUS" = "000" ]; then
        echo "  ⚠️  $url → timeout (might be cache, check manually)"
        WARNINGS=$((WARNINGS + 1))
    else
        echo "  ❌ $url → $STATUS"
        ERRORS=$((ERRORS + 1))
    fi
done

# 6. Checkout page check (critical revenue path)
echo "[6/7] Checkout page (revenue-critical)..."
for url in "https://jikra.in/checkout" "https://getsetnova.com/checkout" "https://ayurvexa.com/checkout"; do
    STATUS=$(curl -sI -o /dev/null -w "%{http_code}" --max-time 10 "$url" 2>/dev/null)
    if [ "$STATUS" = "500" ]; then
        echo "  ❌ $url → 500 CHECKOUT BROKEN!"
        ERRORS=$((ERRORS + 1))
    elif [ "$STATUS" = "302" ] || [ "$STATUS" = "200" ]; then
        echo "  ✅ $url → $STATUS (OK)"
    else
        echo "  ⚠️  $url → $STATUS"
        WARNINGS=$((WARNINGS + 1))
    fi
done

# 7. Recent errors in log
echo "[7/7] Recent errors in log..."
RECENT_ERRORS=$(grep -c "ERROR\|CRITICAL\|Exception" storage/logs/laravel.log 2>/dev/null | tail -1)
LAST_5_MIN=$(find storage/logs/laravel.log -mmin -5 2>/dev/null)
if [ -n "$LAST_5_MIN" ]; then
    NEW_ERRORS=$(tail -100 storage/logs/laravel.log | grep -c "ERROR\|CRITICAL\|Exception" 2>/dev/null)
    if [ "$NEW_ERRORS" -gt 0 ]; then
        echo "  ⚠️  $NEW_ERRORS errors in recent log entries"
        tail -5 storage/logs/laravel.log | grep "ERROR\|Exception" | head -3
        WARNINGS=$((WARNINGS + 1))
    else
        echo "  ✅ No recent errors"
    fi
else
    echo "  ✅ Log not recently modified"
fi

# Summary
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -gt 0 ]; then
    echo "  ❌ DEPLOY BLOCKED — $ERRORS error(s), $WARNINGS warning(s)"
    echo "  Fix errors before clearing cache!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 1
elif [ $WARNINGS -gt 0 ]; then
    echo "  ⚠️  DEPLOY OK with $WARNINGS warning(s)"
    echo "  Review warnings, then clear cache."
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 0
else
    echo "  ✅ ALL CHECKS PASSED — Safe to deploy"
    echo "  Run: php artisan cache:clear"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 0
fi
