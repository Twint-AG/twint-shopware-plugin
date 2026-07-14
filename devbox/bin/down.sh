#!/usr/bin/env bash
# Stop the stack (or one instance). Volumes (data) are preserved; never uses -v.
# Usage: down.sh [all|sw65|sw66|sw67]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc down
else
  resolve_targets "$target"
  dc stop "${RESOLVED_TARGETS[@]}"
  dc rm -f "${RESOLVED_TARGETS[@]}"
fi
