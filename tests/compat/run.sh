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

metadata_value() {
  local file=$1 field=$2
  awk -v field="$field" '
    $0 ~ "^" field ":[[:space:]]*" {
      value = $0
      sub("^[^:]*:[[:space:]]*", "", value)
      print value
      exit
    }
  ' "$file"
}

run_metadata() {
  local expected_wp='' expected_php='' tested_up_to_file=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --expect-wp-min) expected_wp=${2:-}; shift 2 ;;
      --expect-php-min) expected_php=${2:-}; shift 2 ;;
      --allow-tested-up-to-from) tested_up_to_file=${2:-}; shift 2 ;;
      *) fail "unknown metadata option $1" ;;
    esac
  done
  require_value --expect-wp-min "$expected_wp"
  require_value --expect-php-min "$expected_php"
  require_value --allow-tested-up-to-from "$tested_up_to_file"
  [[ "$tested_up_to_file" == 'readme.txt' && -f "$ROOT/$tested_up_to_file" ]] || fail "tested-up-to metadata must come from repository readme.txt"

  local header="$ROOT/gigpress.php" readme="$ROOT/$tested_up_to_file"
  local header_wp header_php readme_wp readme_php tested_up_to
  header_wp=$(metadata_value "$header" 'Requires at least')
  header_php=$(metadata_value "$header" 'Requires PHP')
  readme_wp=$(metadata_value "$readme" 'Requires at least')
  readme_php=$(metadata_value "$readme" 'Requires PHP')
  tested_up_to=$(metadata_value "$readme" 'Tested up to')
  [[ "$header_wp" == "$expected_wp" && "$readme_wp" == "$expected_wp" ]] || fail "WordPress minimum metadata does not match $expected_wp"
  [[ "$header_php" == "$expected_php" && "$readme_php" == "$expected_php" ]] || fail "PHP minimum metadata does not match $expected_php"
  [[ "$tested_up_to" =~ ^[0-9]+[.][0-9]+$ ]] || fail "readme Tested up to must name a WordPress release line"
  [[ "$header_php" != 8.2 && "$readme_php" != 8.2 ]] || fail "PHP 8.2 is diagnostic-only and cannot be declared supported"
  printf '{"status":"PASS","wordpress_min":"%s","php_min":"%s","tested_up_to":"%s"}\n' "$header_wp" "$header_php" "$tested_up_to"
}

for forbidden in COMPOSE_FILE COMPOSE_PROJECT_NAME WORDPRESS_DB_HOST MYSQL_HOST DB_HOST DATABASE_URL COMPAT_VOLUME; do
  [[ -z "${!forbidden:-}" ]] || fail "external database or Compose override $forbidden is not allowed"
done

MODE=${1:-}; shift || true

run_lint() {
  local branches='' files='' all_tracked=false
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --php-branches) branches=${2:-}; shift 2 ;;
      --files) files=${2:-}; shift 2 ;;
      --all-tracked-php) all_tracked=true; shift ;;
      *) fail "unknown lint option $1" ;;
    esac
  done
  require_value --php-branches "$branches"
  if [[ "$all_tracked" == true ]]; then
    [[ -z "$files" ]] || fail "--files and --all-tracked-php cannot be combined"
    php_files=()
    while IFS= read -r file; do
      php_files+=("$file")
    done < <(git -C "$ROOT" ls-files '*.php')
    ((${#php_files[@]})) || fail "no tracked PHP files found"
  else
    require_value --files "$files"
    IFS=',' read -r -a php_files <<< "$files"
  fi
  local branch file image
  IFS=',' read -r -a php_branches <<< "$branches"
  for branch in "${php_branches[@]}"; do
    [[ "$branch" =~ ^[0-9]+[.][0-9]+$ ]] || fail "PHP must be a major.minor version"
    [[ "$branch" != 8.2 ]] || fail "PHP 8.2 is diagnostic-only and cannot satisfy lint"
    image="wordpress:7.1.2-php${branch}-apache"
    docker pull "$image" >/dev/null || fail "could not resolve official image $image"
    printf 'lint image %s (%s)\n' "$image" "$(docker image inspect --format '{{.Id}}' "$image")"
    for file in "${php_files[@]}"; do
      [[ "$file" == *.php && -f "$ROOT/$file" ]] || fail "lint file must be a repository PHP file: $file"
      git -C "$ROOT" ls-files --error-unmatch -- "$file" >/dev/null || fail "lint file is not tracked: $file"
      docker run --rm --network none --volume "$ROOT:/workspace:ro" --workdir /workspace "$image" php -l "$file"
    done
  done
}

wait_for_wordpress() {
  local attempt
  for attempt in $(seq 1 45); do
    if compose_env exec -T wordpress test -f /var/www/html/wp-load.php >/dev/null 2>&1; then return 0; fi
    sleep 2
  done
  compose_env logs --no-color >&2 || true
  fail "disposable WordPress did not become ready"
}

wait_for_database() {
  local attempt
  for attempt in $(seq 1 45); do
    if compose_env exec -T db mariadb -uwordpress -p"$DB_PASSWORD" wordpress -e 'SELECT 1' >/dev/null 2>&1; then return 0; fi
    sleep 2
  done
  compose_env logs --no-color >&2 || true
  fail "disposable MariaDB application account did not become ready"
}

normalise_matrix() {
  local wp_lines=$1 php_branches=$2
  [[ -n "$wp_lines" && -n "$php_branches" ]] || return 1
  local line branch pair
  local -a pairs=()
  IFS=',' read -r -a matrix_wp_lines <<< "$wp_lines"
  IFS=',' read -r -a matrix_php_branches <<< "$php_branches"
  for line in "${matrix_wp_lines[@]}"; do
    [[ "$line" =~ ^[0-9]+[.][0-9]+$ ]] || return 1
    for branch in "${matrix_php_branches[@]}"; do
      [[ "$branch" =~ ^[0-9]+[.][0-9]+$ && "$branch" != 8.2 ]] || return 1
      pairs+=("$line,$branch")
    done
  done
  printf '%s\n' "${pairs[@]}" | sort -u -t, -k1,1n -k2,2n
}

run_self_test() {
  local one one_count unique_count ordered
  one=$(normalise_matrix '7.1' '8.3') || fail "single-cell matrix was rejected"
  one_count=$(printf '%s\n' "$one" | wc -l | tr -d ' ')
  unique_count=$(normalise_matrix '7.1,7.0,7.1' '8.4,8.3,8.3' | wc -l | tr -d ' ')
  ordered=$(normalise_matrix '7.1,7.0' '8.4,8.3' | tr '\n' ' ')
  [[ "$one" == '7.1,8.3' && "$one_count" == 1 ]] || fail "single-cell matrix did not emit exactly one result"
  [[ "$unique_count" == 4 ]] || fail "matrix did not deduplicate exact pairs"
  [[ "$ordered" == '7.0,8.3 7.0,8.4 7.1,8.3 7.1,8.4 ' ]] || fail "matrix result ordering is not stable"
  normalise_matrix '' '8.3' >/dev/null 2>&1 && fail "empty matrix was accepted"
  normalise_matrix '7.1' '8.2' >/dev/null 2>&1 && fail "diagnostic PHP 8.2 was accepted as supported"
  (run_runtime_floor --fixture tests/compat/fixtures/php-floor-plugin.php --plugin gigpress/gigpress.php --wp-lines 7.1 --supported-php 8.3 --diagnostic-php 8.2) >/dev/null 2>&1 && fail "runtime-floor accepted both targets"
  (run_runtime_floor --plugin unknown/plugin.php --wp-lines 7.1 --supported-php 8.3 --diagnostic-php 8.2) >/dev/null 2>&1 && fail "runtime-floor accepted an unknown real-plugin target"
  grep -q "fixture-activate" "$COMPAT_DIR/probe.php" || fail "fixture lifecycle probe is missing"
  grep -q "real_plugin_inventory" "$COMPAT_DIR/probe.php" || fail "real-plugin inventory contract is missing"
  printf '%s\n' '{"status":"PASS","self_test":"matrix ordering, diagnostic exclusion, and distinct runtime targets"}'
}

run_menu_contract() {
  local wp='' php='' cases=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --wp) wp=${2:-}; shift 2 ;;
      --php) php=${2:-}; shift 2 ;;
      --cases) cases=${2:-}; shift 2 ;;
      *) fail "unknown menu-contract option $1" ;;
    esac
  done
  [[ "$wp" == '7.1.2' && "$php" == '8.3' ]] || fail "menu-contract requires WordPress 7.1.2 and PHP 8.3"
  [[ -n "$cases" ]] || fail "menu-contract requires --cases"
  local image="wordpress:${wp}-php${php}-apache" output status
  docker pull "$image" >/dev/null || fail "could not resolve official image $image"
  set +e
  output=$(docker run --rm -i --network none --volume "$ROOT:/workspace:ro" --workdir /workspace -e COMPAT_CASES="$cases" "$image" php <<'PHP'
<?php
function extract_function($source, $name) {
    $tokens = token_get_all($source);
    $capture = false;
    $candidate = false;
    $depth = 0;
    $code = '';
    foreach ($tokens as $token) {
        $text = is_array($token) ? $token[1] : $token;
        if (!$capture && is_array($token) && $token[0] === T_FUNCTION) {
            $candidate = true;
            $code = $text;
            continue;
        }
        if (!$capture && $candidate) {
            $code .= $text;
            if (is_array($token) && $token[0] === T_STRING) {
                if ($token[1] === $name) {
                    $capture = true;
                } else {
                    $candidate = false;
                    $code = '';
                }
            }
            continue;
        }
        if ($capture) {
            $code .= $text;
            if ($text === '{') {
                ++$depth;
            } elseif ($text === '}') {
                --$depth;
                if ($depth === 0) {
                    return $code;
                }
            }
        }
    }
    throw new RuntimeException("Could not extract {$name}");
}

$source = file_get_contents('gigpress.php');
$callback = extract_function($source, 'custom_menu_order');
$adminMenu = extract_function($source, 'gigpress_admin_menu');
eval($callback);
$requested = array_filter(explode(',', getenv('COMPAT_CASES') ?: ''));
$failures = array();
function contract_assert($condition, $case, $message) {
    global $failures;
    if (!$condition) {
        $failures[] = array('case' => $case, 'assertion' => $message);
    }
}
function invoke_contract($input, $case) {
    global $menu, $failures;
    $menu = array(array('Sentinel', 'read', 'sentinel.php'));
    $before = serialize($menu);
    unset($GLOBALS['gigpress_menu_order_conflict']);
    if ($case === 'order-conflict') {
        $GLOBALS['gigpress_menu_order_conflict'] = true;
    }
    try {
        $actual = custom_menu_order($input);
    } catch (Throwable $error) {
        $failures[] = array('case' => $case, 'assertion' => 'callback returns an order without throwing', 'actual' => $error->getMessage());
        return null;
    }
    contract_assert(serialize($menu) === $before, $case, 'callback does not mutate global $menu');
    return $actual;
}
function assert_order($case, $input, $expected) {
    $actual = invoke_contract($input, $case);
    if ($actual !== null) {
        contract_assert($actual === $expected, $case, 'returned order matches the stable transform or untouched fallback');
    }
}

$cases = array(
    'preferred' => function () {
        assert_order('preferred', array('index.php', 'gigpress/gigpress.php', 'edit-comments.php', 'separator-gp', 'tools.php'), array('index.php', 'edit-comments.php', 'separator-gp', 'gigpress/gigpress.php', 'tools.php'));
        global $adminMenu;
        contract_assert(strpos($adminMenu, "'separator-gp'") !== false, 'preferred', 'admin_menu registers the owned separator');
        contract_assert(strpos($adminMenu, 'add_menu_page') < strpos($adminMenu, "'separator-gp'"), 'preferred', 'separator registration follows GigPress page registration');
    },
    'index-zero' => function () {
        assert_order('index-zero', array('gigpress/gigpress.php', 'index.php', 'edit-comments.php', 'separator-gp', 'tools.php'), array('index.php', 'edit-comments.php', 'separator-gp', 'gigpress/gigpress.php', 'tools.php'));
    },
    'missing' => function () {
        foreach (array(
            array('index.php', 'gigpress/gigpress.php', 'separator-gp'),
            array('index.php', 'edit-comments.php', 'separator-gp'),
            array('index.php', 'edit-comments.php', 'gigpress/gigpress.php'),
        ) as $input) {
            assert_order('missing', $input, $input);
        }
    },
    'duplicate' => function () {
        foreach (array(
            array('edit-comments.php', 'edit-comments.php', 'separator-gp', 'gigpress/gigpress.php'),
            array('edit-comments.php', 'separator-gp', 'gigpress/gigpress.php', 'gigpress/gigpress.php'),
            array('edit-comments.php', 'separator-gp', 'separator-gp', 'gigpress/gigpress.php'),
        ) as $input) {
            assert_order('duplicate', $input, $input);
        }
    },
    'empty' => function () { assert_order('empty', array(), array()); },
    'single' => function () { assert_order('single', array('gigpress/gigpress.php'), array('gigpress/gigpress.php')); },
    'order-conflict' => function () {
        $input = array('index.php', 'gigpress/gigpress.php', 'edit-comments.php', 'separator-gp', 'tools.php');
        assert_order('order-conflict', $input, $input);
    },
    'no-global-mutation' => function () {
        $input = array('index.php', 'gigpress/gigpress.php', 'edit-comments.php', 'separator-gp', 'tools.php');
        invoke_contract($input, 'no-global-mutation');
    },
);
foreach ($requested as $case) {
    if (!isset($cases[$case])) {
        $failures[] = array('case' => $case, 'assertion' => 'requested case is supported');
        continue;
    }
    $cases[$case]();
}
echo json_encode(array('status' => $failures ? 'FAIL' : 'PASS', 'target' => 'menu-contract', 'requested_cases' => array_values($requested), 'failures' => $failures), JSON_UNESCAPED_SLASHES) . PHP_EOL;
echo ($failures ? 'not ok 1 - menu-contract' : 'ok 1 - menu-contract') . PHP_EOL;
echo '# tests 1' . PHP_EOL;
echo '# pass ' . ($failures ? '0' : '1') . PHP_EOL;
echo '# fail ' . ($failures ? '1' : '0') . PHP_EOL;
exit($failures ? 1 : 0);
PHP
  )
  status=$?
  set -e
  printf '%s\n' "$output" | tee "$RESULT_DIR/${wp}-php${php}-menu-contract.json"
  [[ "$status" -eq 0 ]] || return "$status"
  printf '%s\n' "$output" | rtk jq -e '.status == "PASS" and .target == "menu-contract" and (.failures | length == 0)' >/dev/null || fail "menu-contract did not satisfy its contract"
}

run_matrix() {
  local wp_lines='' php_branches='' php_supported='' scenario='activation-menu' conflict_fixture='' conflict_mode=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --wp-lines) wp_lines=${2:-}; shift 2 ;;
      --php-branches) php_branches=${2:-}; shift 2 ;;
      --php-supported) php_supported=${2:-}; shift 2 ;;
      --scenario) scenario=${2:-}; shift 2 ;;
      --conflict-fixture) conflict_fixture=${2:-}; shift 2 ;;
      --conflict-mode) conflict_mode=${2:-}; shift 2 ;;
      *) fail "unknown matrix option $1" ;;
    esac
  done
  if [[ -n "$php_supported" ]]; then
    [[ -z "$php_branches" && "$php_supported" == upstream ]] || fail "--php-supported upstream cannot be combined with --php-branches"
    php_branches='8.3,8.4,8.5'
  fi
  if [[ -n "$conflict_fixture" || -n "$conflict_mode" ]]; then
    [[ "$conflict_fixture" == 'tests/compat/fixtures/menu-conflict-plugin.php' && "$conflict_mode" == order-only ]] || fail "matrix conflict coverage requires the order-only fixture"
  fi
  [[ "$scenario" == activation-menu || "$scenario" == csv-roundtrip ]] || fail "unsupported matrix scenario: $scenario"
  local pair line branch wp_version
  while IFS=, read -r line branch; do
    wp_version=$(runtime_wp_version "$line")
    bash "$COMPAT_DIR/run.sh" cell --wp "$wp_version" --php "$branch" --scenario "$scenario" ${conflict_fixture:+--conflict-fixture "$conflict_fixture" --conflict-mode "$conflict_mode"}
  done < <(normalise_matrix "$wp_lines" "$php_branches") || fail "matrix cell failed"
}

runtime_wp_version() {
  case "$1" in
    7.0) printf '%s' '7.0.3' ;;
    7.1) printf '%s' '7.1.2' ;;
    *) fail "unsupported WordPress line $1" ;;
  esac
}

run_fixture_phase() {
  local purpose=$1 output result
  output=$(compose_env exec -T -e COMPAT_PURPOSE="$purpose" wordpress php /compat/probe.php) || { printf '%s\n' "$output" >&2; fail "fixture probe $purpose failed"; }
  result=$(printf '%s\n' "$output" | tail -n 1)
  printf '%s\n' "$result" | rtk jq -e '.status == "PASS" and .fixture_runtime.status == "PASS"' >/dev/null || { printf '%s\n' "$output" >&2; fail "fixture probe $purpose did not satisfy its contract"; }
  printf '%s\n' "$output"
}

run_real_plugin_phase() {
  local purpose=$1 output result
  output=$(compose_env exec -T -e COMPAT_PURPOSE="$purpose" wordpress php /compat/probe.php) || { printf '%s\n' "$output" >&2; fail "real-plugin probe $purpose failed"; }
  result=$(printf '%s\n' "$output" | tail -n 1)
  printf '%s\n' "$result" | rtk jq -e '.status == "PASS" and .real_plugin_runtime.status == "PASS" and (.plugin_errors | length == 0)' >/dev/null || { printf '%s\n' "$output" >&2; fail "real-plugin probe $purpose did not satisfy its contract"; }
  printf '%s\n' "$result"
}

run_runtime_floor() {
  local fixture='' plugin='' wp_lines='' supported_php='' diagnostic_php=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --fixture) fixture=${2:-}; shift 2 ;;
      --plugin) plugin=${2:-}; shift 2 ;;
      --wp-lines) wp_lines=${2:-}; shift 2 ;;
      --supported-php) supported_php=${2:-}; shift 2 ;;
      --diagnostic-php) diagnostic_php=${2:-}; shift 2 ;;
      *) fail "unknown runtime-floor option $1" ;;
    esac
  done
  [[ -n "$wp_lines" && -n "$supported_php" && -n "$diagnostic_php" ]] || fail "runtime-floor requires WordPress and PHP versions"
  [[ "$supported_php" != 8.2 && "$diagnostic_php" == 8.2 ]] || fail "PHP 8.2 is diagnostic-only"
  if [[ -n "$fixture" && -n "$plugin" ]] || [[ -z "$fixture" && -z "$plugin" ]]; then fail "select exactly one of --fixture or --plugin"; fi
  local target purpose_prefix
  if [[ -n "$plugin" ]]; then
    [[ "$plugin" == 'gigpress/gigpress.php' ]] || fail "unknown real-plugin target $plugin"
    target='plugin'
    purpose_prefix='real'
  else
    [[ "$fixture" == 'tests/compat/fixtures/php-floor-plugin.php' ]] || fail "fixture target must be the repository controlled PHP-floor plugin"
    target='fixture'
    purpose_prefix='fixture'
  fi

  local line
  IFS=',' read -r -a target_lines <<< "$wp_lines"
  for line in "${target_lines[@]}"; do
    WP_VERSION=$(runtime_wp_version "$line")
    PHP_VERSION=$supported_php
    PROJECT="gigpress_floor_${line//./}_${RANDOM}_$$_$(date +%s)"
    DB_PASSWORD="compat_${RANDOM}_${RANDOM}"
    DB_ROOT_PASSWORD="root_${RANDOM}_${RANDOM}"
    COMPOSE=(docker compose --project-name "$PROJECT" --file "$COMPAT_DIR/compose.yaml")
    CLEANUP_NEEDED=true
    cleanup_floor() {
      env REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" PHP_VERSION="$PHP_VERSION" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
    }
    compose_env() {
      env -u COMPOSE_FILE -u COMPOSE_PROJECT_NAME -u WORDPRESS_DB_HOST -u MYSQL_HOST -u DB_HOST -u DATABASE_URL REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" PHP_VERSION="$PHP_VERSION" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" "$@"
    }
    trap cleanup_floor EXIT INT TERM
    if ! compose_env pull --quiet wordpress db >/dev/null || ! compose_env up -d db >/dev/null; then cleanup_floor; fail "could not start isolated fixture database $line/PHP $supported_php"; fi
    wait_for_database
    if ! compose_env up -d wordpress >/dev/null; then cleanup_floor; fail "could not start isolated fixture WordPress $line/PHP $supported_php"; fi
    wait_for_wordpress
    if [[ "$target" == fixture ]]; then
      supported_state=$(run_fixture_phase fixture-activate)
    else
      supported_state=$(run_real_plugin_phase real-activate)
    fi
    PHP_VERSION=$diagnostic_php
    if ! compose_env up -d --force-recreate wordpress >/dev/null; then cleanup_floor; fail "could not start diagnostic fixture cell $line/PHP $diagnostic_php"; fi
    wait_for_wordpress
    if [[ "$target" == fixture ]]; then
      diagnostic_state=$(run_fixture_phase fixture-low)
    else
      diagnostic_state=$(run_real_plugin_phase real-low)
    fi
    PHP_VERSION=$supported_php
    if ! compose_env up -d --force-recreate wordpress >/dev/null; then cleanup_floor; fail "could not restore fixture cell $line/PHP $supported_php"; fi
    wait_for_wordpress
    if [[ "$target" == fixture ]]; then
      recovered_state=$(run_fixture_phase fixture-recover)
    else
      compose_env exec -T wordpress php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' || fail "real-plugin recovery did not return to a supported PHP runtime"
      recovered_state=$supported_state
      printf '%s\n' "$supported_state" "$diagnostic_state" "$recovered_state" | rtk jq -s '.[0].real_plugin_runtime.active_state == .[1].real_plugin_runtime.active_state and .[1].real_plugin_runtime.active_state == .[2].real_plugin_runtime.active_state and .[0].real_plugin_runtime.data_snapshot == .[1].real_plugin_runtime.data_snapshot and .[1].real_plugin_runtime.data_snapshot == .[2].real_plugin_runtime.data_snapshot' | rtk jq -e . >/dev/null || fail "real-plugin runtime-floor changed active state or GigPress data/options"
    fi
    cleanup_floor
  done
}

run_diagnose_menu() {
  local diagnostic='' fixture='' conflict_mode='' expect_key='' boundary=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --wp) WP_VERSION=${2:-}; shift 2 ;;
      --php) PHP_VERSION=${2:-}; shift 2 ;;
      --diagnostic) diagnostic=${2:-}; shift 2 ;;
      --conflict-fixture) fixture=${2:-}; shift 2 ;;
      --conflict-mode) conflict_mode=${2:-}; shift 2 ;;
      --expect-key) expect_key=${2:-}; shift 2 ;;
      --assert-repository-boundary) boundary=${2:-}; shift 2 ;;
      *) fail "unknown diagnose-menu option $1" ;;
    esac
  done
  require_value --wp "${WP_VERSION:-}"; require_value --php "${PHP_VERSION:-}"
  require_value --diagnostic "$diagnostic"; require_value --conflict-mode "$conflict_mode"; require_value --expect-key "$expect_key"
  [[ "$WP_VERSION" =~ ^[0-9]+([.][0-9]+){2}$ ]] || fail "WordPress must be an exact patch version"
  [[ "$PHP_VERSION" =~ ^[0-9]+[.][0-9]+$ ]] || fail "PHP must be a major.minor version"
  [[ "$PHP_VERSION" == 8.2 || "$PHP_VERSION" == 8.3 ]] || fail "diagnose-menu accepts PHP 8.2 or 8.3"
  [[ "$diagnostic" == 'tests/compat/diagnostics/menu-trace.php' && -f "$ROOT/$diagnostic" ]] || fail "diagnostic must be the repository menu trace"
  case "$conflict_mode" in
    exact-key-late-add) [[ "$fixture" == 'tests/compat/fixtures/menu-conflict-plugin.php' && -f "$ROOT/$fixture" ]] || fail "exact-key-late-add requires the repository controlled fixture" ;;
    checkout-only) [[ -z "$fixture" ]] || fail "checkout-only does not accept a fixture" ;;
    *) fail "unsupported diagnose-menu conflict mode: $conflict_mode" ;;
  esac
  if [[ -n "$boundary" ]]; then
    [[ "$boundary" == .planning/phases/01-compatibility-baseline-and-menu-diagnosis/01-DIAGNOSIS.md && -f "$ROOT/$boundary" ]] || fail "repository-boundary assertion requires the phase diagnosis"
    rtk grep -Fq 'Fixture-only attribution' "$ROOT/$boundary" || fail "diagnosis omits fixture-only attribution"
    rtk grep -Fq 'cannot be proven from repository evidence' "$ROOT/$boundary" || fail "diagnosis omits the live-site evidence boundary"
    rtk grep -Fq 'Undefined index: separator-gp' "$ROOT/$boundary" || fail "diagnosis omits related historical separator-gp evidence"
    rtk grep -Fq 'standard WordPress order' "$ROOT/$boundary" || fail "diagnosis omits the D-04 fallback"
    git -C "$ROOT" log --all -S'separator-gigpress' --format=%H -- gigpress.php | rtk grep -q . && fail "repository history unexpectedly contains separator-gigpress in gigpress.php"
  fi
  PROJECT="gigpress_menu_diagnosis_${RANDOM}_$$_$(date +%s)"
  DB_PASSWORD="compat_${RANDOM}_${RANDOM}"; DB_ROOT_PASSWORD="root_${RANDOM}_${RANDOM}"
  COMPOSE=(docker compose --project-name "$PROJECT" --file "$COMPAT_DIR/compose.yaml")
  CLEANUP_NEEDED=false
  cleanup() {
    local status=$?
    [[ "$CLEANUP_NEEDED" == true ]] && env REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" PHP_VERSION="$PHP_VERSION" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
    exit "$status"
  }
  trap cleanup EXIT INT TERM
  compose_env() {
    env -u COMPOSE_FILE -u COMPOSE_PROJECT_NAME -u WORDPRESS_DB_HOST -u MYSQL_HOST -u DB_HOST -u DATABASE_URL REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" PHP_VERSION="$PHP_VERSION" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" "$@"
  }
  CLEANUP_NEEDED=true
  compose_env pull wordpress db
  compose_env up -d db wordpress
  wait_for_database; wait_for_wordpress
  compose_env exec -T wordpress sh -c 'mkdir -p /var/www/html/wp-content/mu-plugins && cp /var/www/html/wp-content/plugins/gigpress/tests/compat/diagnostics/menu-trace.php /var/www/html/wp-content/mu-plugins/gigpress-menu-trace.php'
  [[ "$conflict_mode" == exact-key-late-add ]] && compose_env exec -T wordpress sh -c 'cp /var/www/html/wp-content/plugins/gigpress/tests/compat/fixtures/menu-conflict-plugin.php /var/www/html/wp-content/plugins/menu-conflict-plugin.php'
  output=$(compose_env exec -T -e COMPAT_PURPOSE=diagnose-menu -e COMPAT_CONFLICT_MODE="$conflict_mode" wordpress php /compat/probe.php) || { printf '%s\n' "$output" >&2; fail "menu diagnostic probe failed"; }
  printf '%s\n' "$output" | tee "$RESULT_DIR/${WP_VERSION}-php${PHP_VERSION}-diagnose-menu-${conflict_mode}.json"
  if [[ "$conflict_mode" == exact-key-late-add ]]; then
    printf '%s\n' "$output" | rtk jq -e --arg key "$expect_key" '.status == "PASS" and .menu_trace.trace_is_request_local == true and (.menu_trace.row_creators | any(.slug == $key and .callback == "gigpress_menu_conflict_late_add" and .priority == 20)) and (.menu_trace.missing_from_input | index($key)) and (.menu_trace.missing_from_returned_order | index($key)) and (.menu_trace.callbacks | any(.identity == "gigpress_menu_conflict_late_add" and .priority == 20))' >/dev/null || fail "trace did not attribute the exact controlled separator key"
  else
    printf '%s\n' "$output" | rtk jq -e --arg key "$expect_key" '.status == "PASS" and (.menu_trace.final_slugs | index($key))' >/dev/null || fail "checkout-only trace did not retain the checkout separator"
  fi
  if [[ "$PHP_VERSION" == 8.2 ]]; then
    printf '%s\n' "$output" | rtk jq -e '.menu_trace.runtime_label == "diagnostic-only"' >/dev/null || fail "PHP 8.2 was not labeled diagnostic-only"
  else
    printf '%s\n' "$output" | rtk jq -e '.menu_trace.runtime_label == "supported"' >/dev/null || fail "supported PHP diagnostic was mislabeled"
  fi
}

if [[ "$MODE" == metadata ]]; then
  run_metadata "$@"
  exit 0
fi

if [[ "$MODE" == diagnose-menu ]]; then
  run_diagnose_menu "$@"
  exit 0
fi

if [[ "$MODE" == lint ]]; then
  run_lint "$@"
  exit 0
fi

if [[ "$MODE" == self-test ]]; then
  run_self_test "$@"
  exit 0
fi

if [[ "$MODE" == runtime-floor ]]; then
  run_runtime_floor "$@"
  exit 0
fi

if [[ "$MODE" == menu-contract ]]; then
  run_menu_contract "$@"
  exit 0
fi

if [[ "$MODE" == matrix ]]; then
  run_matrix "$@"
  exit 0
fi

[[ "$MODE" == cell ]] || fail "supported commands: cell, matrix, lint, metadata, self-test, runtime-floor, menu-contract"
WP_VERSION=''; PHP_VERSION=''; SCENARIO='activation-menu'; CONFLICT_FIXTURE=''; CONFLICT_MODE=''
while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp) WP_VERSION=${2:-}; shift 2 ;;
    --php) PHP_VERSION=${2:-}; shift 2 ;;
    --scenario) SCENARIO=${2:-}; shift 2 ;;
    --conflict-fixture) CONFLICT_FIXTURE=${2:-}; shift 2 ;;
    --conflict-mode) CONFLICT_MODE=${2:-}; shift 2 ;;
    *) fail "unknown option $1" ;;
  esac
done
require_value --wp "$WP_VERSION"; require_value --php "$PHP_VERSION"
[[ "$WP_VERSION" =~ ^[0-9]+([.][0-9]+){2}$ ]] || fail "WordPress must be an exact patch version"
[[ "$PHP_VERSION" =~ ^[0-9]+[.][0-9]+$ ]] || fail "PHP must be a major.minor version"
[[ "$PHP_VERSION" != 8.2 ]] || fail "PHP 8.2 is diagnostic-only and cannot be a supported cell"
case "$SCENARIO" in
  activation-menu|csv-roundtrip) ;;
  *) fail "unsupported cell scenario: $SCENARIO" ;;
esac
if [[ -n "$CONFLICT_FIXTURE" || -n "$CONFLICT_MODE" ]]; then
  [[ "$CONFLICT_FIXTURE" == 'tests/compat/fixtures/menu-conflict-plugin.php' && "$CONFLICT_MODE" == order-only ]] || fail "cell conflict coverage requires the order-only fixture"
fi

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
wait_for_wordpress
if [[ "$CONFLICT_MODE" == order-only ]]; then
  compose_env exec -T wordpress sh -c 'cp /var/www/html/wp-content/plugins/gigpress/tests/compat/fixtures/menu-conflict-plugin.php /var/www/html/wp-content/plugins/menu-conflict-plugin.php'
  compose_env exec -T -e COMPAT_CONFLICT_MODE=order-only wordpress php -r 'define("WP_INSTALLING", true); require "/var/www/html/wp-load.php"; require_once ABSPATH . "wp-admin/includes/plugin.php"; $result = activate_plugin("menu-conflict-plugin.php", "", false, false); if (is_wp_error($result)) { fwrite(STDERR, $result->get_error_message()); exit(1); }'
fi
output=$(compose_env exec -T -e COMPAT_PURPOSE="$SCENARIO" -e COMPAT_CONFLICT_MODE="$CONFLICT_MODE" wordpress php /compat/probe.php) || { printf '%s\n' "$output" >&2; fail "probe failed"; }
printf '%s\n' "$output" | tee "$RESULT_DIR/${WP_VERSION}-php${PHP_VERSION}-${SCENARIO}.json"
printf '%s\n' "$output" | rtk jq -e '.status == "PASS" and .plugin_active == true and (.menu_slugs | index("gigpress.php")) and (.plugin_errors | length == 0)' >/dev/null || fail "probe did not report a warning-free active GigPress menu"
