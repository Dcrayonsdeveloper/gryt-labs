#!/bin/bash
# Jikra Smoke Test — Run after every deploy
# Usage: bash scripts/smoke-test.sh [domain]
#
# Tests all critical pages return expected HTTP status codes
# Catches: 500 errors, broken templates, missing routes, redirect loops

DOMAIN="${1:-https://jikra.in}"
PASS=0
FAIL=0
WARN=0

check() {
    local url="$1"
    local expected="$2"
    local label="$3"

    status=$(curl -s -o /dev/null -w "%{http_code}" -L --max-time 10 "$url" 2>/dev/null)

    if [ "$status" = "$expected" ]; then
        echo "  ✓ $label ($status)"
        ((PASS++))
    elif [ "$status" = "000" ]; then
        echo "  ✗ $label — TIMEOUT/UNREACHABLE"
        ((FAIL++))
    elif [ "$status" = "500" ] || [ "$status" = "502" ] || [ "$status" = "503" ]; then
        echo "  ✗ $label — SERVER ERROR ($status)"
        ((FAIL++))
    else
        echo "  ⚠ $label — Expected $expected, got $status"
        ((WARN++))
    fi
}

echo ""
echo "  Jikra Smoke Test"
echo "  =================================================="
echo "  Domain: $DOMAIN"
echo "  Time: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

echo "  PUBLIC PAGES:"
check "$DOMAIN/" "200" "Homepage"
check "$DOMAIN/products" "200" "Products listing"
check "$DOMAIN/categories" "200" "Categories"
check "$DOMAIN/cart" "200" "Cart (empty)"
check "$DOMAIN/login" "200" "Login"
check "$DOMAIN/register" "200" "Register"
check "$DOMAIN/contact" "200" "Contact"
check "$DOMAIN/about" "200" "About"
check "$DOMAIN/search?q=fan" "200" "Search"
check "$DOMAIN/blog" "200" "Blog"

echo ""
echo "  PRODUCT PAGES:"
check "$DOMAIN/products/m11-fan" "200" "Product detail (m11-fan)"
check "$DOMAIN/product/m11-fan" "200" "/product/ → /products/ redirect"
check "$DOMAIN/products/nonexistent-product-xyz" "404" "Invalid product → 404"

echo ""
echo "  CATEGORY PAGES:"
check "$DOMAIN/categories/home-kitchen" "200" "Category: Home & Kitchen"
check "$DOMAIN/categories/electronics-gadgets" "200" "Category: Electronics & Gadgets"
check "$DOMAIN/categories/personal-care-beauty" "200" "Category: Personal Care"
check "$DOMAIN/category/home-kitchen" "200" "/category/ → /categories/ redirect"

echo ""
echo "  ERROR PAGES:"
check "$DOMAIN/this-page-does-not-exist-xyz" "404" "404 page"
check "$DOMAIN/checkout" "200" "Checkout (redirects to cart if empty)"

echo ""
echo "  SECURITY:"
STATUS_ENV=$(curl -s -o /dev/null -w "%{http_code}" "$DOMAIN/.env" 2>/dev/null)
if [ "$STATUS_ENV" = "200" ]; then
    echo "  ✗ .env file EXPOSED! ($STATUS_ENV)"
    ((FAIL++))
else
    echo "  ✓ .env blocked ($STATUS_ENV)"
    ((PASS++))
fi

STATUS_LOG=$(curl -s -o /dev/null -w "%{http_code}" "$DOMAIN/storage/logs/laravel.log" 2>/dev/null)
if [ "$STATUS_LOG" = "200" ]; then
    echo "  ✗ Laravel log EXPOSED!"
    ((FAIL++))
else
    echo "  ✓ Logs blocked ($STATUS_LOG)"
    ((PASS++))
fi

echo ""
echo "  ADMIN:"
check "$DOMAIN/admin/login" "200" "Admin login"

echo ""
echo "  =================================================="
echo "  Results: ✓ $PASS passed | ✗ $FAIL failed | ⚠ $WARN warnings"

if [ $FAIL -gt 0 ]; then
    echo "  STATUS: FAILED — Fix errors before going live!"
    exit 1
else
    echo "  STATUS: PASSED"
    exit 0
fi
