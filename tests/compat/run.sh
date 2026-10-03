#!/usr/bin/env bash
# All PHP execution occurs in an official WordPress container via OrbStack.
set -Eeuo pipefail
IFS=$'\n\t'

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)
COMPAT_DIR="$ROOT/tests/compat"
RESULT_DIR="$COMPAT_DIR/.results"
mkdir -p "$RESULT_DIR"
fail() { printf 'compat runner: %s\n' "$*" >&2; exit 2; }
require_value() { [[ -n "${2:-}" ]] || fail "missing value for $1"; }

for forbidden in COMPOSE_FILE COMPOSE_PROJECT_NAME WORDPRESS_DB_HOST MYSQL_HOST DB_HOST DATABASE_URL COMPAT_VOLUME; do
  [[ -z "${!forbidden:-}" ]] || fail "external database or Compose override $forbidden is not allowed"
done

MODE=${1:-}; shift || true

run_lint() {
  local branches='' files=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --php-branches) branches=${2:-}; shift 2 ;;
      --files) files=${2:-}; shift 2 ;;
      *) fail "unknown lint option $1" ;;
    esac
  done
  require_value --php-branches "$branches"; require_value --files "$files"
  local branch file image
  IFS=',' read -r -a php_branches <<< "$branches"
  IFS=',' read -r -a php_files <<< "$files"
  for branch in "${php_branches[@]}"; do
    [[ "$branch" =~ ^[0-9]+[.][0-9]+$ ]] || fail "PHP must be a major.minor version"
    [[ "$branch" != 8.2 ]] || fail "PHP 8.2 is diagnostic-only and cannot satisfy lint"
    image="wordpress:7.1.2-php${branch}-apache"
    docker pull "$image" >/dev/null || fail "could not resolve official image $image"
    for file in "${php_files[@]}"; do
      [[ "$file" == *.php && -f "$ROOT/$file" ]] || fail "lint file must be a repository PHP file: $file"
      git -C "$ROOT" ls-files --error-unmatch -- "$file" >/dev/null || fail "lint file is not tracked: $file"
      docker run --rm --network none --volume "$ROOT:/workspace:ro" --workdir /workspace "$image" php -l "$file"
    done
  done
}

if [[ "$MODE" == lint ]]; then
  run_lint "$@"
  exit 0
fi

[[ "$MODE" == cell ]] || fail "supported commands: cell, lint"
WP_VERSION=''; PHP_VERSION=''; SCENARIO='activation-menu'
while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp) WP_VERSION=${2:-}; shift 2 ;;
    --php) PHP_VERSION=${2:-}; shift 2 ;;
    --scenario) SCENARIO=${2:-}; shift 2 ;;
    *) fail "unknown option $1" ;;
  esac
done
require_value --wp "$WP_VERSION"; require_value --php "$PHP_VERSION"
[[ "$WP_VERSION" =~ ^[0-9]+([.][0-9]+){2}$ ]] || fail "WordPress must be an exact patch version"
[[ "$PHP_VERSION" =~ ^[0-9]+[.][0-9]+$ ]] || fail "PHP must be a major.minor version"
[[ "$PHP_VERSION" != 8.2 ]] || fail "PHP 8.2 is diagnostic-only and cannot be a supported cell"
[[ "$SCENARIO" == activation-menu ]] || fail "unsupported cell scenario: $SCENARIO"

PROJECT="gigpress_compat_${RANDOM}_$$_$(date +%s)"
DB_PASSWORD="compat_${RANDOM}_${RANDOM}"
DB_ROOT_PASSWORD="root_${RANDOM}_${RANDOM}"
COMPOSE=(docker compose --project-name "$PROJECT" --file "$COMPAT_DIR/compose.yaml")
CLEANUP_NEEDED=false
cleanup() {
  local status=$?
  if [[ "$CLEANUP_NEEDED" == true ]]; then
    env REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" PHP_VERSION="$PHP_VERSION" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
  fi
  exit "$status"
}
trap cleanup EXIT INT TERM
compose_env() {
  env -u COMPOSE_FILE -u COMPOSE_PROJECT_NAME -u WORDPRESS_DB_HOST -u MYSQL_HOST -u DB_HOST -u DATABASE_URL \
    REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" PHP_VERSION="$PHP_VERSION" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" "$@"
}

CLEANUP_NEEDED=true
compose_env pull wordpress db
compose_env up -d db wordpress
for attempt in $(seq 1 45); do
  if compose_env exec -T db mariadb-admin ping -h localhost -uroot -p"$DB_ROOT_PASSWORD" --silent >/dev/null 2>&1; then break; fi
  [[ "$attempt" -eq 45 ]] && { compose_env logs --no-color >&2 || true; fail "disposable MariaDB did not become ready"; }
  sleep 2
done
output=$(compose_env exec -T -e COMPAT_PURPOSE="$SCENARIO" wordpress php /compat/probe.php) || { printf '%s\n' "$output" >&2; fail "probe failed"; }
printf '%s\n' "$output" | tee "$RESULT_DIR/${WP_VERSION}-php${PHP_VERSION}-${SCENARIO}.json"
printf '%s\n' "$output" | rtk jq -e '.status == "PASS" and .plugin_active == true and (.menu_slugs | index("gigpress.php")) and (.plugin_errors | length == 0)' >/dev/null || fail "probe did not report a warning-free active GigPress menu"
