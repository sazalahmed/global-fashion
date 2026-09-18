#!/usr/bin/env bash

# ================================================================
# LARAVEL AUTOMATED TESTING GATE RUNNER
# Equivalent to the iOS claude-mobile-ios-testing skill
# ================================================================
# Usage: bash run-gates.sh [--gate=1|2|3|4|all] [--no-dusk]
# ================================================================

set -euo pipefail

# ── Colors ──────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# ── Config ───────────────────────────────────────────────────────
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
LOG_DIR="storage/logs/test-gates"
LOG_FILE="${LOG_DIR}/gate-run-${TIMESTAMP}.log"
SCREENSHOT_DIR="tests/Browser/screenshots"
SKIP_DUSK=false
RUN_GATE="all"

# ── Parse arguments ───────────────────────────────────────────────
for arg in "$@"; do
    case $arg in
        --no-dusk)    SKIP_DUSK=true ;;
        --gate=*)     RUN_GATE="${arg#*=}" ;;
        --help)
            echo "Usage: bash run-gates.sh [--gate=1|2|3|4|all] [--no-dusk]"
            exit 0
            ;;
    esac
done

# ── Helpers ───────────────────────────────────────────────────────
mkdir -p "$LOG_DIR" "$SCREENSHOT_DIR"

PASS_COUNT=0
FAIL_COUNT=0
GATE_RESULTS=()

announce() {
    echo ""
    echo -e "${CYAN}${BOLD}═══════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}${BOLD}  $1${NC}"
    echo -e "${CYAN}${BOLD}═══════════════════════════════════════════════════${NC}"
}

run_gate() {
    local gate_num="$1"
    local gate_name="$2"
    local command="$3"

    echo ""
    echo -e "${BLUE}${BOLD}▶ Running GATE ${gate_num}: ${gate_name}${NC}"
    echo "  Command: ${command}"
    echo ""

    if eval "$command" 2>&1 | tee -a "$LOG_FILE"; then
        echo -e "${GREEN}${BOLD}✅ GATE ${gate_num} PASSED: ${gate_name}${NC}"
        GATE_RESULTS+=("✅ GATE ${gate_num} - ${gate_name}: PASS")
        ((PASS_COUNT++))
        return 0
    else
        echo -e "${RED}${BOLD}❌ GATE ${gate_num} FAILED: ${gate_name}${NC}"
        GATE_RESULTS+=("❌ GATE ${gate_num} - ${gate_name}: FAIL")
        ((FAIL_COUNT++))
        return 1
    fi
}

check_requirements() {
    announce "PRE-FLIGHT CHECKS"

    # Check PHP
    if ! command -v php &>/dev/null; then
        echo -e "${RED}❌ PHP not found. Please install PHP.${NC}"; exit 1
    fi
    echo -e "${GREEN}✅ PHP: $(php -r 'echo PHP_VERSION;')${NC}"

    # Check Composer
    if ! command -v composer &>/dev/null; then
        echo -e "${RED}❌ Composer not found.${NC}"; exit 1
    fi
    echo -e "${GREEN}✅ Composer found${NC}"

    # Check artisan
    if [ ! -f "artisan" ]; then
        echo -e "${RED}❌ Not in a Laravel project directory.${NC}"; exit 1
    fi
    echo -e "${GREEN}✅ Laravel artisan found${NC}"

    # Check .env.testing
    if [ ! -f ".env.testing" ]; then
        echo -e "${YELLOW}⚠️  .env.testing not found. Copying from .env.example...${NC}"
        cp .env.example .env.testing
        echo "APP_ENV=testing" >> .env.testing
        echo "DB_DATABASE=laravel_testing" >> .env.testing
    fi
    echo -e "${GREEN}✅ .env.testing exists${NC}"

    # Check Dusk ChromeDriver
    if [ "$SKIP_DUSK" = false ] && ! command -v chromedriver &>/dev/null; then
        if [ ! -f "vendor/laravel/dusk/bin/chromedriver-linux" ]; then
            echo -e "${YELLOW}⚠️  ChromeDriver not found. Dusk tests may fail.${NC}"
            echo -e "${YELLOW}   Run: php artisan dusk:chrome-driver --detect${NC}"
        fi
    fi
}

prepare_environment() {
    announce "PREPARING TEST ENVIRONMENT"

    echo "📦 Installing/updating dependencies..."
    composer install --no-interaction --quiet

    echo "🔑 Generating app key for testing..."
    php artisan key:generate --env=testing --quiet || true

    echo "🗃️  Running migrations on test database..."
    php artisan migrate:fresh --env=testing --force --quiet

    echo -e "${GREEN}✅ Environment ready${NC}"
}

# ── Gate Definitions ──────────────────────────────────────────────

gate_1() {
    announce "GATE 1 — UNIT TESTS (Models, Services, Helpers)"
    run_gate "1" "Unit Tests" \
        "php artisan test --testsuite=Gate1-Unit --env=testing --colors=always"
}

gate_2() {
    announce "GATE 2 — FEATURE TESTS (Auth, Middleware, UI Flows)"
    run_gate "2" "Feature Tests" \
        "php artisan test --testsuite=Gate2-Feature --env=testing --colors=always"
}

gate_3() {
    announce "GATE 3 — API TESTS (REST Endpoints, Sanctum Auth, CRUD)"
    run_gate "3" "API Tests" \
        "php artisan test --testsuite=Gate3-API --env=testing --colors=always"
}

gate_4() {
    if [ "$SKIP_DUSK" = true ]; then
        echo -e "${YELLOW}⏭️  Skipping GATE 4 (Dusk) — --no-dusk flag set${NC}"
        GATE_RESULTS+=("⏭️  GATE 4 - Browser/Visual Tests: SKIPPED")
        return
    fi

    announce "GATE 4 — BROWSER TESTS (Laravel Dusk, Screenshot Gates)"

    # Start ChromeDriver in background
    echo "🌐 Starting ChromeDriver..."
    php artisan dusk:chrome-driver --detect --quiet || true
    pkill -f chromedriver 2>/dev/null || true
    sleep 1
    ./vendor/laravel/dusk/bin/chromedriver-linux --port=9515 &>/dev/null &
    CHROME_PID=$!
    sleep 2

    # Run Dusk tests
    run_gate "4A" "Login Flow + Screenshots" \
        "php artisan dusk tests/Browser/DuskGatesTest.php --filter=Gate4A --env=testing"

    run_gate "4B" "Registration Flow" \
        "php artisan dusk tests/Browser/DuskGatesTest.php --filter=Gate4B --env=testing"

    run_gate "4C" "Dashboard Navigation" \
        "php artisan dusk tests/Browser/DuskGatesTest.php --filter=Gate4C --env=testing"

    run_gate "6A" "Responsive / Mobile Layout" \
        "php artisan dusk tests/Browser/DuskGatesTest.php --filter=Gate6A --env=testing"

    run_gate "6B" "Form Submission" \
        "php artisan dusk tests/Browser/DuskGatesTest.php --filter=Gate6B --env=testing"

    run_gate "6C" "AJAX / React Component" \
        "php artisan dusk tests/Browser/DuskGatesTest.php --filter=Gate6C --env=testing"

    # Stop ChromeDriver
    kill $CHROME_PID 2>/dev/null || true

    # List screenshots taken
    echo ""
    echo -e "${CYAN}📸 Screenshots captured:${NC}"
    ls -1 "$SCREENSHOT_DIR"/*.png 2>/dev/null | while read -r f; do
        echo "   $(basename "$f")"
    done
}

print_summary() {
    announce "GATE RUN SUMMARY"
    echo ""

    for result in "${GATE_RESULTS[@]}"; do
        echo -e "  $result"
    done

    echo ""
    echo -e "  ${GREEN}${BOLD}PASSED: ${PASS_COUNT}${NC}   ${RED}${BOLD}FAILED: ${FAIL_COUNT}${NC}"
    echo ""
    echo -e "  📄 Full log saved to: ${LOG_FILE}"

    if [ "$SKIP_DUSK" = false ] && ls "$SCREENSHOT_DIR"/*.png &>/dev/null 2>&1; then
        echo -e "  📸 Screenshots: ${SCREENSHOT_DIR}/"
    fi

    echo ""

    if [ "$FAIL_COUNT" -eq 0 ]; then
        echo -e "${GREEN}${BOLD}🎉 ALL GATES PASSED — Application is healthy!${NC}"
        exit 0
    else
        echo -e "${RED}${BOLD}💥 ${FAIL_COUNT} GATE(S) FAILED — Review the output above.${NC}"
        exit 1
    fi
}

# ── Main ──────────────────────────────────────────────────────────
clear
echo ""
echo -e "${BOLD}${CYAN}"
echo "  ██████╗  █████╗ ████████╗███████╗    ██████╗ ██╗   ██╗███╗   ██╗███╗   ██╗███████╗██████╗ "
echo "  ██╔════╝ ██╔══██╗╚══██╔══╝██╔════╝    ██╔══██╗██║   ██║████╗  ██║████╗  ██║██╔════╝██╔══██╗"
echo "  ██║  ███╗███████║   ██║   █████╗      ██████╔╝██║   ██║██╔██╗ ██║██╔██╗ ██║█████╗  ██████╔╝"
echo "  ██║   ██║██╔══██║   ██║   ██╔══╝      ██╔══██╗██║   ██║██║╚██╗██║██║╚██╗██║██╔══╝  ██╔══██╗"
echo "  ╚██████╔╝██║  ██║   ██║   ███████╗    ██║  ██║╚██████╔╝██║ ╚████║██║ ╚████║███████╗██║  ██║"
echo "   ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚══════╝    ╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚═╝  ╚═══╝╚══════╝╚═╝  ╚═╝"
echo -e "${NC}"
echo -e "  ${BOLD}Laravel Autonomous Testing Gate Runner${NC} — inspired by claude-mobile-ios-testing"
echo -e "  Running gate: ${YELLOW}${RUN_GATE}${NC}  |  Dusk: ${YELLOW}$([ "$SKIP_DUSK" = true ] && echo "SKIPPED" || echo "ENABLED")${NC}"
echo ""

check_requirements
prepare_environment

case "$RUN_GATE" in
    1)   gate_1 ;;
    2)   gate_2 ;;
    3)   gate_3 ;;
    4)   gate_4 ;;
    all) gate_1; gate_2; gate_3; gate_4 ;;
    *)
        echo -e "${RED}Unknown gate: $RUN_GATE. Use 1, 2, 3, 4, or all.${NC}"
        exit 1
        ;;
esac

print_summary
