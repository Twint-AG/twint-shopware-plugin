#!/usr/bin/env bash
# Start the stack (or one instance). Usage: up.sh [all|sw65|sw66|sw67]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

# Generate the Traefik basic-auth htpasswd from .env creds (protects every
# instance). Must exist before the proxy starts (it mounts ./auth read-only).
AUTH_DIR="$DEVBOX_DIR/auth"
mkdir -p "$AUTH_DIR"
: "${BASIC_AUTH_USER:?set BASIC_AUTH_USER in .env}"
: "${BASIC_AUTH_PASSWORD:?set BASIC_AUTH_PASSWORD in .env}"
printf '%s:%s\n' "$BASIC_AUTH_USER" \
  "$(openssl passwd -apr1 -stdin <<<"$BASIC_AUTH_PASSWORD")" > "$AUTH_DIR/htpasswd"
chmod 600 "$AUTH_DIR/htpasswd"
echo "==> basic auth ready (user: $BASIC_AUTH_USER)"

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc up -d
else
  resolve_targets "$target"
  dc up -d proxy "${RESOLVED_TARGETS[@]}"
fi

dc ps
