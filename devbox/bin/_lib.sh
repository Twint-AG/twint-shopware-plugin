#!/usr/bin/env bash
# Shared helpers for devbox scripts.
# Source it:  . "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"

set -euo pipefail

DEVBOX_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$DEVBOX_DIR/.env"

INSTANCES=(sw65 sw66 sw67)

# Load and export every var from devbox/.env.
load_env() {
  if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE not found. Copy .env.example to .env and fill it in." >&2
    exit 1
  fi
  set -a
  # shellcheck disable=SC1090
  . "$ENV_FILE"
  set +a
}

# True if $1 is a known instance name.
is_instance() {
  local x="${1:-}"
  local i
  for i in "${INSTANCES[@]}"; do
    [ "$i" = "$x" ] && return 0
  done
  return 1
}

# Validate a target ("all" or one instance) and populate the RESOLVED_TARGETS
# array in the CALLER's shell. Exits non-zero on invalid/missing input.
# Call it directly (`resolve_targets "$x"`), then read "${RESOLVED_TARGETS[@]}".
# Do NOT wrap it in $(...) or <(...) — a subshell would swallow the exit.
resolve_targets() {
  local target="${1:-}"
  RESOLVED_TARGETS=()
  if [ -z "$target" ]; then
    echo "ERROR: missing instance (one of: ${INSTANCES[*]} all)" >&2
    exit 1
  fi
  if [ "$target" = "all" ]; then
    RESOLVED_TARGETS=("${INSTANCES[@]}")
    return 0
  fi
  if is_instance "$target"; then
    RESOLVED_TARGETS=("$target")
    return 0
  fi
  echo "ERROR: unknown instance '$target' (expected: ${INSTANCES[*]} all)" >&2
  exit 1
}

# Authenticated GitLab URL. Never echo this to logs.
git_remote_url() {
  printf 'https://oauth2:%s@%s' "$GITLAB_TOKEN" "$GIT_REMOTE"
}

# docker compose pinned to the devbox project (so ./src/... resolves correctly).
dc() {
  docker compose --project-directory "$DEVBOX_DIR" -f "$DEVBOX_DIR/compose.yaml" "$@"
}
