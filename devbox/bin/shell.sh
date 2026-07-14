#!/usr/bin/env bash
# Open a bash shell inside an instance. Usage: shell.sh <sw65|sw66|sw67>
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-}"
if ! is_instance "$target"; then
  echo "Usage: shell.sh <${INSTANCES[*]}>" >&2
  exit 1
fi
dc exec "$target" bash
