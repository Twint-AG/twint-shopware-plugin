#!/usr/bin/env bash
# up.sh [65|66|67] — start ONE Shopware version + the TLS proxy, wait until the
# shop answers, then provision it (plugin + assets + CHF/CH + domain).
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOCAL="$(cd "$HERE/.." && pwd)"
cd "$LOCAL"

V="${1:-67}"
case "$V" in
  65|66|67) ;;
  *) echo "usage: up.sh [65|66|67]" >&2; exit 2 ;;
esac
SVC="sw${V}"

echo "== starting ${SVC} + proxy =="
docker compose --profile "$SVC" up -d

echo "== waiting for ${SVC} to answer (up to ~5 min) =="
ready=0
for _ in $(seq 1 60); do
  if docker compose exec -T "$SVC" bash -lc \
       'curl -sfo /dev/null http://localhost/admin || curl -sfo /dev/null http://localhost/'; then
    ready=1; break
  fi
  sleep 5
done
if [ "$ready" -ne 1 ]; then
  echo "!! ${SVC} did not become ready — check: docker compose logs ${SVC}" >&2
  exit 1
fi

echo "== provisioning ${SVC} =="
docker compose exec -T "$SVC" bash -s < bin/provision.sh

echo
echo "Ready: https://shopware.twint.local"
echo "  admin: https://shopware.twint.local/admin  (admin / shopware)"
