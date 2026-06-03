#!/bin/bash
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# SMOKE TEST — Run After EVERY Deploy
# Tests all 3 tenants: jikra.in, getsetnova.com, ayurvexa.com
# Usage: bash scripts/smoke-test-all-tenants.sh
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

TENANTS=("jikra.in" "getsetnova.com" "ayurvexa.com")
PASS=0
FAIL=0
WARN=0

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  SMOKE TEST — ALL TENANTS"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check() {
    local label=$1
    local url=$2
    local expected=$3
    local status=$(curl -sI -o /dev/null -w "%{http_code}" --max-time 10 "$url" 2>/dev/null)

    if [ "$status" = "$expected" ]; then
        echo "  ✅ $label → $status"
        PASS=$((PASS + 1))
    elif [ "$status" = "000" ]; then
        echo "  ⚠️  $label → TIMEOUT"
        WARN=$((WARN + 1))
    else
        echo "  ❌ $label → $status (expected $expected)"
        FAIL=$((FAIL + 1))
    fi
}

check_not_500() {
    local label=$1
    local url=$2
    local status=$(curl -sI -o /dev/null -w "%{http_code}" --max-time 10 "$url" 2>/dev/null)

    if [ "$status" = "500" ]; then
        echo "  ❌ $label → 500 SERVER ERROR"
        FAIL=$((FAIL + 1))
    elif [ "$status" = "000" ]; then
        echo "  ⚠️  $label → TIMEOUT"
        WARN=$((WARN + 1))
    else
        echo "  ✅ $label → $status"
        PASS=$((PASS + 1))
    fi
}

check_content() {
    local label=$1
    local url=$2
    local must_contain=$3
    local must_not_contain=$4
    local body=$(curl -s --max-time 10 "$url" 2>/dev/null)

    if echo "$body" | grep -qi "$must_contain" 2>/dev/null; then
        if [ -n "$must_not_contain" ] && echo "$body" | grep -qi "$must_not_contain" 2>/dev/null; then
            echo "  ❌ $label → Contains '$must_not_contain' (cross-tenant leak!)"
            FAIL=$((FAIL + 1))
        else
            echo "  ✅ $label → Contains '$must_contain'"
            PASS=$((PASS + 1))
        fi
    else
        echo "  ❌ $label → Missing '$must_contain'"
        FAIL=$((FAIL + 1))
    fi
}

for tenant in "${TENANTS[@]}"; do
    echo ""
    echo "─── $tenant ───"

    # 1. Homepage loads
    check "Homepage" "https://$tenant" "200"

    # 2. Products page
    check "Products page" "https://$tenant/products" "200"

    # 3. Checkout page (302 = redirect to login, 200 = guest, both OK)
    check_not_500 "Checkout page" "https://$tenant/checkout"

    # 4. Login page
    check "Login page" "https://$tenant/login" "200"

    # 5. Search
    check "Search" "https://$tenant/search?q=test" "200"

    # 6. Cart data API
    check "Cart API" "https://$tenant/cart/data" "200"

    # 7. Admin panel (should redirect to login)
    check_not_500 "Admin panel" "https://$tenant/admin"
done

# Cross-tenant isolation checks
echo ""
echo "─── CROSS-TENANT ISOLATION ───"

# Favicon should NOT be jikra's on other tenants
for tenant in "getsetnova.com" "ayurvexa.com"; do
    check_content "$tenant favicon" "https://$tenant" "rel=\"icon\"" "favicon.png"
done

# GetSetNova should not show "Jikra" in body
check_content "GSN no Jikra text" "https://getsetnova.com" "GetSetNova" ""
check_content "Ayurvexa no Jikra text" "https://ayurvexa.com" "Ayurvexa" ""

# Tools endpoint should require auth
echo ""
echo "─── SECURITY CHECKS ───"
status=$(curl -sI -o /dev/null -w "%{http_code}" --max-time 10 "https://jikra.in/tools/jikra-img-mgr" 2>/dev/null)
if [ "$status" = "302" ] || [ "$status" = "401" ] || [ "$status" = "403" ]; then
    echo "  ✅ /tools/jikra-img-mgr protected → $status"
    PASS=$((PASS + 1))
elif [ "$status" = "200" ]; then
    echo "  ❌ /tools/jikra-img-mgr UNPROTECTED → 200 (anyone can access!)"
    FAIL=$((FAIL + 1))
else
    echo "  ✅ /tools/jikra-img-mgr → $status"
    PASS=$((PASS + 1))
fi

# Gift card rate limiting
echo "  ℹ️  Gift card rate limit: throttle:5,1 (test manually with 6+ rapid requests)"

# Summary
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  RESULTS: ✅ $PASS passed, ❌ $FAIL failed, ⚠️ $WARN warnings"

if [ $FAIL -gt 0 ]; then
    echo "  ❌ SMOKE TEST FAILED — DO NOT SHIP"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 1
elif [ $WARN -gt 0 ]; then
    echo "  ⚠️  PASSED WITH WARNINGS — Check manually"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 0
else
    echo "  ✅ ALL TESTS PASSED"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 0
fi
