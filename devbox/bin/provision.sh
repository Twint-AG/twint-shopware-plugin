#!/usr/bin/env bash
# One-time-per-instance: point the sales-channel domain at the subdomain so
# Shopware emits correct URLs behind Traefik. Idempotent.
# Usage: provision.sh [all|sw65|sw66|sw67]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

resolve_targets "${1:-all}"
for inst in "${RESOLVED_TARGETS[@]}"; do
  host="${inst}-${DOMAIN_BASE}"
  echo "==> [$inst] set sales-channel domain -> http://$host"
  dc exec -T "$inst" php bin/console sales-channel:update:domain "$host"
done
