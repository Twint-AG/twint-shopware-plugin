#!/usr/bin/env bash
# logs.sh [65|66|67] — follow the running version's container logs.
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
V="${1:-67}"
docker compose logs -f "sw${V}"
