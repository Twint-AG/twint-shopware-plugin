#!/usr/bin/env bash
#
# Run the GitLab `test` job locally, in Docker, using the SAME image + spc as CI
# (shivammathur/node:jammy) — so a red result means a real problem, not a flaky
# shared runner. Mirrors `.gitlab-ci.yml` (test): spc -> composer install -> ecs
# -> rector --dry-run, matrix PHP 8.1-8.5.
#
# Usage:
#   infra/ci/local-ci.sh tests            # all versions, in parallel
#   infra/ci/local-ci.sh tests 8.3        # only PHP 8.3 (or a few)
#
set -uo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
LOGS="$HERE/logs"
IMAGE="shivammathur/node:jammy"
ALL_VERSIONS=(8.1 8.2 8.3 8.4 8.5)
EXT="mbstring, curl, dom, fileinfo, gd, iconv, intl, json, xml, pdo, phar, zip, sodium, pdo_mysql, bcmath, soap"

# Optional private-SDK token (dev branches) from infra/local/.env.
GITLAB_TOKEN=""
# shellcheck disable=SC1091
[ -f "$ROOT/infra/local/.env" ] && { set -a; . "$ROOT/infra/local/.env"; set +a; }

mkdir -p "$LOGS"

# Copy the repo into a container-local /work (exclude heavy dirs) so parallel jobs
# never clobber each other's composer.lock/vendor and the working tree is untouched.
COPY_SRC='mkdir -p /work && tar cf - -C /src --exclude=./.git --exclude=./vendor --exclude=./node_modules --exclude=./dist --exclude=./build . | tar xf - -C /work && cd /work'

tests_script() {
  cat <<EOF
set -euo pipefail
$COPY_SRC
spc -U
spc --php-version "\$PHP_VERSION" --extensions "$EXT"
[ -n "\${GITLAB_TOKEN:-}" ] && composer config --global gitlab-token.git.nfq.asia "\$GITLAB_TOKEN"
[ "\$PHP_VERSION" = "8.1" ] && composer config --global audit.block-insecure false
rm -f composer.lock
composer install --no-progress --optimize-autoloader
vendor/bin/ecs
vendor/bin/rector process src --dry-run
EOF
}

run_one() {
  local v="$1"
  docker run --rm \
    -e PHP_VERSION="$v" -e GITLAB_TOKEN="$GITLAB_TOKEN" \
    -v "$ROOT":/src:ro \
    "$IMAGE" bash -c "$(tests_script)" >"$LOGS/tests-$v.log" 2>&1
}

cmd_tests() {
  local versions=("$@")
  [ ${#versions[@]} -eq 0 ] && versions=("${ALL_VERSIONS[@]}")
  echo "== tests: ${versions[*]} (logs in infra/ci/logs/) =="
  local pids=() vers=() rc=0
  for v in "${versions[@]}"; do
    run_one "$v" &
    pids+=("$!"); vers+=("$v")
  done
  for i in "${!pids[@]}"; do
    if wait "${pids[$i]}"; then
      echo "  PASS  tests ${vers[$i]}"
    else
      echo "  FAIL  tests ${vers[$i]}  -> infra/ci/logs/tests-${vers[$i]}.log"; rc=1
    fi
  done
  return $rc
}

case "${1:-tests}" in
  tests) shift; cmd_tests "$@" ;;
  *) echo "usage: local-ci.sh tests [versions...]" >&2; exit 2 ;;
esac
