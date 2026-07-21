#!/usr/bin/env bash
# shell.sh [65|66|67] — open a bash shell in the running version's container.
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
V="${1:-67}"
docker compose exec "sw${V}" bash
