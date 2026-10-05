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
  local expected_wp='' expected_php='' tested_up_to_file='' plugin_file='' readme_file='' matrix_evidence='' require_tested_line_pass=false
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --expect-wp-min) expected_wp=${2:-}; shift 2 ;;
      --expect-php-min) expected_php=${2:-}; shift 2 ;;
      --allow-tested-up-to-from) tested_up_to_file=${2:-}; shift 2 ;;
      --plugin) plugin_file=${2:-}; shift 2 ;;
      --readme) readme_file=${2:-}; shift 2 ;;
      --matrix-evidence) matrix_evidence=${2:-}; shift 2 ;;
      --require-tested-line-pass) require_tested_line_pass=true; shift ;;
      *) fail "unknown metadata option $1" ;;
    esac
  done
  require_value --expect-wp-min "$expected_wp"
  require_value --expect-php-min "$expected_php"
  if [[ -n "$readme_file" || -n "$plugin_file" || -n "$matrix_evidence" || "$require_tested_line_pass" == true ]]; then
    [[ "$plugin_file" == 'gigpress.php' && -f "$ROOT/$plugin_file" ]] || fail "metadata plugin must be the repository gigpress.php"
    [[ "$readme_file" == 'readme.txt' && -f "$ROOT/$readme_file" ]] || fail "metadata readme must be repository readme.txt"
    [[ -n "$matrix_evidence" && -f "$ROOT/$matrix_evidence" ]] || fail "matrix evidence is required"
    tested_up_to_file=$readme_file
  else
    require_value --allow-tested-up-to-from "$tested_up_to_file"
    [[ "$tested_up_to_file" == 'readme.txt' && -f "$ROOT/$tested_up_to_file" ]] || fail "tested-up-to metadata must come from repository readme.txt"
    plugin_file='gigpress.php'
  fi

  local header="$ROOT/$plugin_file" readme="$ROOT/$tested_up_to_file"
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
  if [[ "$require_tested_line_pass" == true ]]; then
    rtk grep -Fq "| ${tested_up_to}" "$ROOT/$matrix_evidence" || fail "matrix evidence has no row for readme Tested up to ${tested_up_to}"
    rtk grep -Fq 'PASS' "$ROOT/$matrix_evidence" || fail "matrix evidence has no passing workflow rows"
    evidence_revision=$(awk -F'`' '/^Source revision: `/ { print $2; exit }' "$ROOT/$matrix_evidence")
    [[ "$evidence_revision" =~ ^[0-9a-f]{40}$ ]] || fail "matrix evidence has no full source revision"
    git -C "$ROOT" merge-base --is-ancestor "$evidence_revision" HEAD || fail "matrix evidence source revision is not in this checkout"
    git -C "$ROOT" diff --quiet "$evidence_revision" HEAD -- gigpress.php || fail "packaged plugin metadata differs from the matrix evidence source revision"
  fi
  printf '{"status":"PASS","wordpress_min":"%s","php_min":"%s","tested_up_to":"%s"}\n' "$header_wp" "$header_php" "$tested_up_to"
}

for forbidden in COMPOSE_FILE COMPOSE_PROJECT_NAME WORDPRESS_DB_HOST MYSQL_HOST DB_HOST DATABASE_URL COMPAT_VOLUME; do
  [[ -z "${!forbidden:-}" ]] || fail "external database or Compose override $forbidden is not allowed"
done

MODE=${1:-}; shift || true

# Exercise the actual cleanup body with a controlled backend; this is a harness unit check.
if [[ "$MODE" == browser-cleanup-contract-test ]]; then
  python3 - "$COMPAT_DIR/run.sh" <<'PYTEST'
import pathlib, re, subprocess, sys, tempfile
source = pathlib.Path(sys.argv[1]).read_text()
body = re.search(r'^  browser_cleanup\(\) \{\n.*?^  \}', source, re.M | re.S).group(0)
cases = [('compose_failure', 1, 0, '', 0, True), ('inventory_failure', 0, 1, '', 0, True),
         ('leftover_resource', 0, 0, 'owned-resource', 0, True), ('clean_success', 0, 0, '', 0, False),
         ('operation_failure_cleaned', 0, 0, '', 7, False)]
failed = 0
for index, (name, down, inventory, leftover, initial, retained) in enumerate(cases, 1):
    with tempfile.TemporaryDirectory(prefix='gigpress-cleanup-contract-') as root:
        directory = pathlib.Path(root) / 'gigpress-browser-unit'
        directory.mkdir(mode=0o700)
        (directory / 'session.json').write_text('{}')
        # Functions receive their own arguments, so put controlled outcomes in distinct globals.
        script = """BROWSER_DIR=$1; PROJECT=gigpress_browser_unit; BROWSER_RETAIN=false
DOWN_RESULT=$2; INVENTORY_RESULT=$3; LEFTOVER=$4; INITIAL_RESULT=$5
compose_env() { echo synthetic-cleanup-log; return "$DOWN_RESULT"; }
docker() { printf '%s' "$LEFTOVER"; return "$INVENTORY_RESULT"; }
""" + body + '\ntrap browser_cleanup EXIT\nexit "$INITIAL_RESULT"\n'
        result = subprocess.run(['bash', '-c', script, 'cleanup-test', str(directory), str(down), str(inventory), leftover, str(initial)], capture_output=True, text=True)
        exists = directory.exists()
        ok = exists == retained and (result.returncode != 0 if retained else result.returncode == initial)
        if retained:
            ok = ok and (directory / 'session.json').is_file() and (directory / 'cleanup.log').is_file()
        failed += not ok
        print(('ok' if ok else 'not ok') + ' %s - browser-cleanup.%s' % (index, name))
print('# tests %s\n# pass %s\n# fail %s' % (len(cases), len(cases)-failed, failed))
sys.exit(bool(failed))
PYTEST
  exit $?
fi

# Public evidence contract: an absent report can never establish a passing matrix.
if [[ "$MODE" == public-contract-test ]]; then
  contract_dir=$(mktemp -d "${TMPDIR:-/tmp}/gigpress-public-contract.XXXXXX"); chmod 700 "$contract_dir"
  contract_output=$(COMPAT_PRIVATE_EVIDENCE_DIR="$contract_dir" bash "$COMPAT_DIR/run.sh" public-evidence --action validate --report "$contract_dir/missing.md" --wp-lines 7.0,7.1 --php-min 8.3 2>&1) && contract_exit=0 || contract_exit=$?
  rmdir "$contract_dir"
  if [[ "$contract_exit" -ne 0 && "$contract_output" == *'public evidence report is required'* ]]; then
    printf 'ok 1 - public-evidence.missing_report_rejected\n# tests 1\n# pass 1\n# fail 0\n'
  else
    printf 'not ok 1 - public-evidence.missing_report_rejected\n# expected: public evidence report is required\n# actual: %s\n# tests 1\n# pass 0\n# fail 1\n' "$contract_output"
    exit 1
  fi
  exit 0
fi

if [[ "$MODE" == administration-contract-test ]]; then
  contract_dir=$(mktemp -d "${TMPDIR:-/tmp}/gigpress-admin-contract.XXXXXX"); chmod 700 "$contract_dir"
  contract_output=$(COMPAT_PRIVATE_EVIDENCE_DIR="$contract_dir" bash "$COMPAT_DIR/run.sh" administration-evidence --action validate --report "$contract_dir/missing.md" --wp-lines 7.0,7.1 --php-min 8.3 2>&1) && contract_exit=0 || contract_exit=$?
  rmdir "$contract_dir"
  if [[ "$contract_exit" -ne 0 && "$contract_output" == *'administration evidence report is required'* ]]; then
    printf 'ok 1 - administration-evidence.missing_report_rejected\n# tests 1\n# pass 1\n# fail 0\n'
  else
    printf 'not ok 1 - administration-evidence.missing_report_rejected\n# expected: administration evidence report is required\n# actual: %s\n# tests 1\n# pass 0\n# fail 1\n' "$contract_output"
    exit 1
  fi
  exit 0
fi

# The ownership contract is runnable before transport implementation (TDD RED).
if [[ "$MODE" == browser-contract-test ]]; then
  test_output=$(bash "$COMPAT_DIR/run.sh" browser-fixture --action status --session /tmp/foreign-gigpress-session.json 2>&1) && test_exit=0 || test_exit=$?
  if [[ "$test_exit" -ne 0 && "$test_output" == *'not an owned private browser session'* ]]; then
    printf 'ok 1 - browser-session.foreign_metadata_rejected\n# tests 1\n# pass 1\n# fail 0\n'
  else
    printf 'not ok 1 - browser-session.foreign_metadata_rejected\n# expected: not an owned private browser session\n# actual: %s\n# tests 1\n# pass 0\n# fail 1\n' "$test_output"
    exit 1
  fi
  exit 0
fi

if [[ "$MODE" == browser-expanded-test ]]; then
  smoke_output=$(bash "$COMPAT_DIR/run.sh" browser-fixture --action smoke --wp 7.1.2 --php 8.3 --case all 2>&1) && smoke_exit=0 || smoke_exit=$?
  printf '%s\n' "$smoke_output"
  if [[ "$smoke_exit" == 0 ]] && printf '%s\n' "$smoke_output" | jq -se 'map(select(.case? == "all")) | length == 1 and (.[0].cases|map(.case)|sort) == ["entry","guards","settings"] and ([.[0].cases[] | .assertion_count > 0 and .status == "PASS"]|all)' >/dev/null; then
    printf 'ok 1 - browser-http.all_nonempty_authority_cases\n# tests 1\n# pass 1\n# fail 0\n'
  else
    printf 'not ok 1 - browser-http.all_nonempty_authority_cases\n# expected: entry, settings and guards with positive real HTTP assertions\n# tests 1\n# pass 0\n# fail 1\n'
    exit 1
  fi
  exit 0
fi

run_lint() {
  local branches='' files='' all_tracked=false image_ids=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --php-branches) branches=${2:-}; shift 2 ;;
      --image-ids) image_ids=${2:-}; shift 2 ;;
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
    image="wordpress:php${branch}-apache"
    if [[ -n "$image_ids" ]]; then image=$(pinned_value "$image_ids" "$branch");
    else docker pull "$image" >/dev/null || fail "could not resolve official image $image"; fi
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

install_archived_wordpress_core() {
  compose_env exec -T -e COMPAT_EXPECTED_WP_VERSION="$WP_VERSION" wordpress sh -ec '
    current=$(awk "/wp_version = / { print; exit }" /var/www/html/wp-includes/version.php | tr -cd "0-9.\\n")
    [ "$current" = "$COMPAT_EXPECTED_WP_VERSION" ] && exit 0
    archive="/tmp/wordpress-${COMPAT_EXPECTED_WP_VERSION}.tar.gz"
    curl -fsSL "https://wordpress.org/wordpress-${COMPAT_EXPECTED_WP_VERSION}.tar.gz" -o "$archive"
    find /var/www/html -mindepth 1 -maxdepth 1 ! -name wp-content ! -name wp-config.php -exec rm -rf {} +
    tar -xzf "$archive" --strip-components=1 --exclude="wordpress/wp-content" -C /var/www/html
  '
}

latest_wordpress_patch_from_json() {
  local line=$1
  jq -er --arg line "$line" '
    [ .offers[]?.version? | strings
      | select(test("^[0-9]+[.][0-9]+[.][0-9]+$"))
      | select((split(".")[0:2] | join(".")) == $line)
    ]
    | unique
    | sort_by(split(".") | map(tonumber))
    | last // empty
  '
}

resolve_latest_wordpress_patch() {
  local line=$1 php_min=$2 response version
  [[ "$line" =~ ^[0-9]+[.][0-9]+$ ]] || fail "WordPress line must be major.minor"
  response=$(curl -fsSL --connect-timeout 5 --max-time 30 \
    "https://api.wordpress.org/core/version-check/1.7/?version=${line}.0&php=${php_min}.0&locale=en_US") \
    || fail "could not resolve the latest stable WordPress patch for ${line} from WordPress.org"
  version=$(printf '%s' "$response" | latest_wordpress_patch_from_json "$line") \
    || fail "WordPress.org returned no stable patch release for WordPress ${line}"
  [[ "$version" =~ ^[0-9]+[.][0-9]+[.][0-9]+$ && "${version%.*}" == "$line" ]] \
    || fail "WordPress.org returned an invalid patch release for WordPress ${line}"
  printf '%s' "$version"
}

php_supported_branches_from_html() {
  local minimum=$1
  awk '/<h3>Currently Supported Versions<\/h3>/ { section=1; next }
       section && /<\/table>/ { exit }
       section { print }' \
    | grep -Eo 'href="/downloads\.php\?version=[0-9]+\.[0-9]+"' \
    | sed -E 's/.*version=([0-9]+\.[0-9]+).*/\1/' \
    | awk -F. -v minimum="$minimum" '
        BEGIN { split(minimum, floor, ".") }
        ($1 + 0) > (floor[1] + 0) || (($1 + 0) == (floor[1] + 0) && ($2 + 0) >= (floor[2] + 0))
      ' \
    | sort -u -t. -k1,1n -k2,2n \
    | paste -sd, -
}

resolve_upstream_php_branches() {
  local minimum=$1 html branches
  html=$(curl -fsSL --connect-timeout 5 --max-time 30 'https://www.php.net/supported-versions.php') \
    || fail "could not read PHP's upstream supported-version list"
  branches=$(printf '%s' "$html" | php_supported_branches_from_html "$minimum") \
    || fail "could not parse PHP's upstream supported-version list"
  [[ -n "$branches" ]] || fail "PHP upstream reports no supported branches at or above PHP ${minimum}"
  printf '%s' "$branches"
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
  local one one_count unique_count ordered lifecycle_summary resolved_wp upstream_php
  one=$(normalise_matrix '7.1' '8.3') || fail "single-cell matrix was rejected"
  one_count=$(printf '%s\n' "$one" | wc -l | tr -d ' ')
  unique_count=$(normalise_matrix '7.1,7.0,7.1' '8.4,8.3,8.3' | wc -l | tr -d ' ')
  ordered=$(normalise_matrix '7.1,7.0' '8.4,8.3' | tr '\n' ' ')
  [[ "$one" == '7.1,8.3' && "$one_count" == 1 ]] || fail "single-cell matrix did not emit exactly one result"
  [[ "$unique_count" == 4 ]] || fail "matrix did not deduplicate exact pairs"
  [[ "$ordered" == '7.0,8.3 7.0,8.4 7.1,8.3 7.1,8.4 ' ]] || fail "matrix result ordering is not stable"
  lifecycle_summary=$(printf '%s\n' '{"active":true,"data":[1]}' '{"active":true,"data":[1]}' | rtk jq -s '{active_preserved:(.[0].active == .[1].active),data_preserved:(.[0].data == .[1].data)}') || fail "runtime-floor result summary has invalid jq syntax"
  printf '%s\n' "$lifecycle_summary" | rtk jq -e '.active_preserved and .data_preserved' >/dev/null || fail "runtime-floor result summary does not preserve comparison results"
  resolved_wp=$(printf '%s' '{"offers":[{"version":"7.1.2"},{"version":"7.0.6"},{"version":"7.0.4"},{"version":"7.2.0"}]}' | latest_wordpress_patch_from_json '7.0') || fail "WordPress patch resolver rejected valid version-check data"
  [[ "$resolved_wp" == '7.0.6' ]] || fail "WordPress patch resolver did not choose the latest patch in the requested line"
  upstream_php=$(printf '%s\n' '<h3>Currently Supported Versions</h3>' '<table>' '<tr><td><a href="/downloads.php?version=8.2">8.2</a></td></tr>' '<tr><td><a href="/downloads.php?version=8.3">8.3</a></td></tr>' '<tr><td><a href="/downloads.php?version=8.4">8.4</a></td></tr>' '</table>' '<h3>Unsupported Branches</h3>' '<a href="/downloads.php?version=9.9">9.9</a>' | php_supported_branches_from_html '8.3') || fail "PHP support-page parser rejected valid official version data"
  [[ "$upstream_php" == '8.3,8.4' ]] || fail "PHP support-page parser included the below-minimum or unsupported branch"
  resolved_matrix_wp=$( (
    runtime_wp_version() { printf '%s.6' "$1"; }
    resolve_matrix_wp_versions $'7.0,8.3\n7.0,8.4\n7.1,8.3\n7.1,8.4'
  ) ) || fail "matrix WordPress resolver rejected valid cells"
  [[ "$resolved_matrix_wp" == $'7.0,7.0.6\n7.1,7.1.6' ]] || fail "matrix did not pin one WordPress patch per release line"
  normalise_matrix '' '8.3' >/dev/null 2>&1 && fail "empty matrix was accepted"
  normalise_matrix '7.1' '8.2' >/dev/null 2>&1 && fail "diagnostic PHP 8.2 was accepted as supported"
  (run_matrix --php-branches 8.3) >/dev/null 2>&1 && fail "matrix accepted missing WordPress lines"
  (run_matrix --wp-lines 7.1) >/dev/null 2>&1 && fail "matrix accepted missing PHP branches"
  (run_matrix --wp-lines ',' --php-branches 8.3) >/dev/null 2>&1 && fail "matrix accepted an invalid normalized pair list"
  normalise_menu_cases 'preferred,index-zero' >/dev/null || fail "valid menu case list was rejected"
  normalise_menu_cases '' >/dev/null 2>&1 && fail "empty menu case list was accepted"
  normalise_menu_cases ',' >/dev/null 2>&1 && fail "blank menu cases were accepted"
  normalise_menu_cases 'preferred,,missing' >/dev/null 2>&1 && fail "menu case list with an empty entry was accepted"
  (run_runtime_floor --fixture tests/compat/fixtures/php-floor-plugin.php --plugin gigpress/gigpress.php --wp-lines 7.1 --supported-php 8.3 --diagnostic-php 8.2) >/dev/null 2>&1 && fail "runtime-floor accepted both targets"
  (run_runtime_floor --plugin unknown/plugin.php --wp-lines 7.1 --supported-php 8.3 --diagnostic-php 8.2) >/dev/null 2>&1 && fail "runtime-floor accepted an unknown real-plugin target"
  grep -q "fixture-activate" "$COMPAT_DIR/probe.php" || fail "fixture lifecycle probe is missing"
  grep -q "real_plugin_inventory" "$COMPAT_DIR/probe.php" || fail "real-plugin inventory contract is missing"
  grep -Fq "if (!in_array(\$purpose, array('real-recover', 'real-low-live'), true))" "$COMPAT_DIR/probe.php" || fail "real-plugin low-floor and recovery probes do not use normal active-plugin bootstrap"
  grep -Fq "update_option('siteurl', 'http://gigpress-compat.test')" "$COMPAT_DIR/probe.php" || fail "fresh installs do not seed a stable site URL for later recovery probes"
  grep -Fq 'menu_trace.menu_warnings | any(' "$COMPAT_DIR/run.sh" || fail "exact-key diagnostic does not require the WordPress warning"
  cleanup_paths=$(grep -v 'cleanup_paths=' "$COMPAT_DIR/run.sh" | grep -Fc 'compose_env down --volumes --remove-orphans')
  [[ "$cleanup_paths" == 4 ]] || fail "all disposable Compose targets must use the configured cleanup environment"
  printf '%s\n' '{"status":"PASS","self_test":"matrix ordering, per-line WordPress patch pinning, upstream PHP support-page parsing, diagnostic exclusion, recovery bootstrap, and Compose cleanup"}'
}

normalise_menu_cases() {
  [[ "${1:-}" =~ ^[^,]+(,[^,]+)*$ ]] || return 1
  printf '%s' "$1"
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
  cases=$(normalise_menu_cases "$cases") || fail "menu-contract requires one or more nonempty --cases entries"
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
$caseList = explode(',', getenv('COMPAT_CASES') ?: '');
$requested = array_values(array_filter($caseList, 'strlen'));
if ($requested === array() || count($requested) !== count($caseList)) {
    throw new RuntimeException('menu-contract requires one or more nonempty cases');
}
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
  printf '%s\n' "$output" | rtk sed -n '1p' | rtk jq -e '.status == "PASS" and .target == "menu-contract" and (.failures | length == 0)' >/dev/null || fail "menu-contract did not satisfy its contract"
}

run_matrix() {
  local wp_lines='' php_branches='' php_supported='' scenario='activation-menu' upgrade_case='' conflict_fixture='' conflict_mode='' conflict_position='' wp_patches='latest' php_min='' error_reporting=''
  local wp_versions='' image_ids=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --wp-lines) wp_lines=${2:-}; shift 2 ;;
      --wp-versions) wp_versions=${2:-}; shift 2 ;;
      --image-ids) image_ids=${2:-}; shift 2 ;;
      --php-branches) php_branches=${2:-}; shift 2 ;;
      --php-supported) php_supported=${2:-}; shift 2 ;;
      --wp-patches) wp_patches=${2:-}; shift 2 ;;
      --php-min) php_min=${2:-}; shift 2 ;;
      --error-reporting) error_reporting=${2:-}; shift 2 ;;
      --scenario) scenario=${2:-}; shift 2 ;;
      --case) upgrade_case=${2:-}; shift 2 ;;
      --conflict-fixture) conflict_fixture=${2:-}; shift 2 ;;
      --conflict-mode) conflict_mode=${2:-}; shift 2 ;;
      --conflict-position) conflict_position=${2:-}; shift 2 ;;
      *) fail "unknown matrix option $1" ;;
    esac
  done
  if [[ -n "$php_supported" ]]; then
    [[ -z "$php_branches" && "$php_supported" == upstream ]] || fail "--php-supported upstream cannot be combined with --php-branches"
  fi
  require_value --wp-lines "$wp_lines"
  [[ -z "$wp_patches" || "$wp_patches" == latest ]] || fail "matrix supports only --wp-patches latest"
  [[ -z "$php_min" || "$php_min" == 8.3 ]] || fail "matrix PHP minimum must be the supported 8.3 floor"
  [[ -z "$error_reporting" || "$error_reporting" == E_ALL ]] || fail "matrix error reporting must be E_ALL"
  if [[ "$php_supported" == upstream ]]; then
    php_branches=$(resolve_upstream_php_branches "${php_min:-8.3}")
  fi
  require_value --php-branches "$php_branches"
  if [[ -n "$conflict_fixture" || -n "$conflict_mode" ]]; then
    [[ "$conflict_fixture" == 'tests/compat/fixtures/menu-conflict-plugin.php' && "$conflict_mode" == order-only ]] || fail "matrix conflict coverage requires the order-only fixture"
    [[ -n "$conflict_position" ]] || conflict_position=before
    [[ "$conflict_position" == before || "$conflict_position" == after ]] || fail "matrix conflict coverage requires a before or after position"
  fi
  [[ "$scenario" == activation-menu || "$scenario" == admin-menu || "$scenario" == csv-roundtrip || "$scenario" == full-workflows || "$scenario" == upgrade-preservation || "$scenario" == administration-workflows || "$scenario" == public-publishing ]] || fail "unsupported matrix scenario: $scenario"
  if [[ "$scenario" == upgrade-preservation || "$scenario" == administration-workflows || "$scenario" == public-publishing ]]; then
    [[ -n "$upgrade_case" ]] || upgrade_case=all
  else
    [[ -z "$upgrade_case" || "$upgrade_case" == tracer-1.4 ]] || fail "--case is only supported by case-based scenarios"
  fi
  if [[ "$scenario" == public-publishing ]]; then
    [[ "$upgrade_case" =~ ^(tracer-1\.4|migrated-contracts|layout-main|layout-compact|override-priority|html-json|rss-contract|ical-contract|empty-contracts|all)$ ]] || fail "unsupported public-publishing case: $upgrade_case"
  fi
  local line branch wp_version pairs resolved_wp_versions matrix_failed=false index
  local -a matrix_wp_lines=() matrix_wp_versions=()
  pairs=$(normalise_matrix "$wp_lines" "$php_branches") || fail "invalid compatibility matrix"
  [[ -n "$pairs" ]] || fail "compatibility matrix is empty"
  if [[ -n "$wp_versions" ]]; then
    resolved_wp_versions=$(printf '%s' "$wp_versions" | tr ';=' '\n,')
  else resolved_wp_versions=$(resolve_matrix_wp_versions "$pairs") || fail "could not resolve WordPress patches for matrix"; fi
  while IFS=, read -r line wp_version; do
    matrix_wp_lines+=("$line")
    matrix_wp_versions+=("$wp_version")
  done <<< "$resolved_wp_versions"
  while IFS=, read -r line branch; do
    wp_version=''
    for ((index=0; index<${#matrix_wp_lines[@]}; index++)); do
      if [[ "${matrix_wp_lines[$index]}" == "$line" ]]; then
        wp_version=${matrix_wp_versions[$index]}
        break
      fi
    done
    [[ -n "$wp_version" ]] || fail "matrix has no pinned WordPress patch for line $line"
    local -a cell_args=(cell --wp "$wp_version" --php "$branch" --scenario "$scenario")
    [[ -z "$image_ids" ]] || cell_args+=(--image-id "$(pinned_value "$image_ids" "$branch")")
    [[ "$scenario" != upgrade-preservation && "$scenario" != administration-workflows && "$scenario" != public-publishing ]] || cell_args+=(--case "$upgrade_case")
    if [[ -n "$conflict_fixture" ]]; then
      cell_args+=(--conflict-fixture "$conflict_fixture" --conflict-mode "$conflict_mode" --conflict-position "$conflict_position")
    fi
    if ! bash "$COMPAT_DIR/run.sh" "${cell_args[@]}" </dev/null; then
      matrix_failed=true
    fi
  done <<< "$pairs"
  [[ "$matrix_failed" == false ]] || fail "matrix cell failed"
}

run_preservation_evidence() {
  local report='' wp_lines='' php_min=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --report) report=${2:-}; shift 2 ;;
      --wp-lines) wp_lines=${2:-}; shift 2 ;;
      --php-min) php_min=${2:-}; shift 2 ;;
      *) fail "unknown preservation-evidence option $1" ;;
    esac
  done
  [[ "$report" == .planning/phases/02-data-and-upgrade-preservation/02-PRESERVATION-MATRIX.md && -f "$ROOT/$report" ]] || fail "preservation evidence report is required"
  [[ "$wp_lines" == '7.0,7.1' ]] || fail "preservation evidence requires WordPress lines 7.0,7.1"
  [[ "$php_min" == '8.3' ]] || fail "preservation evidence requires PHP 8.3 minimum"
  rtk grep -Fq 'reconstructed from repository evidence' "$ROOT/$report" || fail "preservation report omits reconstructed-fixture caveat"
  rtk grep -Fq 'No live backup or live site was tested' "$ROOT/$report" || fail "preservation report overclaims live-site evidence"
  local evidence
  evidence=$(rtk sed -n 's/^<!-- preservation-evidence: \(.*\) -->$/\1/p' "$ROOT/$report")
  [[ $(printf '%s\n' "$evidence" | wc -l | tr -d ' ') == 1 ]] || fail "preservation report must contain exactly one machine evidence record"
  printf '%s\n' "$evidence" | rtk jq -e '
    . as $evidence
    | .schema == "gigpress-preservation-evidence/v1"
    and .support_boundary.php_min == "8.3"
    and (.support_boundary.diagnostic_only_php | index("8.2"))
    and (.wordpress_lines | sort == ["7.0", "7.1"])
    and (.php_branches | length > 0)
    and ([.php_branches[] | test("^[0-9]+[.][0-9]+$")] | all)
    and ([.php_branches[] | select(. == "8.3")] | length == 1)
    and (.php_branches == (.php_branches | unique | sort_by(split(".") | map(tonumber))))
    and ([.commands[] | contains("matrix --scenario upgrade-preservation --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL")] | any)
    and ([.commands[] | contains("matrix --scenario full-workflows --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL")] | any)
    and (.fixtures | sort == ["1.0", "1.1", "1.2", "1.3", "1.4", "1.5", "1.6"])
    and (.requirements["DATA-01"].status == "PASS")
    and (.requirements["DATA-02"].status == "PASS")
    and (.required_case_statuses | keys | sort == ["current-1.6", "entity-guards", "metadata-classification", "optional-request-fields", "safety-1.4", "settings-repeat", "show-lifecycle", "tour-undo", "tracer-1.4", "versions-1.0-1.2", "versions-1.3-1.5"])
    and ([.required_case_statuses[] | . == "PASS"] | all)
    and ((.cells | length) == (2 * (.php_branches | length)))
    and ([.cells[] | .wordpress_line] | unique | sort == ["7.0", "7.1"])
    and (([.cells[] | [.wordpress_line, .php_branch] | join("/")] | unique | length) == (2 * (.php_branches | length)))
    and (([.cells[] | [.wordpress_line, .wordpress_version] | join("/")] | unique | length) == 2)
    and ([.cells[] | .php_branch] | unique | sort_by(split(".") | map(tonumber)) == ($evidence.php_branches | sort_by(split(".") | map(tonumber))))
    and ([.cells[] |
      .status == "PASS"
      and .upgrade_preservation.status == "PASS"
      and .full_workflows.status == "PASS"
      and .warning_count == 0
      and .fatal_count == 0
      and .plugin_error_count == 0
      and .required_case_status == "PASS"
      and (.wordpress_version | test("^[0-9]+[.][0-9]+[.][0-9]+$"))
      and ((.wordpress_version | split(".")[0:2] | join(".")) == .wordpress_line)
      and (.php_version | test("^[0-9]+[.][0-9]+[.][0-9]+$"))
      and ((.php_version | split(".")[0:2] | join(".")) == .php_branch)
      and (.image == ("wordpress:php" + .php_branch + "-apache"))
      and (.image_id | test("^sha256:[0-9a-f]{64}$"))
    ] | all)
  ' >/dev/null || fail "preservation report does not contain complete passing supported evidence"
  printf '{"status":"PASS","report":"%s","wordpress_lines":"%s","php_min":"%s"}\n' "$report" "$wp_lines" "$php_min"
}

pinned_value() {
  local pins=$1 key=$2 value
  value=$(printf '%s' "$pins" | tr ';' '\n' | awk -F= -v key="$key" '$1 == key { print $2 }')
  [[ -n "$value" && "$value" != *$'\n'* ]] || fail "missing or duplicate pinned target $key"
  printf '%s' "$value"
}

# Node only parses records and hashes source; PHP execution stays container-owned.
administration_record() {
  node - "$ROOT" "$@" <<'NODE'
const fs = require('fs'), path = require('path'), crypto = require('crypto'), cp = require('child_process');
const [root, action, report, work] = process.argv.slice(2);
const admin = ['entry-create','entry-recovery','entry-controls','settings-save','settings-sections','list-single','list-navigation','list-bulk'];
const legacy = ['tracer-1.4','safety-1.4','metadata-classification','versions-1.0-1.2','versions-1.3-1.5','current-1.6','settings-repeat','show-lifecycle','optional-request-fields','entity-guards','tour-undo'];
const workflows = ['admin_create_edit_read','public_shortcode','rss','ical','csv_import_export','duplicate_preserved'];
const lintFiles = ['gigpress.php','admin/new.php','admin/handlers.php','admin/settings.php','admin/shows.php','admin/artists.php','admin/venues.php','tests/compat/probe.php','tests/compat/administration-entry.php','tests/compat/administration-settings.php','tests/compat/administration-list.php','tests/compat/upgrade-preservation-crud.php','tests/compat/browser-bootstrap.php'];
const git = (...args) => cp.execFileSync('git', ['-C',root,...args], {maxBuffer: 16*1024*1024});
const hash = data => crypto.createHash('sha256').update(data).digest('hex');
const same = (a,b) => JSON.stringify(a) === JSON.stringify(b);
const exact = (actual,required) => Array.isArray(actual) && actual.length === required.length && same([...actual].sort(), [...required].sort());
const must = (condition,reason) => { if (!condition) throw new Error(reason); };
const sourcePaths = () => git('ls-files','-z').toString().split('\0').filter(f => /\.(php|js|css)$/.test(f) || ['tests/compat/run.sh','tests/compat/compose.yaml','tests/compat/compose.browser.yaml'].includes(f)).sort();
const source = () => ({revision:git('rev-parse','HEAD').toString().trim(), files:sourcePaths().map(file => ({file,sha256:hash(fs.readFileSync(path.join(root,file)))}))});
const read = file => {
  const matches = [...fs.readFileSync(file,'utf8').matchAll(/^<!-- administration-evidence: (.*) -->$/gm)];
  must(matches.length === 1, 'exactly one administration evidence record is required');
  return JSON.parse(matches[0][1]);
};
function validate(e) {
  must(e.schema === 'gigpress-administration-evidence/v1' && e.status === 'PASS','schema/status');
  must(same(e.wordpress_lines,['7.0','7.1']) && e.support_boundary.php_min === '8.3' && same(e.support_boundary.diagnostic_only_php,['8.2']),'support boundary');
  const branches = e.php_branches;
  must(Array.isArray(branches) && branches.length > 0 && branches.includes('8.3') && exact(branches,[...new Set(branches)]) && branches.every(b => /^\d+\.\d+$/.test(b) && (Number(b.split('.')[0]) > 8 || Number(b.split('.')[0]) === 8 && Number(b.split('.')[1]) >= 3)), 'supported PHP branches');
  must(e.resolution.wordpress_url === 'https://api.wordpress.org/core/version-check/1.7/' && e.resolution.php_url === 'https://www.php.net/supported-versions.php' && !isNaN(Date.parse(e.resolution.resolved_at)), 'target resolution provenance');
  must(exact(Object.keys(e.resolution.wordpress_versions),e.wordpress_lines) && exact(Object.keys(e.resolution.images),branches) && exact(Object.keys(e.resolution.php_versions),branches),'resolved target coverage');
  must(exact(e.required_cases,admin) && exact(e.preservation_required_cases,legacy), 'required case registry');
  must(/^\w{40}$/.test(e.source.revision) && /^[0-9a-f]{40}$/.test(e.source.revision), 'source revision');
  git('merge-base','--is-ancestor',e.source.revision,'HEAD');
  const current = source();
  must(same(e.source.files,current.files),'stale source fingerprint');
  must(exact(e.source.files.map(f=>f.file),sourcePaths()),'source file set');
  git('diff','--quiet',e.source.revision,'--',...e.source.files.map(f=>f.file));
  const verifiedRevisions = new Set([e.source.revision]);
  must(Array.isArray(e.commands) && ['administration-workflows','upgrade-preservation','full-workflows'].every(s=>e.commands.some(c=>c.includes(`--scenario ${s}`) && c.includes('--wp-versions ') && c.includes('--image-ids '))), 'pinned matrix commands');
  must(Number.isFinite(e.elapsed_seconds) && e.elapsed_seconds > 0, 'matrix duration');
  must(e.browser_acceptance === 'separate-observations-required' && e.migrated_public_csv === 'not-certified' && same(e.review_items,['ADMIN-03/unclassified','three-descriptorless-product-prohibitions']), 'honest acceptance boundaries');
  must(e.lint.status === 'PASS' && exact(e.lint.files,lintFiles) && exact(e.lint.branches,branches) && exact(e.lint.cells.map(c=>c.php_branch),branches), 'lint coverage');
  must(e.lint.cells.every(c=>c.status === 'PASS' && c.image_id === e.resolution.images[c.php_branch] && Number.isInteger(c.file_count) && c.file_count === lintFiles.length), 'pinned lint identities');
  must(Array.isArray(e.cells) && e.cells.length === branches.length*2, 'runtime cell count');
  must(exact(e.cells.map(c=>c.wordpress_line+'/'+c.php_branch), e.wordpress_lines.flatMap(w=>branches.map(p=>w+'/'+p))), 'duplicate/missing runtime cell');
  let assertions = 0;
  for (const cell of e.cells) {
    must(cell.status === 'PASS' && Number.isFinite(cell.elapsed_seconds) && cell.elapsed_seconds > 0,'cell status/duration');
    must(cell.wordpress_version === e.resolution.wordpress_versions[cell.wordpress_line] && /^\d+\.\d+\.\d+$/.test(cell.wordpress_version) && cell.wordpress_version.startsWith(cell.wordpress_line+'.'), 'pinned WordPress patch');
    must(/^\d+\.\d+\.\d+$/.test(cell.php_version) && cell.php_version.startsWith(cell.php_branch+'.') && cell.php_version === e.resolution.php_versions[cell.php_branch], 'exact pinned PHP patch');
    must(cell.image === `wordpress:php${cell.php_branch}-apache` && /^sha256:[0-9a-f]{64}$/.test(cell.image_id) && cell.image_id === e.resolution.images[cell.php_branch], 'pinned image');
    for (const scenario of ['administration','preservation','workflows']) {
      const result = cell[scenario];
      must(result.status === 'PASS' && result.plugin_active === true && result.fatal === null && Array.isArray(result.plugin_errors) && result.plugin_errors.length === 0 && Array.isArray(result.menu_warnings) && result.menu_warnings.length === 0,scenario+' zero-error active plugin');
      must(result.wordpress_version === cell.wordpress_version && result.php_version === cell.php_version && result.image === cell.image && result.image_id === cell.image_id && /^[0-9a-f]{40}$/.test(result.source_revision), scenario+' runtime/source identity');
      if (!verifiedRevisions.has(result.source_revision)) {
        git('merge-base','--is-ancestor',result.source_revision,'HEAD');
        git('diff','--quiet',result.source_revision,'--',...e.source.files.map(f=>f.file));
        verifiedRevisions.add(result.source_revision);
      }
      must(result.elapsed_seconds > 0, scenario+' duration');
    }
    const a = cell.administration.administration_workflows;
    must(a.status === 'PASS' && a.case === 'all' && exact(a.required_cases,admin) && exact(a.cases.map(c=>c.case),admin),'administration exact cases');
    for (const c of a.cases) {
      must(c.status === 'PASS' && c.plugin_active === true && c.ready === true && c.checks && !Array.isArray(c.checks) && typeof c.checks === 'object', 'administration ready case');
      const checks = Object.entries(c.checks);
      must(checks.length > 0 && checks.every(([name,value])=>name.length>0 && value === true) && Number.isInteger(c.assertion_count) && c.assertion_count === checks.length,'positive named checks');
      must(c.warning_count === 0 && c.fatal_count === 0 && c.plugin_error_count === 0 && Number.isFinite(c.elapsed_seconds) && c.elapsed_seconds >= 0, 'case zero errors/timing');
      assertions += c.assertion_count;
    }
    const p = cell.preservation.upgrade_preservation;
    must(p.status === 'PASS' && p.case === 'all' && p.ready === true && p.plugin_active === true && exact(p.required_cases,legacy) && exact(p.cases.map(c=>c.case),legacy),'preservation exact eleven cases');
    must(p.cases.every(c=>c.status === 'PASS' && c.ready === true && c.plugin_active === true && c.warning_count === 0 && c.fatal_count === 0 && c.plugin_error_count === 0 && typeof c.fixture === 'string' && c.fixture.startsWith('reconstructed-')), 'preservation readiness/errors/fixture');
    const f = cell.workflows.full_workflows;
    must(f.status === 'PASS' && workflows.every(name=>f[name] === true),'fresh full workflows');
  }
  must(assertions === e.assertion_count && assertions > 0,'aggregate assertion count');
  return {status:'PASS',schema:e.schema,cells:e.cells.length,administration_cases:admin.length,assertion_count:assertions,preservation_cases:legacy.length,warnings:0,fatals:0,plugin_errors:0,source_revision:e.source.revision};
}
function write(file,e) {
  const rows=e.cells.map(c=>`| ${c.wordpress_version} | ${c.php_version} | ${c.image_id} | ${c.administration.administration_workflows.cases.reduce((n,c)=>n+c.assertion_count,0)} / 8 | 11 PASS | PASS | 0 / 0 / 0 | ${c.elapsed_seconds} |`).join('\n');
  fs.writeFileSync(file,`# Phase 03 Administration Matrix\n\nSource revision: \`${e.source.revision}\`\n\nResolved once: ${e.resolution.resolved_at}. WordPress patches and immutable official image IDs were pinned across all three matrices and container PHP lint. PHP 8.2 is diagnostic only and excluded.\n\n| WordPress | PHP | Official image ID | Administration assertions / cases | Preservation cases | Fresh workflows | Warnings / fatals / plugin errors | Cell seconds |\n|---|---|---|---|---|---|---|---|\n${rows}\n\nFull build: ${e.elapsed_seconds} seconds; ${e.assertion_count} administration assertions across ${e.cells.length} supported cells. Preservation fixtures are reconstructed from repository evidence. No live backup or live site was tested. Full workflows use fresh synthetic fixtures; migrated public/CSV integration belongs to Phases 04/05.\n\nBrowser acceptance requires separate actual observations in 03-BROWSER.md. This matrix does not certify native picker, keyboard, disabled-JS or assistive announcements. ADMIN-03/unclassified and all three descriptor-less product prohibitions remain unresolved/flagged-unverified for downstream review.\n\n## Exact commands\n\n${e.commands.map(c=>'\x60'+c+'\x60').join('\n\n')}\n\n## Machine evidence\n\n<!-- administration-evidence: ${JSON.stringify(e)} -->\n`);
}
try {
  if (action === 'snapshot') { fs.writeFileSync(report,JSON.stringify(source())); }
  else if (action === 'render') {
    const e=JSON.parse(fs.readFileSync(path.join(work,'meta.json'),'utf8'));
    e.source=JSON.parse(fs.readFileSync(path.join(work,'source.json'),'utf8'));
    e.cells=[]; e.assertion_count=0;
    for (const w of e.wordpress_lines) for (const p of e.php_branches) {
      const wp=e.resolution.wordpress_versions[w];
      const get=s=>JSON.parse(fs.readFileSync(path.join(work,`${wp}-php${p}-${s}.json`),'utf8'));
      const a=get('administration-workflows'), preservation=get('upgrade-preservation'), workflows=get('full-workflows');
      const cell={wordpress_line:w,wordpress_version:wp,php_branch:p,php_version:a.php_version,image:a.image,image_id:a.image_id,status:'PASS',administration:a,preservation,workflows,elapsed_seconds:a.elapsed_seconds+preservation.elapsed_seconds+workflows.elapsed_seconds};
      e.assertion_count+=a.administration_workflows.cases.reduce((n,c)=>n+c.assertion_count,0); e.cells.push(cell);
    }
    e.required_cases=admin; e.preservation_required_cases=legacy;
    e.lint={status:'PASS',files:lintFiles,branches:e.php_branches,cells:e.php_branches.map(p=>({php_branch:p,image_id:e.resolution.images[p],file_count:lintFiles.length,status:'PASS'}))};
    const verdict=validate(e); write(report,e); console.log(JSON.stringify(verdict));
  } else if (action === 'validate') console.log(JSON.stringify(validate(read(report))));
  else if (action === 'self-test') {
    const control=read(report); validate(control);
    const corruptions={
      missing_case:e=>e.cells[0].administration.administration_workflows.cases.pop(),
      duplicate_case:e=>e.cells[0].administration.administration_workflows.cases[1]=e.cells[0].administration.administration_workflows.cases[0],
      empty_checks:e=>{e.cells[0].administration.administration_workflows.cases[0].checks={};e.cells[0].administration.administration_workflows.cases[0].assertion_count=0;},
      failed_check:e=>{const c=e.cells[0].administration.administration_workflows.cases[0];c.checks[Object.keys(c.checks)[0]]=false;},
      failed_case:e=>e.cells[0].administration.administration_workflows.cases[0].status='FAIL',
      warning:e=>e.cells[0].administration.administration_workflows.cases[0].warning_count=1,
      fatal:e=>e.cells[0].administration.fatal={message:'synthetic corruption'},
      plugin_error:e=>e.cells[0].administration.plugin_errors.push({message:'synthetic corruption'}),
      missing_cell:e=>e.cells.pop(), duplicate_cell:e=>e.cells[1]=e.cells[0],
      stale_source:e=>e.source.files[0].sha256='0'.repeat(64),
      foreign_revision:e=>e.source.revision='0'.repeat(40),
      altered_wp:e=>e.cells[0].wordpress_version='7.0.0',
      altered_php:e=>e.cells[0].php_version='8.2.0',
      altered_image:e=>e.cells[0].image_id='sha256:'+'0'.repeat(64),
      missing_runtime:e=>delete e.cells[0].php_version,
      preservation_missing:e=>e.cells[0].preservation.upgrade_preservation.cases.pop(),
      workflows_failed:e=>e.cells[0].workflows.full_workflows.csv_import_export=false,
      lint_missing:e=>e.lint.files.pop(),
      browser_overclaim:e=>e.browser_acceptance='PASS'
    };
    const temp=fs.mkdtempSync(path.join(require('os').tmpdir(),'gigpress-admin-evidence-')); fs.chmodSync(temp,0o700);
    const checks={clean_control:true};
    try {
      for (const [name,mutate] of Object.entries(corruptions)) {
        const e=JSON.parse(JSON.stringify(control)); mutate(e);
        const file=path.join(temp,name+'.md'); write(file,e); fs.chmodSync(file,0o600);
        const result=cp.spawnSync('bash',[path.join(root,'tests/compat/run.sh'),'administration-evidence','--action','validate','--report',file,'--wp-lines','7.0,7.1','--php-min','8.3'],{env:{...process.env,COMPAT_PRIVATE_EVIDENCE_DIR:temp},encoding:'utf8'});
        must(result.status !== null && result.status !== 0 && result.stderr.includes('invalid administration evidence'), 'corruption accepted or validator did not run: '+name);
        checks[name]=true;
      }
    } finally { fs.rmSync(temp,{recursive:true,force:true}); }
    console.log(JSON.stringify({status:'PASS',assertion_count:Object.keys(checks).length,checks}));
  } else throw new Error('unknown evidence record action');
} catch(error) { console.error('compat runner: invalid administration evidence: '+error.message); process.exit(2); }
NODE
}

run_administration_evidence() {
  local action='' report='' wp_lines='' php_min=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --action) action=${2:-}; shift 2 ;;
      --report) report=${2:-}; shift 2 ;;
      --wp-lines) wp_lines=${2:-}; shift 2 ;;
      --php-min) php_min=${2:-}; shift 2 ;;
      *) fail "unknown administration-evidence option $1" ;;
    esac
  done
  [[ "$action" == build || "$action" == validate || "$action" == self-test ]] || fail "administration evidence action must be build, validate or self-test"
  [[ "$wp_lines" == 7.0,7.1 && "$php_min" == 8.3 ]] || fail "administration evidence requires WordPress 7.0,7.1 and PHP 8.3 minimum"
  if [[ "$report" == .planning/phases/03-administration-workflows/03-ADMIN-MATRIX.md ]]; then report="$ROOT/$report";
  elif [[ "$action" == validate && -n "${COMPAT_PRIVATE_EVIDENCE_DIR:-}" && "$report" == "$COMPAT_PRIVATE_EVIDENCE_DIR/"*.md && -d "$COMPAT_PRIVATE_EVIDENCE_DIR" && ! -L "$COMPAT_PRIVATE_EVIDENCE_DIR" && "$(stat -f '%Lp' "$COMPAT_PRIVATE_EVIDENCE_DIR")" == 700 ]]; then :;
  else fail "administration evidence report is required"; fi
  if [[ "$action" != build ]]; then
    [[ -f "$report" ]] || fail "administration evidence report is required"
    administration_record "$action" "$report"
    return
  fi
  local started resolved branches pairs wp_pins='' image_pins='' php_pins='' line wp branch image image_id php_patch scenario work duration
  started=$(date +%s); resolved=$(date -u +%Y-%m-%dT%H:%M:%SZ)
  branches=$(resolve_upstream_php_branches 8.3)
  pairs=$(normalise_matrix "$wp_lines" "$branches")
  while IFS=, read -r line wp; do wp_pins+="${wp_pins:+;}$line=$wp"; done <<< "$(resolve_matrix_wp_versions "$pairs")"
  IFS=',' read -r -a supported <<< "$branches"
  for branch in "${supported[@]}"; do
    image="wordpress:php${branch}-apache"
    docker pull "$image" >/dev/null || fail "could not resolve official image $image"
    image_id=$(docker image inspect --format '{{.Id}}' "$image")
    [[ "$image_id" =~ ^sha256:[0-9a-f]{64}$ ]] || fail "invalid official image identity"
    image_pins+="${image_pins:+;}$branch=$image_id"
    php_patch=$(docker run --rm --network none "$image_id" php -r 'echo PHP_VERSION;')
    [[ "$php_patch" == "$branch."* && "$php_patch" =~ ^[0-9]+[.][0-9]+[.][0-9]+$ ]] || fail "official image does not match PHP branch $branch"
    php_pins+="${php_pins:+;}$branch=$php_patch"
  done
  work=$(mktemp -d "${TMPDIR:-/tmp}/gigpress-admin-build.XXXXXX"); chmod 700 "$work"
  trap 'rm -rf "$work"' EXIT
  administration_record snapshot "$work/source.json"
  printf 'Resolved WordPress: %s; PHP branches: %s\n' "$wp_pins" "$branches"
  local lint_files='gigpress.php,admin/new.php,admin/handlers.php,admin/settings.php,admin/shows.php,admin/artists.php,admin/venues.php,tests/compat/probe.php,tests/compat/administration-entry.php,tests/compat/administration-settings.php,tests/compat/administration-list.php,tests/compat/upgrade-preservation-crud.php,tests/compat/browser-bootstrap.php'
  bash "$COMPAT_DIR/run.sh" lint --php-branches "$branches" --image-ids "$image_pins" --files "$lint_files" >"$work/lint.log" 2>&1 || { tail -n 25 "$work/lint.log" >&2; fail "administration supported PHP lint failed"; }
  printf 'Pinned PHP lint PASS: %s files per branch\n' 13
  for scenario in administration-workflows upgrade-preservation full-workflows; do
    local -a args=(matrix --wp-lines "$wp_lines" --php-branches "$branches" --wp-versions "$wp_pins" --image-ids "$image_pins" --php-min 8.3 --error-reporting E_ALL --scenario "$scenario")
    [[ "$scenario" == full-workflows ]] || args+=(--case all)
    printf 'Running pinned %s matrix\n' "$scenario"
    if ! bash "$COMPAT_DIR/run.sh" "${args[@]}" >"$work/$scenario.log" 2>&1; then tail -c 6000 "$work/$scenario.log" >&2; fail "$scenario supported matrix failed"; fi
    while IFS=, read -r line branch; do
      wp=$(pinned_value "$wp_pins" "$line")
      cp "$RESULT_DIR/${wp}-php${branch}-${scenario}.json" "$work/"
    done <<< "$pairs"
    printf '%s PASS: %s cells\n' "$scenario" "$(printf '%s\n' "$pairs" | wc -l | tr -d ' ')"
  done
  duration=$(($(date +%s)-started))
  jq -n --arg branches "$branches" --arg wp_pins "$wp_pins" --arg image_pins "$image_pins" --arg php_pins "$php_pins" --arg resolved "$resolved" --argjson duration "$duration" '
    {schema:"gigpress-administration-evidence/v1",status:"PASS",wordpress_lines:["7.0","7.1"],php_branches:($branches|split(",")),support_boundary:{php_min:"8.3",diagnostic_only_php:["8.2"]},resolution:{wordpress_url:"https://api.wordpress.org/core/version-check/1.7/",php_url:"https://www.php.net/supported-versions.php",resolved_at:$resolved,wordpress_versions:($wp_pins|split(";")|map(split("=")|{key:.[0],value:.[1]})|from_entries),images:($image_pins|split(";")|map(split("=")|{key:.[0],value:.[1]})|from_entries),php_versions:($php_pins|split(";")|map(split("=")|{key:.[0],value:.[1]})|from_entries)},elapsed_seconds:$duration,browser_acceptance:"separate-observations-required",migrated_public_csv:"not-certified",review_items:["ADMIN-03/unclassified","three-descriptorless-product-prohibitions"],commands:(["administration-workflows","upgrade-preservation","full-workflows"]|map("rtk proxy bash tests/compat/run.sh matrix --scenario "+.+(if . == "full-workflows" then "" else " --case all" end)+" --wp-lines 7.0,7.1 --php-branches "+$branches+" --wp-versions \u0027"+$wp_pins+"\u0027 --image-ids \u0027"+$image_pins+"\u0027 --php-min 8.3 --error-reporting E_ALL"))}' >"$work/meta.json"
  administration_record render "$report" "$work"
  rm -rf "$work"; trap - EXIT
}

# Public evidence is parser-validated locally; the build path pins and executes
# the exact supported matrix before it records any passing case.
public_evidence_record() {
  node - "$ROOT" "$@" <<'NODE'
const fs = require('fs'), path = require('path'), crypto = require('crypto'), cp = require('child_process');
const [root, action, report, work] = process.argv.slice(2);
const required = ['tracer-1.4','migrated-contracts','layout-main','layout-compact','override-priority','html-json','rss-contract','ical-contract','empty-contracts'];
const git = (...args) => cp.execFileSync('git', ['-C',root,...args], {maxBuffer: 32*1024*1024});
const hash = data => crypto.createHash('sha256').update(data).digest('hex');
const same = (a,b) => JSON.stringify(a) === JSON.stringify(b);
const exact = (a,b) => Array.isArray(a) && same(a,b);
const must = (condition,reason) => { if (!condition) throw new Error(reason); };
const sourcePaths = () => git('ls-files','-z').toString().split('\0').filter(f => /\.(php|js|css)$/.test(f) || ['tests/compat/run.sh','tests/compat/compose.yaml','tests/compat/compose.browser.yaml'].includes(f)).sort();
const sourceSnapshot = () => ({revision:git('rev-parse','HEAD').toString().trim(),files:sourcePaths().map(file=>({file,sha256:hash(fs.readFileSync(path.join(root,file)))}))});
const read = file => {
  const text=fs.readFileSync(file,'utf8');
  const matches=[...text.matchAll(/^<!-- public-evidence: (.*) -->$/gm)];
  must(matches.length===1,'exactly one public evidence record is required');
  return JSON.parse(matches[0][1]);
};
function validate(e) {
  must(e.schema==='gigpress-public-evidence/v1' && e.status==='PASS','schema/status');
  must(exact(e.wordpress_lines,['7.0','7.1']) && e.php_min==='8.3' && exact(e.diagnostic_only_php,['8.2']),'support boundary');
  must(Array.isArray(e.php_branches) && e.php_branches.length>0 && e.php_branches.includes('8.3') && exact(e.php_branches,[...new Set(e.php_branches)].sort((a,b)=>a.localeCompare(b,undefined,{numeric:true}))) && e.php_branches.every(v=>/^\d+\.\d+$/.test(v) && (Number(v.split('.')[0])>8 || Number(v.split('.')[0])===8 && Number(v.split('.')[1])>=3)),'supported PHP branches');
  must(e.resolution.wordpress_url==='https://api.wordpress.org/core/version-check/1.7/' && e.resolution.php_url==='https://www.php.net/supported-versions.php' && !isNaN(Date.parse(e.resolution.resolved_at)),'target resolution provenance');
  must(exact(Object.keys(e.resolution.wordpress_versions).sort(),e.wordpress_lines.slice().sort()) && exact(Object.keys(e.resolution.php_versions).sort(),e.php_branches.slice().sort()) && exact(Object.keys(e.resolution.images).sort(),e.php_branches.slice().sort()),'resolved target coverage');
  for (const line of e.wordpress_lines) must(new RegExp('^'+line.replace('.','\\.')+'\\.\\d+$').test(e.resolution.wordpress_versions[line]),'pinned WordPress patch '+line);
  for (const branch of e.php_branches) {
    must(new RegExp('^'+branch.replace('.','\\.')+'\\.\\d+$').test(e.resolution.php_versions[branch]),'pinned PHP patch '+branch);
    must(/^sha256:[0-9a-f]{64}$/.test(e.resolution.images[branch]),'immutable official image '+branch);
  }
  must(exact(e.required_cases,required),'exact public case registry');
  must(/^[0-9a-f]{40}$/.test(e.source.revision) && Array.isArray(e.source.files) && e.source.files.length>0,'source revision/files');
  git('merge-base','--is-ancestor',e.source.revision,'HEAD');
  const current=sourceSnapshot();
  must(same(e.source.files,current.files),'stale or incorrect source fingerprint');
  must(exact(e.source.files.map(f=>f.file),sourcePaths()),'source file set');
  git('diff','--quiet',e.source.revision,'--',...e.source.files.map(f=>f.file));
  must(Number.isFinite(e.elapsed_seconds) && e.elapsed_seconds>0,'matrix duration');
  must(Array.isArray(e.commands) && e.commands.some(c=>c.includes('matrix --scenario public-publishing --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL')),'requested public matrix command');
  must(e.acceptance.phase_accepted===false && e.acceptance.nyquist_compliant===false && e.acceptance.final_source_browser_calendar==='pending-blocking-human-plan-04-05','phase/browser acceptance boundary');
  must(e.browser_evidence.status==='pending' && e.browser_evidence.source_revision===e.source.revision && e.browser_evidence.http_is_not_browser===true && e.browser_evidence.blocks_acceptance===true,'browser and HTTP evidence separation');
  must(Array.isArray(e.cells) && e.cells.length===e.wordpress_lines.length*e.php_branches.length,'runtime cell count');
  const pairs=e.wordpress_lines.flatMap(w=>e.php_branches.map(p=>w+'/'+p));
  must(exact(e.cells.map(c=>c.wordpress_line+'/'+c.php_branch),pairs),'duplicate, missing, or reordered runtime cell');
  let assertions=0;
  for (const cell of e.cells) {
    must(cell.status==='PASS' && Number.isFinite(cell.elapsed_seconds) && cell.elapsed_seconds>0,'cell status/duration');
    must(cell.wordpress_version===e.resolution.wordpress_versions[cell.wordpress_line] && cell.wordpress_version.startsWith(cell.wordpress_line+'.'),'WordPress runtime identity');
    must(cell.php_version===e.resolution.php_versions[cell.php_branch] && cell.php_version.startsWith(cell.php_branch+'.'),'PHP runtime identity');
    must(cell.image===`wordpress:php${cell.php_branch}-apache` && cell.image_id===e.resolution.images[cell.php_branch],'container image identity');
    must(cell.source_revision===e.source.revision,'cell source revision');
    must(cell.plugin_active===true && cell.fatal===null && cell.warning_count===0 && cell.fatal_count===0 && cell.plugin_error_count===0,'cell runtime errors');
    const p=cell.public_publishing;
    must(p.status==='PASS' && p.case==='all' && exact(p.required_cases,required) && exact(p.cases.map(c=>c.case),required),'public aggregate exact case set');
    for (const c of p.cases) {
      must(c.status==='PASS' && c.case && c.checks && typeof c.checks==='object' && !Array.isArray(c.checks),'public case status/assertions');
      const checks=Object.entries(c.checks);
      must(checks.length>0 && checks.every(([name,value])=>name.length>0 && value===true) && Number.isInteger(c.assertion_count) && c.assertion_count===checks.length && c.output_empty===true && c.checks_boolean===true,'positive named public checks');
      assertions+=c.assertion_count;
    }
    const migrated=p.cases.find(c=>c.case==='migrated-contracts');
    const rss=p.cases.find(c=>c.case==='rss-contract');
    const ical=p.cases.find(c=>c.case==='ical-contract');
    must(migrated.checks.all_read_snapshots_unchanged===true && rss.checks.rss_public_reads_leave_migrated_rows_settings_schema_and_links_unchanged===true && ical.checks.ical_public_reads_leave_migrated_rows_settings_schema_and_links_unchanged===true,'unchanged migrated snapshots');
  }
  must(assertions===e.assertion_count && assertions>0,'positive aggregate assertion count');
  return {status:'PASS',schema:e.schema,cells:e.cells.length,cases_per_cell:required.length,assertion_count:assertions,warnings:0,fatals:0,plugin_errors:0,source_revision:e.source.revision,browser_acceptance:'pending-blocking-human-plan-04-05'};
}
function write(file,e) {
  const rows=e.cells.map(c=>`| ${c.wordpress_version} | ${c.php_version} | ${c.image_id} | ${c.public_publishing.assertion_count} / 9 | PASS | 0 / 0 / 0 | ${c.elapsed_seconds} |`).join('\n');
  const matrix=e.cells.map(c=>`| ${c.wordpress_line} | ${c.wordpress_version} | ${c.php_branch} | ${c.php_version} | ${c.image} | ${c.image_id} | ${c.public_publishing.assertion_count} | ${c.elapsed_seconds} |`).join('\n');
  const body=`# Phase 04 Public Publishing Matrix\n\nSource revision: \`${e.source.revision}\`\n\nResolved once at ${e.resolution.resolved_at}. This report binds the complete nine-case public contract to WordPress 7.0/7.1, every PHP branch upstream-supported at or above 8.3, and the immutable WordPress/PHP image IDs shown below. Each row reports the aggregate named assertion count across all nine cases.\n\n## Supported runtime matrix\n\n| WordPress line | WordPress patch | PHP branch | PHP patch | Official image | Image ID | Named checks across 9 cases | Cell seconds |\n|---|---:|---:|---:|---|---|---:|---:|\n${matrix}\n\nBuild duration: ${e.elapsed_seconds} seconds across ${e.cells.length} pinned runtime cells and ${e.assertion_count} named assertions. Warnings: 0; fatals: 0; plugin errors: 0. Every case reported PASS with nonempty named true checks. Migrated source rows, settings, schema, and related links remained unchanged across the migrated, RSS, and iCalendar reads.\n\n## Acceptance boundary\n\nAutomated HTTP and parser evidence is complete for this source revision. It does not claim browser or calendar-client observations. Final-source 320 CSS-pixel and wide-layout behavior, theme inheritance, keyboard access, disabled-JavaScript access, and a real calendar-client import remain **PENDING and BLOCKING** under Plan 04-05. Phase acceptance remains false and Nyquist compliance remains false until that Plan 04-05 gate is completed.\n\n## Commands\n\n${e.commands.map(c=>'`'+c+'`').join('\n\n')}\n\n## Per-cell summary\n\n| WordPress | PHP | Image ID | Assertions / cases | Result | Warnings / fatals / plugin errors | Seconds |\n|---|---|---|---:|---|---|---:|\n${rows}\n\n## Machine evidence\n\n<!-- public-evidence: ${JSON.stringify(e)} -->\n`;
  fs.writeFileSync(file,body);
}
try {
  if (action==='snapshot') fs.writeFileSync(report,JSON.stringify(sourceSnapshot()));
  else if (action==='render') {
    const e=JSON.parse(fs.readFileSync(path.join(work,'meta.json'),'utf8'));
    e.source=JSON.parse(fs.readFileSync(path.join(work,'source.json'),'utf8')); e.cells=[]; e.assertion_count=0;
    e.browser_evidence.source_revision=e.source.revision;
    for (const line of e.wordpress_lines) for (const branch of e.php_branches) {
      const wp=e.resolution.wordpress_versions[line];
      const file=path.join(work,`${wp}-php${branch}-public-publishing.json`);
      const runtime=JSON.parse(fs.readFileSync(file,'utf8'));
      const p=runtime.public_publishing;
      const caseAssertions=p.cases.reduce((n,c)=>n+c.assertion_count,0);
      const warnings=Array.isArray(runtime.menu_warnings)?runtime.menu_warnings.length:-1;
      const fatalCount=runtime.fatal===null?0:1;
      const pluginErrors=Array.isArray(runtime.plugin_errors)?runtime.plugin_errors.length:-1;
      const cell={wordpress_line:line,wordpress_version:wp,php_branch:branch,php_version:runtime.php_version,image:runtime.image,image_id:runtime.image_id,status:runtime.status==='PASS'&&p.status==='PASS'?'PASS':'FAIL',elapsed_seconds:runtime.elapsed_seconds,source_revision:runtime.source_revision,plugin_active:runtime.plugin_active,fatal:runtime.fatal,warning_count:warnings,fatal_count:fatalCount,plugin_error_count:pluginErrors,public_publishing:{...p,assertion_count:caseAssertions},public_snapshot_evidence:{migrated:p.cases.find(c=>c.case==='migrated-contracts')?.checks?.all_read_snapshots_unchanged===true,rss:p.cases.find(c=>c.case==='rss-contract')?.checks?.rss_public_reads_leave_migrated_rows_settings_schema_and_links_unchanged===true,ical:p.cases.find(c=>c.case==='ical-contract')?.checks?.ical_public_reads_leave_migrated_rows_settings_schema_and_links_unchanged===true}};
      e.assertion_count+=caseAssertions; e.cells.push(cell);
    }
    const verdict=validate(e); write(report,e); console.log(JSON.stringify(verdict));
  } else if (action==='validate') console.log(JSON.stringify(validate(read(report))));
  else if (action==='self-test') {
    const control=read(report); validate(control);
    const corruptions={
      missing_case:e=>e.cells[0].public_publishing.cases.pop(),
      duplicate_case:e=>e.cells[0].public_publishing.cases[1]=e.cells[0].public_publishing.cases[0],
      unknown_case:e=>e.cells[0].public_publishing.cases[0].case='unknown-public-case',
      empty_checks:e=>{const c=e.cells[0].public_publishing.cases[0];c.checks={};c.assertion_count=0;},
      failed_check:e=>{const c=e.cells[0].public_publishing.cases[0];c.checks[Object.keys(c.checks)[0]]=false;},
      failed_case:e=>e.cells[0].public_publishing.cases[0].status='FAIL',
      stale_source_hash:e=>e.source.files[0].sha256='0'.repeat(64),
      foreign_source_revision:e=>e.source.revision='0'.repeat(40),
      wrong_wordpress_patch:e=>e.cells[0].wordpress_version='7.0.0',
      wrong_php_patch:e=>e.cells[0].php_version='8.2.0',
      wrong_php_branch:e=>e.cells[0].php_branch='8.2',
      wrong_image:e=>e.cells[0].image_id='sha256:'+'0'.repeat(64),
      missing_runtime:e=>delete e.cells[0].php_version,
      missing_cell:e=>e.cells.pop(), duplicate_cell:e=>e.cells[1]=e.cells[0],
      migrated_snapshot_changed:e=>e.cells[0].public_publishing.cases.find(c=>c.case==='migrated-contracts').checks.all_read_snapshots_unchanged=false,
      empty_failed_result:e=>e.cells[0].public_publishing.cases.find(c=>c.case==='rss-contract').checks={},
      missing_duration:e=>e.cells[0].elapsed_seconds=0,
      nonzero_warning:e=>e.cells[0].warning_count=1,
      browser_http_overclaim:e=>{e.browser_evidence.status='PASS';e.browser_evidence.http_is_not_browser=false;},
      phase_accepted_too_early:e=>e.acceptance.phase_accepted=true
    };
    const temp=fs.mkdtempSync(path.join(require('os').tmpdir(),'gigpress-public-evidence-')); fs.chmodSync(temp,0o700);
    const checks={clean_control:true};
    try {
      for (const [name,mutate] of Object.entries(corruptions)) {
        const e=JSON.parse(JSON.stringify(control)); mutate(e);
        const file=path.join(temp,name+'.md'); fs.writeFileSync(file,`# Corrupted test record\n\n<!-- public-evidence: ${JSON.stringify(e)} -->\n`); fs.chmodSync(file,0o600);
        const result=cp.spawnSync('bash',[path.join(root,'tests/compat/run.sh'),'public-evidence','--action','validate','--report',file,'--wp-lines','7.0,7.1','--php-min','8.3'],{env:{...process.env,COMPAT_PRIVATE_EVIDENCE_DIR:temp},encoding:'utf8'});
        must(result.status!==null && result.status!==0 && result.stderr.includes('invalid public evidence'),'corruption accepted or validator did not run: '+name);
        checks[name]=true;
      }
    } finally { fs.rmSync(temp,{recursive:true,force:true}); }
    must(Object.keys(checks).length===Object.keys(corruptions).length+1,'named corruption set is not complete');
    console.log(JSON.stringify({status:'PASS',assertion_count:Object.keys(checks).length,checks}));
  } else throw new Error('unknown public evidence action');
} catch(error) { console.error('compat runner: invalid public evidence: '+error.message); process.exit(2); }
NODE
}

run_public_evidence() {
  local action='' report='' wp_lines='' php_min=''
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --action) action=${2:-}; shift 2 ;;
      --report) report=${2:-}; shift 2 ;;
      --wp-lines) wp_lines=${2:-}; shift 2 ;;
      --php-min) php_min=${2:-}; shift 2 ;;
      *) fail "unknown public-evidence option $1" ;;
    esac
  done
  [[ "$action" == build || "$action" == validate || "$action" == self-test ]] || fail "public-evidence action must be build, validate or self-test"
  [[ "$wp_lines" == '7.0,7.1' && "$php_min" == 8.3 ]] || fail "public evidence requires WordPress 7.0,7.1 and PHP 8.3 minimum"
  if [[ "$report" == .planning/phases/04-public-publishing/04-PUBLIC-MATRIX.md ]]; then report="$ROOT/$report";
  elif [[ "$action" != build && -n "${COMPAT_PRIVATE_EVIDENCE_DIR:-}" && "$report" == "$COMPAT_PRIVATE_EVIDENCE_DIR/"*.md && -d "$COMPAT_PRIVATE_EVIDENCE_DIR" && ! -L "$COMPAT_PRIVATE_EVIDENCE_DIR" && "$(stat -f '%Lp' "$COMPAT_PRIVATE_EVIDENCE_DIR")" == 700 ]]; then :;
  else fail "public evidence report is required"; fi
  if [[ "$action" != build ]]; then
    [[ -f "$report" ]] || fail "public evidence report is required"
    public_evidence_record "$action" "$report"
    return
  fi
  local started resolved branches pairs wp_pins='' image_pins='' php_pins='' line branch image image_id php_patch wp work duration source_after
  started=$(date +%s); resolved=$(date -u +%Y-%m-%dT%H:%M:%SZ)
  branches=$(resolve_upstream_php_branches "$php_min")
  pairs=$(normalise_matrix "$wp_lines" "$branches") || fail "invalid public evidence matrix"
  while IFS=, read -r line wp; do wp_pins+="${wp_pins:+;}$line=$wp"; done <<< "$(resolve_matrix_wp_versions "$pairs")"
  IFS=',' read -r -a supported <<< "$branches"
  for branch in "${supported[@]}"; do
    image="wordpress:php${branch}-apache"
    docker pull "$image" >/dev/null || fail "could not resolve official image $image"
    image_id=$(docker image inspect --format '{{.Id}}' "$image")
    [[ "$image_id" =~ ^sha256:[0-9a-f]{64}$ ]] || fail "invalid official image identity for PHP $branch"
    image_pins+="${image_pins:+;}$branch=$image_id"
    php_patch=$(docker run --rm --network none "$image_id" php -r 'echo PHP_VERSION;')
    [[ "$php_patch" == "$branch."* && "$php_patch" =~ ^[0-9]+[.][0-9]+[.][0-9]+$ ]] || fail "official image does not match PHP branch $branch"
    php_pins+="${php_pins:+;}$branch=$php_patch"
  done
  work=$(mktemp -d "${TMPDIR:-/tmp}/gigpress-public-build.XXXXXX"); chmod 700 "$work"
  trap 'rm -rf "$work"' EXIT
  public_evidence_record snapshot "$work/source.json"
  printf 'Resolved WordPress: %s; PHP branches: %s\n' "$wp_pins" "$branches"
  local -a args=(matrix --wp-lines "$wp_lines" --php-branches "$branches" --wp-versions "$wp_pins" --image-ids "$image_pins" --php-min "$php_min" --error-reporting E_ALL --scenario public-publishing --case all)
  printf 'Running pinned public-publishing matrix\n'
  if ! bash "$COMPAT_DIR/run.sh" "${args[@]}" >"$work/matrix.log" 2>&1; then tail -c 8000 "$work/matrix.log" >&2; fail "public-publishing supported matrix failed"; fi
  while IFS=, read -r line branch; do
    wp=$(pinned_value "$wp_pins" "$line")
    cp "$RESULT_DIR/${wp}-php${branch}-public-publishing.json" "$work/"
  done <<< "$pairs"
  source_after=$(mktemp "${TMPDIR:-/tmp}/gigpress-public-source.XXXXXX")
  public_evidence_record snapshot "$source_after"
  cmp -s "$work/source.json" "$source_after" || { rm -f "$source_after"; fail "source changed during public matrix run"; }
  rm -f "$source_after"
  duration=$(($(date +%s)-started))
  jq -n --arg branches "$branches" --arg wp_pins "$wp_pins" --arg image_pins "$image_pins" --arg php_pins "$php_pins" --arg resolved "$resolved" --argjson duration "$duration" '
    {schema:"gigpress-public-evidence/v1",status:"PASS",wordpress_lines:["7.0","7.1"],php_min:"8.3",diagnostic_only_php:["8.2"],php_branches:($branches|split(",")),required_cases:["tracer-1.4","migrated-contracts","layout-main","layout-compact","override-priority","html-json","rss-contract","ical-contract","empty-contracts"],resolution:{wordpress_url:"https://api.wordpress.org/core/version-check/1.7/",php_url:"https://www.php.net/supported-versions.php",resolved_at:$resolved,wordpress_versions:($wp_pins|split(";")|map(split("=")|{key:.[0],value:.[1]})|from_entries),images:($image_pins|split(";")|map(split("=")|{key:.[0],value:.[1]})|from_entries),php_versions:($php_pins|split(";")|map(split("=")|{key:.[0],value:.[1]})|from_entries)},elapsed_seconds:$duration,commands:["rtk proxy bash tests/compat/run.sh matrix --scenario public-publishing --case all --wp-lines 7.0,7.1 --wp-patches latest --php-supported upstream --php-min 8.3 --error-reporting E_ALL","rtk proxy bash tests/compat/run.sh matrix --scenario public-publishing --case all --wp-lines 7.0,7.1 --php-branches "+$branches+" --wp-versions '"+$wp_pins+"' --image-ids '"+$image_pins+"' --php-min 8.3 --error-reporting E_ALL","rtk proxy bash tests/compat/run.sh cell --wp 7.1.2 --php 8.3 --scenario upgrade-preservation --case all"],acceptance:{phase_accepted:false,nyquist_compliant:false,final_source_browser_calendar:"pending-blocking-human-plan-04-05"},browser_evidence:{status:"pending",source_revision:"",http_is_not_browser:true,blocks_acceptance:true}}' >"$work/meta.json"
  public_evidence_record render "$report" "$work"
  rm -rf "$work"; trap - EXIT
}

runtime_wp_version() {
  case "$1" in
    7.0|7.1) resolve_latest_wordpress_patch "$1" '8.3' ;;
    *) fail "unsupported WordPress line $1" ;;
  esac
}

resolve_matrix_wp_versions() {
  local pairs=$1 line branch previous_line=''
  while IFS=, read -r line branch; do
    if [[ "$line" != "$previous_line" ]]; then
      printf '%s,%s\n' "$line" "$(runtime_wp_version "$line")"
      previous_line=$line
    fi
  done <<< "$pairs"
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
      compose_env down --volumes --remove-orphans >/dev/null 2>&1 || true
    }
    compose_env() {
      env -u COMPOSE_FILE -u COMPOSE_PROJECT_NAME -u WORDPRESS_DB_HOST -u MYSQL_HOST -u DB_HOST -u DATABASE_URL REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" WORDPRESS_IMAGE="wordpress:php${PHP_VERSION}-apache" PHP_VERSION="$PHP_VERSION" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" "$@"
    }
    trap cleanup_floor EXIT INT TERM
    if ! compose_env pull --quiet wordpress db >/dev/null || ! compose_env up -d db >/dev/null; then cleanup_floor; fail "could not start isolated fixture database $line/PHP $supported_php"; fi
    wait_for_database
    if ! compose_env up -d wordpress >/dev/null; then cleanup_floor; fail "could not start isolated fixture WordPress $line/PHP $supported_php"; fi
    wait_for_wordpress
    install_archived_wordpress_core
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
      diagnostic_state=$(run_real_plugin_phase real-low-live)
    fi
    PHP_VERSION=$supported_php
    if ! compose_env up -d --force-recreate wordpress >/dev/null; then cleanup_floor; fail "could not restore fixture cell $line/PHP $supported_php"; fi
    wait_for_wordpress
    if [[ "$target" == fixture ]]; then
      recovered_state=$(run_fixture_phase fixture-recover)
    else
      compose_env exec -T wordpress php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' || fail "real-plugin recovery did not return to a supported PHP runtime"
      recovered_state=$(run_real_plugin_phase real-recover)
      printf '%s\n' "$supported_state" "$diagnostic_state" "$recovered_state" | rtk jq -s '.[0].real_plugin_runtime.active_state == .[1].real_plugin_runtime.active_state and .[1].real_plugin_runtime.active_state == .[2].real_plugin_runtime.active_state and .[0].real_plugin_runtime.data_snapshot == .[1].real_plugin_runtime.data_snapshot and .[1].real_plugin_runtime.data_snapshot == .[2].real_plugin_runtime.data_snapshot' | rtk jq -e . >/dev/null || fail "real-plugin runtime-floor changed active state or GigPress data/options"
      printf '%s\n' "$supported_state" "$diagnostic_state" "$recovered_state" | rtk jq -s --arg wp "$WP_VERSION" '{status:"PASS",wordpress:$wp,php_transition:[.[0].php_version,.[1].php_version,.[2].php_version],plugin_active_preserved:(.[0].real_plugin_runtime.active_state == .[1].real_plugin_runtime.active_state and .[1].real_plugin_runtime.active_state == .[2].real_plugin_runtime.active_state),data_preserved:(.[0].real_plugin_runtime.data_snapshot == .[1].real_plugin_runtime.data_snapshot and .[1].real_plugin_runtime.data_snapshot == .[2].real_plugin_runtime.data_snapshot),low_floor:.[1].real_plugin_runtime,recovery:.[2].real_plugin_runtime}'
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
    [[ "$CLEANUP_NEEDED" == true ]] && compose_env down --volumes --remove-orphans >/dev/null 2>&1 || true
    exit "$status"
  }
  trap cleanup EXIT INT TERM
  compose_env() {
    env -u COMPOSE_FILE -u COMPOSE_PROJECT_NAME -u WORDPRESS_DB_HOST -u MYSQL_HOST -u DB_HOST -u DATABASE_URL REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" WORDPRESS_IMAGE="wordpress:php${PHP_VERSION}-apache" PHP_VERSION="$PHP_VERSION" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" "$@"
  }
  CLEANUP_NEEDED=true
  compose_env pull wordpress db
  compose_env up -d db wordpress
  wait_for_database; wait_for_wordpress
  compose_env exec -T wordpress sh -c 'mkdir -p /var/www/html/wp-content/mu-plugins && cp /var/www/html/wp-content/plugins/gigpress/tests/compat/diagnostics/menu-trace.php /var/www/html/wp-content/mu-plugins/gigpress-menu-trace.php'
  [[ "$conflict_mode" == exact-key-late-add ]] && compose_env exec -T wordpress sh -c 'cp /var/www/html/wp-content/plugins/gigpress/tests/compat/fixtures/menu-conflict-plugin.php /var/www/html/wp-content/plugins/menu-conflict-plugin.php'
  local skip_gigpress_activation=''
  if [[ "$PHP_VERSION" == 8.2 && "$conflict_mode" == exact-key-late-add ]]; then
    skip_gigpress_activation=1
  fi
  output=$(compose_env exec -T -e COMPAT_PURPOSE=diagnose-menu -e COMPAT_CONFLICT_MODE="$conflict_mode" -e COMPAT_SKIP_GIGPRESS_ACTIVATION="$skip_gigpress_activation" wordpress php /compat/probe.php) || { printf '%s\n' "$output" >&2; fail "menu diagnostic probe failed"; }
  printf '%s\n' "$output" | tee "$RESULT_DIR/${WP_VERSION}-php${PHP_VERSION}-diagnose-menu-${conflict_mode}.json"
  if [[ "$conflict_mode" == exact-key-late-add ]]; then
    printf '%s\n' "$output" | rtk jq -e --arg key "$expect_key" '.status == "PASS" and .menu_trace.trace_is_request_local == true and (.menu_trace.row_creators | any(.slug == $key and .callback == "gigpress_menu_conflict_late_add" and .priority == 20)) and (.menu_trace.missing_from_input | index($key)) and (.menu_trace.missing_from_returned_order | index($key)) and (.menu_trace.callbacks | any(.identity == "gigpress_menu_conflict_late_add" and .priority == 20)) and (.menu_trace.menu_warnings | any(.message == ("Undefined array key \"" + $key + "\"")))' >/dev/null || fail "trace did not reproduce and attribute the exact controlled separator warning"
  else
    printf '%s\n' "$output" | rtk jq -e --arg key "$expect_key" '.status == "PASS" and (.menu_trace.final_slugs | index($key))' >/dev/null || fail "checkout-only trace did not retain the checkout separator"
  fi
  if [[ "$PHP_VERSION" == 8.2 ]]; then
    printf '%s\n' "$output" | rtk jq -e '.menu_trace.runtime_label == "diagnostic-only"' >/dev/null || fail "PHP 8.2 was not labeled diagnostic-only"
  else
    printf '%s\n' "$output" | rtk jq -e '.menu_trace.runtime_label == "supported"' >/dev/null || fail "supported PHP diagnostic was mislabeled"
  fi
}

run_browser_fixture() {
  local action='' session='' browser_case='entry' output result port container_id image_id started=$SECONDS
  WP_VERSION=''; PHP_VERSION=''; TABLE_PREFIX='wp_'
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --action) action=${2:-}; shift 2 ;;
      --wp) WP_VERSION=${2:-}; shift 2 ;;
      --php) PHP_VERSION=${2:-}; shift 2 ;;
      --case) browser_case=${2:-}; shift 2 ;;
      --session) session=${2:-}; shift 2 ;;
      *) fail "unknown browser-fixture option $1" ;;
    esac
  done
  [[ "$action" =~ ^(smoke|start|status|stop)$ ]] || fail "browser-fixture requires smoke, start, status or stop"
  [[ "$browser_case" =~ ^(entry|settings|guards|all)$ ]] || fail "unknown browser HTTP case"
  if [[ "$action" == status || "$action" == stop ]]; then
    # No eval/source: reconstruct only allowlisted fields after strict private-file checks.
    [[ -n "$session" && -f "$session" && ! -L "$session" && ! -L "$(dirname "$session")" ]] || fail "not an owned private browser session"
    [[ "$(stat -f '%u:%Lp' "$session")" == "$(id -u):600" && "$(stat -f '%u:%Lp' "$(dirname "$session")")" == "$(id -u):700" ]] || fail "not an owned private browser session"
    jq -e --arg root "$ROOT" --argjson uid "$(id -u)" '
      .schema == "gigpress-browser-session/v1" and .root == $root and .uid == $uid
      and (.project | test("^gigpress_browser_[0-9]+_[0-9]+_[0-9]+$"))
      and (.owner | test("^[0-9a-f]{64}$")) and (.password | test("^[0-9a-f]{64}$"))
      and (.db_password | test("^[0-9a-f]{64}$")) and (.db_root_password | test("^[0-9a-f]{64}$"))
      and (.wp | test("^[0-9]+[.][0-9]+[.][0-9]+$")) and (.php | test("^8[.][3-9]$"))
      and (.url | test("^http://127[.]0[.]0[.]1:[0-9]+$"))
    ' "$session" >/dev/null || fail "not an owned private browser session"
    PROJECT=$(jq -r .project "$session"); BROWSER_OWNER=$(jq -r .owner "$session")
    WP_VERSION=$(jq -r .wp "$session"); PHP_VERSION=$(jq -r .php "$session")
    DB_PASSWORD=$(jq -r .db_password "$session"); DB_ROOT_PASSWORD=$(jq -r .db_root_password "$session")
    BROWSER_PASSWORD=$(jq -r .password "$session"); BROWSER_URL=$(jq -r .url "$session")
    BROWSER_DIR=$(dirname "$session")
    [[ "$BROWSER_DIR" == */gigpress-browser-* && "$session" == "$BROWSER_DIR/session.json" ]] || fail "not an owned private browser session"
  else
    [[ "$WP_VERSION" =~ ^[0-9]+[.][0-9]+[.][0-9]+$ && "$PHP_VERSION" =~ ^8[.][3-9]$ ]] || fail "supported exact WordPress/PHP required"
    umask 077
    BROWSER_DIR=$(mktemp -d "${TMPDIR:-/tmp}/gigpress-browser-XXXXXX")
    session="$BROWSER_DIR/session.json"
    PROJECT="gigpress_browser_${RANDOM}_$$_$(date +%s)"
    DB_PASSWORD=$(openssl rand -hex 32); DB_ROOT_PASSWORD=$(openssl rand -hex 32)
    BROWSER_OWNER=$(openssl rand -hex 32); BROWSER_PASSWORD=$(openssl rand -hex 32)
    BROWSER_URL=''
  fi
  COMPOSE=(docker compose --project-name "$PROJECT" --file "$COMPAT_DIR/compose.yaml" --file "$COMPAT_DIR/compose.browser.yaml")
  compose_env() {
    env -u COMPOSE_FILE -u COMPOSE_PROJECT_NAME -u WORDPRESS_DB_HOST -u MYSQL_HOST -u DB_HOST -u DATABASE_URL \
      REPO_ROOT="$ROOT" WORDPRESS_IMAGE="wordpress:php${PHP_VERSION}-apache" COMPAT_TABLE_PREFIX=wp_ \
      COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" COMPAT_BROWSER_OWNER="$BROWSER_OWNER" "${COMPOSE[@]}" "$@"
  }
  browser_cleanup() {
    local status=$?
    local cleanup_failed=false containers='' volumes=''
    trap - EXIT INT TERM
    if [[ "$BROWSER_RETAIN" != true ]]; then
      if ! compose_env down --volumes --remove-orphans >"$BROWSER_DIR/cleanup.log" 2>&1; then cat "$BROWSER_DIR/cleanup.log" >&2; cleanup_failed=true; fi
      if ! containers=$(docker ps -aq --filter "label=com.docker.compose.project=$PROJECT" 2>>"$BROWSER_DIR/cleanup.log"); then cleanup_failed=true; fi
      if ! volumes=$(docker volume ls -q --filter "label=com.docker.compose.project=$PROJECT" 2>>"$BROWSER_DIR/cleanup.log"); then cleanup_failed=true; fi
      [[ -z "$containers" && -z "$volumes" ]] || cleanup_failed=true
      if [[ "$cleanup_failed" == true ]]; then
        status=1
        printf 'Browser cleanup is unconfirmed; private recovery files retained at %s\n' "$BROWSER_DIR" >&2
        if [[ -f "$BROWSER_DIR/session.json" ]]; then printf 'Retry: bash tests/compat/run.sh browser-fixture --action stop --session %q\n' "$BROWSER_DIR/session.json" >&2; fi
      else
        printf '{"cleanup":"PASS","owned_project":"%s","services_removed":true,"volumes_removed":true}\n' "$PROJECT"
        rm -rf -- "$BROWSER_DIR"
      fi
    fi
    exit "$status"
  }
  BROWSER_RETAIN=false
  if [[ "$action" == status || "$action" == stop ]]; then
    local id found=0 owned_ids=''
    owned_ids=$(docker ps -aq --filter "label=com.docker.compose.project=$PROJECT") || fail "cannot inspect owned browser services"
    while IFS= read -r id; do
      [[ -n "$id" ]] || continue
      [[ "$(docker inspect --format '{{index .Config.Labels "gigpress.browser.owner"}}' "$id")" == "$BROWSER_OWNER" ]] || fail "not an owned private browser session"
      found=$((found+1))
    done <<< "$owned_ids"
    [[ "$found" -le 2 ]] || fail "owned browser session has unexpected services"
    if [[ "$action" == stop ]]; then trap browser_cleanup EXIT; return 0; fi
    [[ "$found" == 2 ]] || fail "owned browser session must have exactly two services"
    jq '{status:"PASS",session:$session,url,wordpress_version:.wp,php_branch:.php,source_revision,image_id,project}' --arg session "$session" "$session"
    return 0
  fi
  trap browser_cleanup EXIT
  trap 'exit 130' INT
  trap 'exit 143' TERM
  compose_env pull wordpress db >"$BROWSER_DIR/setup.log" 2>&1
  compose_env up -d db wordpress >>"$BROWSER_DIR/setup.log" 2>&1
  wait_for_database; wait_for_wordpress; install_archived_wordpress_core
  port=$(compose_env port wordpress 80)
  [[ "$port" =~ ^127[.]0[.]0[.]1:[0-9]+$ ]] || fail "browser fixture is not loopback-only"
  BROWSER_URL="http://$port"
  browser_php() {
    compose_env exec -T -e COMPAT_BROWSER_MODE="$1" -e COMPAT_BROWSER_CASE="$browser_case" -e COMPAT_BROWSER_URL="$BROWSER_URL" \
      -e COMPAT_BROWSER_PASSWORD="$BROWSER_PASSWORD" -e COMPAT_EXPECTED_WP_VERSION="$WP_VERSION" wordpress php /compat/browser-bootstrap.php
  }
  output=$(browser_php seed) || { printf '%s\n' "$output" >&2; fail "browser bootstrap failed"; }
  printf '%s\n' "$output" | jq -e '.status == "PASS" and .assertion_count > 0 and (.checks|length) == .assertion_count and ([.checks[]]|all)' >/dev/null || fail "browser seed assertions failed"
  image_id=$(docker image inspect --format '{{.Id}}' "wordpress:php${PHP_VERSION}-apache")
  jq -n --arg root "$ROOT" --argjson uid "$(id -u)" --arg project "$PROJECT" --arg owner "$BROWSER_OWNER" \
    --arg wp "$WP_VERSION" --arg php "$PHP_VERSION" --arg url "$BROWSER_URL" --arg password "$BROWSER_PASSWORD" \
    --arg db_password "$DB_PASSWORD" --arg db_root_password "$DB_ROOT_PASSWORD" --arg image_id "$image_id" \
    --arg revision "$(git -C "$ROOT" rev-parse HEAD)" '{schema:"gigpress-browser-session/v1",root:$root,uid:$uid,project:$project,owner:$owner,
      wp:$wp,php:$php,url:$url,password:$password,db_password:$db_password,db_root_password:$db_root_password,
      admin_user:"browser-admin",subscriber_user:"browser-subscriber",source_revision:$revision,image_id:$image_id}' >"$session"
  if [[ "$action" == start ]]; then
    BROWSER_RETAIN=true
    printf '{"status":"PASS","session":"%s","url":"%s","setup_seconds":%s}\n' "$session" "$BROWSER_URL" "$((SECONDS-started))"
    return 0
  fi
  output=$(browser_php smoke) || { printf '%s\n' "$output" >&2; fail "browser HTTP smoke assertions failed"; }
  result=$(printf '%s\n' "$output" | jq -c --arg image_id "$image_id" --arg revision "$(git -C "$ROOT" rev-parse HEAD)" --arg case "$browser_case" --argjson duration "$((SECONDS-started))" '. + {image_id:$image_id,source_revision:$revision,case:$case,elapsed_seconds:$duration,loopback_only:true}')
  printf '%s\n' "$result" | tee "$RESULT_DIR/browser-${browser_case}.json"
  printf '%s\n' "$result" | jq -e --arg wp "$WP_VERSION" '.status == "PASS" and .wordpress_version == $wp and .assertion_count > 0 and (.checks|length) == .assertion_count and ([.checks[]]|all) and (.errors|length) == 0 and (.http_errors|length) == 0' >/dev/null || fail "browser HTTP smoke contract failed"
  compose_env logs --no-color wordpress >"$BROWSER_DIR/http.log"
  if grep -Ei '(PHP (Warning|Fatal|Parse|Notice)|Uncaught .*Error)' "$BROWSER_DIR/http.log" >/dev/null; then fail "HTTP runtime log contains errors"; fi
}

if [[ "$MODE" == browser-fixture ]]; then
  run_browser_fixture "$@"
  exit 0
fi

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

if [[ "$MODE" == preservation-evidence ]]; then
  run_preservation_evidence "$@"
  exit 0
fi

if [[ "$MODE" == administration-evidence ]]; then
  run_administration_evidence "$@"
  exit 0
fi

if [[ "$MODE" == public-evidence ]]; then
  run_public_evidence "$@"
  exit 0
fi

if [[ "$MODE" == matrix ]]; then
  run_matrix "$@"
  exit 0
fi

[[ "$MODE" == cell ]] || fail "supported commands: cell, matrix, lint, metadata, self-test, runtime-floor, menu-contract, preservation-evidence, public-evidence"
WP_VERSION=''; PHP_VERSION=''; SCENARIO='activation-menu'; UPGRADE_CASE='tracer-1.4'; TABLE_PREFIX='wp_'; CONFLICT_FIXTURE=''; CONFLICT_MODE=''; CONFLICT_POSITION=''; PINNED_IMAGE=''
CELL_STARTED=$(date +%s)
while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp) WP_VERSION=${2:-}; shift 2 ;;
    --php) PHP_VERSION=${2:-}; shift 2 ;;
    --image-id) PINNED_IMAGE=${2:-}; shift 2 ;;
    --scenario) SCENARIO=${2:-}; shift 2 ;;
    --case) UPGRADE_CASE=${2:-}; shift 2 ;;
    --conflict-fixture) CONFLICT_FIXTURE=${2:-}; shift 2 ;;
    --conflict-mode) CONFLICT_MODE=${2:-}; shift 2 ;;
    --conflict-position) CONFLICT_POSITION=${2:-}; shift 2 ;;
    *) fail "unknown option $1" ;;
  esac
done
require_value --wp "$WP_VERSION"; require_value --php "$PHP_VERSION"
[[ "$WP_VERSION" =~ ^[0-9]+([.][0-9]+){2}$ ]] || fail "WordPress must be an exact patch version"
[[ "$PHP_VERSION" =~ ^[0-9]+[.][0-9]+$ ]] || fail "PHP must be a major.minor version"
[[ "$PHP_VERSION" != 8.2 ]] || fail "PHP 8.2 is diagnostic-only and cannot be a supported cell"
[[ -z "$PINNED_IMAGE" || "$PINNED_IMAGE" =~ ^sha256:[0-9a-f]{64}$ ]] || fail "pinned image must be an exact local official image ID"
case "$SCENARIO" in
  activation-menu|admin-menu|csv-roundtrip|full-workflows) [[ "$UPGRADE_CASE" == tracer-1.4 ]] || fail "--case is only supported by case-based scenarios" ;;
  upgrade-preservation) [[ "$UPGRADE_CASE" =~ ^(tracer-1\.4|safety-1\.4|metadata-classification|versions-1\.0-1\.2|versions-1\.3-1\.5|current-1\.6|settings-repeat|show-lifecycle|optional-request-fields|entity-guards|tour-undo|all)$ ]] || fail "unsupported upgrade-preservation case: $UPGRADE_CASE" ;;
  administration-workflows) [[ "$UPGRADE_CASE" =~ ^(entry-create|entry-recovery|entry-controls|settings-save|settings-sections|list-single|list-navigation|list-bulk|all)$ ]] || fail "unsupported administration-workflows case: $UPGRADE_CASE" ;;
  public-publishing) [[ "$UPGRADE_CASE" =~ ^(tracer-1\.4|migrated-contracts|layout-main|layout-compact|override-priority|html-json|rss-contract|ical-contract|empty-contracts|all)$ ]] || fail "unsupported public-publishing case: $UPGRADE_CASE"; TABLE_PREFIX='compat_legacy_' ;;
  *) fail "unsupported cell scenario: $SCENARIO" ;;
esac
[[ "$SCENARIO" != upgrade-preservation ]] || TABLE_PREFIX='compat_legacy_'
if [[ -n "$CONFLICT_FIXTURE" || -n "$CONFLICT_MODE" ]]; then
  [[ "$CONFLICT_FIXTURE" == 'tests/compat/fixtures/menu-conflict-plugin.php' && "$CONFLICT_MODE" == order-only ]] || fail "cell conflict coverage requires the order-only fixture"
  [[ -n "$CONFLICT_POSITION" ]] || CONFLICT_POSITION=before
  [[ "$CONFLICT_POSITION" == before || "$CONFLICT_POSITION" == after ]] || fail "cell conflict coverage requires a before or after position"
fi

PROJECT="gigpress_compat_${RANDOM}_$$_$(date +%s)"
DB_PASSWORD="compat_${RANDOM}_${RANDOM}"
DB_ROOT_PASSWORD="root_${RANDOM}_${RANDOM}"
COMPOSE=(docker compose --project-name "$PROJECT" --file "$COMPAT_DIR/compose.yaml")
CLEANUP_NEEDED=false
cleanup() {
  local status=$?
  if [[ "$CLEANUP_NEEDED" == true ]]; then
    compose_env down --volumes --remove-orphans >/dev/null 2>&1 || { printf 'compat runner: owned cell cleanup failed: %s\n' "$PROJECT" >&2; status=2; }
  fi
  exit "$status"
}
trap cleanup EXIT INT TERM
compose_env() {
  env -u COMPOSE_FILE -u COMPOSE_PROJECT_NAME -u WORDPRESS_DB_HOST -u MYSQL_HOST -u DB_HOST -u DATABASE_URL \
    REPO_ROOT="$ROOT" WP_VERSION="$WP_VERSION" WORDPRESS_IMAGE="${PINNED_IMAGE:-wordpress:php${PHP_VERSION}-apache}" PHP_VERSION="$PHP_VERSION" COMPAT_TABLE_PREFIX="$TABLE_PREFIX" COMPAT_DB_PASSWORD="$DB_PASSWORD" COMPAT_DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" "${COMPOSE[@]}" "$@"
}

CLEANUP_NEEDED=true
if [[ -n "$PINNED_IMAGE" ]]; then compose_env pull db; else compose_env pull wordpress db; fi
compose_env up -d db wordpress
for attempt in $(seq 1 45); do
  if compose_env exec -T db mariadb-admin ping -h localhost -uroot -p"$DB_ROOT_PASSWORD" --silent >/dev/null 2>&1; then break; fi
  [[ "$attempt" -eq 45 ]] && { compose_env logs --no-color >&2 || true; fail "disposable MariaDB did not become ready"; }
  sleep 2
done
wait_for_database
wait_for_wordpress
install_archived_wordpress_core
if [[ "$CONFLICT_MODE" == order-only ]]; then
  compose_env exec -T wordpress sh -c 'cp /var/www/html/wp-content/plugins/gigpress/tests/compat/fixtures/menu-conflict-plugin.php /var/www/html/wp-content/plugins/menu-conflict-plugin.php'
fi
set +e
output=$(compose_env exec -T -e COMPAT_PURPOSE="$SCENARIO" -e COMPAT_UPGRADE_CASE="$UPGRADE_CASE" -e COMPAT_CONFLICT_MODE="$CONFLICT_MODE" -e COMPAT_CONFLICT_POSITION="$CONFLICT_POSITION" wordpress php /compat/probe.php)
probe_status=$?
set -e
if [[ "$probe_status" -ne 0 ]]; then
  printf '%s\n' "$output" >&2
  if [[ "$SCENARIO" == upgrade-preservation || "$SCENARIO" == administration-workflows ]]; then
    printf 'not ok 1 - %s.%s\n' "$SCENARIO" "$UPGRADE_CASE" >&2
    printf '# tests 1\n# pass 0\n# fail 1\n' >&2
  fi
  fail "probe failed"
fi
image="wordpress:php${PHP_VERSION}-apache"
image_id=$(rtk docker image inspect --format '{{.Id}}' "${PINNED_IMAGE:-$image}")
result=$(printf '%s\n' "$output" | rtk proxy jq -c --arg image "$image" --arg image_id "$image_id" --arg source_revision "$(rtk proxy git rev-parse HEAD)" --argjson elapsed_seconds "$(($(date +%s) - CELL_STARTED))" '. + {image: $image, image_id: $image_id, source_revision: $source_revision, elapsed_seconds: $elapsed_seconds}')
printf '%s\n' "$result" | tee "$RESULT_DIR/${WP_VERSION}-php${PHP_VERSION}-${SCENARIO}.json"
printf '%s\n' "$result" | rtk jq -e --arg wp "$WP_VERSION" '.wordpress_version == $wp' >/dev/null || fail "probe did not boot requested WordPress $WP_VERSION"
if [[ "$SCENARIO" == admin-menu && "$CONFLICT_MODE" == order-only ]]; then
  printf '%s\n' "$output" | rtk jq -e '.status == "PASS" and .plugin_active == true and .menu_order_conflict == true and (.menu_warnings | length == 0) and (.plugin_errors | length == 0) and ((.menu_slugs | length) == (.menu_slugs | unique | length)) and ((.menu_slugs | index("edit-comments.php")) as $comments | (.menu_slugs | index("gigpress.php")) as $gigpress | ($comments != null and $gigpress != null and $gigpress > $comments) and ((.menu_slugs | index("separator-gp")) == null))' >/dev/null || fail "conflict cell did not preserve standard WordPress menu order"
elif [[ "$SCENARIO" == admin-menu ]]; then
  printf '%s\n' "$output" | rtk jq -e '.status == "PASS" and .plugin_active == true and (.menu_warnings | length == 0) and (.plugin_errors | length == 0) and ((.menu_slugs | length) == (.menu_slugs | unique | length)) and ((.menu_slugs | index("edit-comments.php")) as $comments | (.menu_slugs | index("separator-gp")) as $separator | (.menu_slugs | index("gigpress.php")) as $gigpress | ($comments != null and $separator == ($comments + 1) and $gigpress == ($separator + 1)))' >/dev/null || fail "admin-menu cell did not preserve warning-free preferred GigPress placement"
elif [[ "$SCENARIO" == full-workflows ]]; then
  printf '%s\n' "$result" | rtk jq -e '.status == "PASS" and .plugin_active == true and (.plugin_errors | length == 0) and .full_workflows.status == "PASS" and .full_workflows.admin_create_edit_read and .full_workflows.public_shortcode and .full_workflows.rss and .full_workflows.ical and .full_workflows.csv_import_export and .full_workflows.duplicate_preserved' >/dev/null || fail "full workflow cell did not satisfy the compatibility contract"
elif [[ "$SCENARIO" == administration-workflows ]]; then
  administration_contract='
    def valid_case:
      .status == "PASS" and (.checks | type == "object" and length > 0) and
      .assertion_count > 0 and (.assertion_count == (.checks | length)) and
      ([.checks[] | . == true] | all) and .warning_count == 0 and .fatal_count == 0 and .plugin_error_count == 0;
    .status == "PASS" and .plugin_active == true and .fatal == null and
    (.plugin_errors | length == 0) and (.menu_warnings | length == 0) and
    (.administration_workflows as $a | $a.case == $case and
      if $case == "all" then
        ["entry-create","entry-recovery","entry-controls","settings-save","settings-sections","list-single","list-navigation","list-bulk"] as $required |
        ($a.required_cases | sort) == ($required | sort) and
        ($a.cases | length) == 8 and ($a.cases | map(.case) | unique | length) == 8 and
        ($a.cases | map(.case) | sort) == ($required | sort) and ([$a.cases[] | valid_case] | all)
      else ($a | valid_case) end)'
  printf '%s\n' "$result" | rtk proxy jq -e --arg case "$UPGRADE_CASE" "$administration_contract" >/dev/null || fail "administration workflow cell did not satisfy the nonempty case contract"
elif [[ "$SCENARIO" == upgrade-preservation ]]; then
  if [[ "$UPGRADE_CASE" == all ]]; then
    upgrade_contract='.status == "PASS" and .plugin_active == true and (.plugin_errors | length == 0) and .fatal == null and (.menu_warnings | length == 0) and .upgrade_preservation.status == "PASS" and .upgrade_preservation.case == "all" and (.upgrade_preservation.required_cases | length == 11) and (.upgrade_preservation.cases | length == 11) and ((.upgrade_preservation.cases | map(.case) | unique | length) == 11) and ([.upgrade_preservation.cases[] | .status == "PASS" and .ready == true and .plugin_active == true and .warning_count == 0 and .fatal_count == 0 and .plugin_error_count == 0] | all)'
  elif [[ "$UPGRADE_CASE" == current-1.6 ]]; then
    upgrade_contract='.status == "PASS" and .plugin_active == true and (.plugin_errors | length == 0) and .upgrade_preservation.status == "PASS" and .upgrade_preservation.unchanged and .upgrade_preservation.repeat and .upgrade_preservation.journal_absent and (.upgrade_preservation.checks | all)'
  elif [[ "$UPGRADE_CASE" == versions-1.0-1.2 || "$UPGRADE_CASE" == versions-1.3-1.5 || "$UPGRADE_CASE" == settings-repeat ]]; then
    upgrade_contract='.status == "PASS" and .plugin_active == true and (.plugin_errors | length == 0) and .upgrade_preservation.status == "PASS" and ([.upgrade_preservation.fixtures[] | (.repeat and (.checks | all))] | all)'
  else
    upgrade_contract='.status == "PASS" and .plugin_active == true and (.plugin_errors | length == 0) and .upgrade_preservation.status == "PASS" and .upgrade_preservation.case == $case and .upgrade_preservation.manifest_matches and .upgrade_preservation.repeat_matches and .upgrade_preservation.fixture == "reconstructed-1.4"'
    [[ "$UPGRADE_CASE" != safety-1.4 ]] || upgrade_contract="$upgrade_contract and .upgrade_preservation.safety_passed"
    [[ "$UPGRADE_CASE" != metadata-classification ]] || upgrade_contract="$upgrade_contract and .upgrade_preservation.metadata_passed"
  fi
  printf '%s\n' "$result" | rtk jq -e --arg case "$UPGRADE_CASE" "$upgrade_contract" >/dev/null || fail "upgrade preservation cell did not satisfy the contract"
elif [[ "$SCENARIO" == public-publishing ]]; then
  public_contract='.status == "PASS" and .plugin_active == true and .fatal == null and (.plugin_errors | length == 0) and (.menu_warnings | length == 0) and (.public_publishing.case == $case) and .public_publishing.status == "PASS" and (.public_publishing.checks | type == "object" and length > 0) and (.public_publishing.assertion_count == (.public_publishing.checks | length)) and ([.public_publishing.checks[] | . == true] | all) and .public_publishing.checks_boolean and .public_publishing.output_empty'
  printf '%s\n' "$result" | rtk proxy jq -e --arg case "$UPGRADE_CASE" "$public_contract" >/dev/null || fail "public-publishing cell did not satisfy the selected nonempty case contract"
else
  printf '%s\n' "$output" | rtk jq -e '.status == "PASS" and .plugin_active == true and (.menu_slugs | index("gigpress.php")) and (.plugin_errors | length == 0)' >/dev/null || fail "probe did not report a warning-free active GigPress menu"
fi
