#!/usr/bin/env bash
# Start the stack (or one instance). Usage: up.sh [all|sw65|sw66|sw67]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc up -d
else
  resolve_targets "$target"
  dc up -d proxy "${RESOLVED_TARGETS[@]}"
fi

dc ps
