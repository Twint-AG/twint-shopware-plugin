#!/usr/bin/env bash
# down.sh [--volumes] — stop every version + proxy. --volumes also drops the
# per-version named volumes (full reset of DB + Shopware files).
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

ALL=(--profile sw65 --profile sw66 --profile sw67)
if [ "${1:-}" = "--volumes" ]; then
  docker compose "${ALL[@]}" down -v
else
  docker compose "${ALL[@]}" down
fi
