# Devbox: Multi-version Shopware dev box — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up a `devbox/` toolset that runs Shopware 6.5, 6.6, and 6.7 concurrently on the fresh EC2 dev box (`ssh twint-dev`) behind a Traefik reverse proxy, with persistent per-instance DB + media, and a `deploy.sh` that pulls any GitLab branch/commit of the TWINT plugin into a chosen instance.

**Architecture:** One Docker Compose project = one Traefik proxy (`:80`) + three `dockware/dev` all-in-one containers on a shared `web` network. Traefik routes `sw65|sw66|sw67.$DOMAIN_BASE` by Host header to each container's internal port 80. Each instance bind-mounts a deploy-managed git checkout (`devbox/src/swXX`) as the plugin; DB and media are per-instance named volumes. Bash scripts in `devbox/bin/` wrap host bootstrap, compose lifecycle, git-pull deploy, and provisioning.

**Tech Stack:** Docker Engine + Compose v2, Traefik v3, dockware/dev images, Bash, git (HTTPS + token), Shopware CLI (`bin/console`), Composer.

**Spec:** `docs/superpowers/specs/2026-07-14-devbox-multi-version-shopware-design.md`

## Global Constraints

- Instances and their pinned images: `sw65` → `dockware/dev:6.5.8.0`, `sw66` → `dockware/dev:6.6.7.0`, `sw67` → `dockware/dev:6.7.2.2` (image tags come from `.env`; `6.6.7.0` matches `ci66`).
- Plugin name is `TwintPayment`; in-container path is `/var/www/html/custom/plugins/TwintPayment`.
- dockware default DB is used as-is: user `root` / password `root`, database `shopware`.
- Hostnames derive from `DOMAIN_BASE` (default `twint-dev`): instance `swXX` is served at `swXX.$DOMAIN_BASE`.
- v1 is **HTTP only** on `:80`. No TLS, no `:443` (Route53 + Let's Encrypt is a documented later step, not built here).
- Persistence is **DB + media only** via per-instance named volumes. Container code/assets are not persisted.
- `down` must never pass `-v` — volumes (data) survive `down`. Data removal is always an explicit manual action.
- `GITLAB_TOKEN` must never be committed and must not be left in any checkout's `.git/config` after a deploy completes.
- `devbox/` is internal tooling: it stays `export-ignore` in `.gitattributes` and in `sync.sh`'s `EXCLUDE_PATHS`, so it never reaches the public GitHub mirror.
- All scripts: `#!/usr/bin/env bash` + `set -euo pipefail`, executable bit set, pass `bash -n`.
- Every task that adds a script also updates its documentation in the same task where a doc exists; the dedicated docs task (Task 10) writes the doc set in full.

---

## File Structure

```
.gitattributes                         # MODIFY: add `devbox/ export-ignore` (done in Task 1)
bin/sync.sh                            # MODIFY: EXCLUDE_PATHS scrub (done in Task 1)
devbox/
  .gitignore                           # Task 2
  .env.example                         # Task 2
  compose.yaml                         # Task 3
  bin/
    _lib.sh                            # Task 4 — sourced by all other scripts
    bootstrap.sh                       # Task 5
    up.sh                              # Task 6
    down.sh                            # Task 6
    deploy.sh                          # Task 7
    provision.sh                       # Task 8
    logs.sh                            # Task 9
    shell.sh                           # Task 9
  README.md                            # Task 10
  docs/
    setup.md  deploy.md  operations.md
    dns-tls.md  troubleshooting.md  architecture.md   # Task 10
```

**Verification model (infra, not unit tests):** each task's "test" is a static check that runs locally — `bash -n <file>` for scripts, `docker compose -f devbox/compose.yaml config` for compose. True functional acceptance (containers actually serving, plugin actually deploying) requires the `twint-dev` box and is done once in **Task 11**. Do not claim functional success from a passing `bash -n`.

---

### Task 1: Exclude `devbox/` from the public GitHub mirror

Already applied to the working tree during design; this task verifies and commits it so the exclusion lands before `devbox/` exists.

**Files:**
- Modify: `bin/sync.sh` (adds `EXCLUDE_PATHS=(devbox infra)` + scrub commit + retag)
- Modify: `.gitattributes` (adds `devbox/ export-ignore`)

- [ ] **Step 1: Verify sync.sh contents**

Run: `grep -n 'EXCLUDE_PATHS' bin/sync.sh`
Expected: a line `EXCLUDE_PATHS=(devbox infra)`.

- [ ] **Step 2: Verify .gitattributes**

Run: `grep -n 'devbox/ export-ignore' .gitattributes`
Expected: one match.

- [ ] **Step 3: Syntax-check sync.sh**

Run: `bash -n bin/sync.sh && echo OK`
Expected: `OK`.

- [ ] **Step 4: Commit**

```bash
git add bin/sync.sh .gitattributes
git commit -m "chore(sync): exclude devbox/ + infra/ from public GitHub mirror"
```

---

### Task 2: Scaffold `devbox/` config (`.gitignore`, `.env.example`)

**Files:**
- Create: `devbox/.gitignore`
- Create: `devbox/.env.example`

**Interfaces:**
- Produces: the env variables every script and `compose.yaml` consume — `DOMAIN_BASE`, `GIT_REMOTE`, `GITLAB_TOKEN`, `SW65_IMAGE`, `SW66_IMAGE`, `SW67_IMAGE`.

- [ ] **Step 1: Create `devbox/.gitignore`**

```gitignore
# Host-specific, deploy-managed — never commit
/.env
/src/
```

- [ ] **Step 2: Create `devbox/.env.example`**

```dotenv
# ── Devbox configuration ─────────────────────────────────────────────
# Copy to `.env` (gitignored) and fill in. Compose auto-reads .env; the
# bin/ scripts source it too (via _lib.sh).

# Base hostname for instance subdomains.
#   Instances are served at sw65.$DOMAIN_BASE, sw66.$DOMAIN_BASE, sw67.$DOMAIN_BASE
#   Local testing: keep 'twint-dev' and add matching /etc/hosts entries on your laptop.
#   Later (Route53): set to a real domain, e.g. dev.twint.example
DOMAIN_BASE=twint-dev

# GitLab plugin repo as host+path WITHOUT scheme.
#   Used as: https://oauth2:$GITLAB_TOKEN@$GIT_REMOTE
#   TODO: replace with the real GitLab host/path.
GIT_REMOTE=gitlab.example.com/twint-ag/twint-shopware-plugin.git

# GitLab read token (deploy token or PAT with read_repository scope).
#   Keep secret. .env is gitignored; never commit a real value.
GITLAB_TOKEN=changeme

# dockware image tags per Shopware version (pinned).
SW65_IMAGE=dockware/dev:6.5.8.0
SW66_IMAGE=dockware/dev:6.6.7.0
SW67_IMAGE=dockware/dev:6.7.2.2
```

- [ ] **Step 3: Verify the example parses as shell env**

Run: `( set -a && . devbox/.env.example && set +a && echo "$DOMAIN_BASE $SW66_IMAGE" )`
Expected: `twint-dev dockware/dev:6.6.7.0`

- [ ] **Step 4: Commit**

```bash
git add devbox/.gitignore devbox/.env.example
git commit -m "feat(devbox): add config scaffold (.gitignore, .env.example)"
```

---

### Task 3: `compose.yaml` — Traefik proxy + three dockware instances

**Files:**
- Create: `devbox/compose.yaml`

**Interfaces:**
- Consumes: `DOMAIN_BASE`, `SW65_IMAGE`, `SW66_IMAGE`, `SW67_IMAGE` from `.env`.
- Produces: services named `proxy`, `sw65`, `sw66`, `sw67`; named volumes `swXX_db`, `swXX_media`; network `web`. All `bin/` scripts address instances by these exact service names.

- [ ] **Step 1: Create `devbox/compose.yaml`**

```yaml
name: devbox

services:
  proxy:
    image: traefik:v3.1
    container_name: devbox_proxy
    restart: unless-stopped
    command:
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--api.dashboard=true"
      - "--api.insecure=true"
    ports:
      - "80:80"
      - "127.0.0.1:8080:8080"   # dashboard, localhost only (reach via SSH tunnel)
    volumes:
      - "/var/run/docker.sock:/var/run/docker.sock:ro"
    networks:
      - web

  sw65:
    image: ${SW65_IMAGE}
    container_name: sw65
    restart: unless-stopped
    environment:
      - APP_URL=http://sw65.${DOMAIN_BASE}
      - XDEBUG_ENABLED=0
    volumes:
      - "./src/sw65:/var/www/html/custom/plugins/TwintPayment:rw"
      - "sw65_db:/var/lib/mysql"
      - "sw65_media:/var/www/html/public/media"
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.sw65.rule=Host(`sw65.${DOMAIN_BASE}`)"
      - "traefik.http.routers.sw65.entrypoints=web"
      - "traefik.http.services.sw65.loadbalancer.server.port=80"
    networks:
      - web

  sw66:
    image: ${SW66_IMAGE}
    container_name: sw66
    restart: unless-stopped
    environment:
      - APP_URL=http://sw66.${DOMAIN_BASE}
      - XDEBUG_ENABLED=0
    volumes:
      - "./src/sw66:/var/www/html/custom/plugins/TwintPayment:rw"
      - "sw66_db:/var/lib/mysql"
      - "sw66_media:/var/www/html/public/media"
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.sw66.rule=Host(`sw66.${DOMAIN_BASE}`)"
      - "traefik.http.routers.sw66.entrypoints=web"
      - "traefik.http.services.sw66.loadbalancer.server.port=80"
    networks:
      - web

  sw67:
    image: ${SW67_IMAGE}
    container_name: sw67
    restart: unless-stopped
    environment:
      - APP_URL=http://sw67.${DOMAIN_BASE}
      - XDEBUG_ENABLED=0
    volumes:
      - "./src/sw67:/var/www/html/custom/plugins/TwintPayment:rw"
      - "sw67_db:/var/lib/mysql"
      - "sw67_media:/var/www/html/public/media"
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.sw67.rule=Host(`sw67.${DOMAIN_BASE}`)"
      - "traefik.http.routers.sw67.entrypoints=web"
      - "traefik.http.services.sw67.loadbalancer.server.port=80"
    networks:
      - web

volumes:
  sw65_db:
  sw65_media:
  sw66_db:
  sw66_media:
  sw67_db:
  sw67_media:

networks:
  web:
    external: false
```

- [ ] **Step 2: Validate compose with a throwaway env**

Run:
```bash
cp devbox/.env.example devbox/.env
docker compose -f devbox/compose.yaml config >/dev/null && echo "compose OK"
```
Expected: `compose OK` (variables interpolate, YAML is valid). Leave `devbox/.env` in place for later local checks — it is gitignored.

- [ ] **Step 3: Confirm expected services + no stray host ports on shops**

Run: `docker compose -f devbox/compose.yaml config --services | sort`
Expected (one per line): `proxy`, `sw65`, `sw66`, `sw67`.
Run: `docker compose -f devbox/compose.yaml config | grep -A2 'published'`
Expected: only the `proxy` service publishes ports (`80` and `127.0.0.1:8080`); no `swXX` publishes host ports.

- [ ] **Step 4: Commit**

```bash
git add devbox/compose.yaml
git commit -m "feat(devbox): compose stack (traefik + sw65/sw66/sw67)"
```

---

### Task 4: `bin/_lib.sh` — shared helpers

**Files:**
- Create: `devbox/bin/_lib.sh`

**Interfaces:**
- Produces (sourced by every other script): variables `DEVBOX_DIR`, `ENV_FILE`, array `INSTANCES=(sw65 sw66 sw67)`; functions `load_env`, `is_instance <name>`, `resolve_targets <all|swXX>` (prints one instance name per line), `git_remote_url` (prints `https://oauth2:$GITLAB_TOKEN@$GIT_REMOTE`), `dc <args...>` (runs `docker compose` pinned to the devbox project).

- [ ] **Step 1: Create `devbox/bin/_lib.sh`**

```bash
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

# Expand a target ("all" or a single instance) to a newline list of instances.
resolve_targets() {
  local target="${1:-}"
  if [ -z "$target" ]; then
    echo "ERROR: missing instance (one of: ${INSTANCES[*]} all)" >&2
    exit 1
  fi
  if [ "$target" = "all" ]; then
    printf '%s\n' "${INSTANCES[@]}"
    return 0
  fi
  if is_instance "$target"; then
    echo "$target"
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
```

- [ ] **Step 2: Syntax check**

Run: `bash -n devbox/bin/_lib.sh && echo OK`
Expected: `OK`

- [ ] **Step 3: Behavioral smoke-test of the pure-logic helpers**

Run:
```bash
bash -c '
  set -euo pipefail
  . devbox/bin/_lib.sh
  is_instance sw66 && echo "is_instance sw66: yes"
  is_instance nope || echo "is_instance nope: no"
  echo "resolve all -> $(resolve_targets all | tr "\n" " ")"
  echo "resolve sw67 -> $(resolve_targets sw67)"
'
```
Expected:
```
is_instance sw66: yes
is_instance nope: no
resolve all -> sw65 sw66 sw67 
resolve sw67 -> sw67
```

- [ ] **Step 4: Commit**

```bash
chmod +x devbox/bin/_lib.sh
git add devbox/bin/_lib.sh
git commit -m "feat(devbox): shared script helpers (_lib.sh)"
```

---

### Task 5: `bin/bootstrap.sh` — fresh-host Docker install (Ubuntu)

**Files:**
- Create: `devbox/bin/bootstrap.sh`

**Interfaces:**
- Consumes: nothing from `.env` (runs before config exists). Uses `sudo`, `apt-get`.
- Produces: Docker Engine + Compose plugin + git installed on the host.

- [ ] **Step 1: Create `devbox/bin/bootstrap.sh`**

```bash
#!/usr/bin/env bash
# One-time host setup for the devbox (Ubuntu/Debian). Idempotent.
set -euo pipefail

if ! command -v apt-get >/dev/null 2>&1; then
  echo "ERROR: bootstrap.sh targets Ubuntu/Debian (apt-based). Aborting." >&2
  exit 1
fi

echo "==> Installing prerequisites (ca-certificates, curl, git)"
sudo apt-get update -y
sudo apt-get install -y ca-certificates curl git

echo "==> Adding Docker's official apt repository"
sudo install -m 0755 -d /etc/apt/keyrings
if [ ! -f /etc/apt/keyrings/docker.asc ]; then
  sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  sudo chmod a+r /etc/apt/keyrings/docker.asc
fi
# shellcheck disable=SC1091
. /etc/os-release
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

echo "==> Installing Docker Engine + Compose plugin"
sudo apt-get update -y
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

echo "==> Enabling Docker service"
sudo systemctl enable --now docker

echo "==> Adding $USER to the docker group"
sudo usermod -aG docker "$USER"

echo
echo "Docker:  $(docker --version)"
echo "Compose: $(docker compose version)"
echo
echo "NOTE: log out and back in (or run 'newgrp docker') for docker-group"
echo "      membership to take effect before running devbox/bin/up.sh."
```

- [ ] **Step 2: Syntax check**

Run: `bash -n devbox/bin/bootstrap.sh && echo OK`
Expected: `OK`
(Do NOT run it locally — it mutates the host. It is exercised on `twint-dev` in Task 11.)

- [ ] **Step 3: Commit**

```bash
chmod +x devbox/bin/bootstrap.sh
git add devbox/bin/bootstrap.sh
git commit -m "feat(devbox): host bootstrap for Docker on Ubuntu"
```

---

### Task 6: `bin/up.sh` + `bin/down.sh` — lifecycle

**Files:**
- Create: `devbox/bin/up.sh`
- Create: `devbox/bin/down.sh`

**Interfaces:**
- Consumes: `_lib.sh` (`DEVBOX_DIR`, `INSTANCES`, `load_env`, `resolve_targets`, `dc`).
- `up.sh [all|swXX]` pre-creates `src/swXX` dirs (user-owned) then `dc up -d`. `down.sh [all|swXX]` stops without deleting volumes (never `-v`).

- [ ] **Step 1: Create `devbox/bin/up.sh`**

```bash
#!/usr/bin/env bash
# Start the stack (or one instance). Usage: up.sh [all|sw65|sw66|sw67]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

# Pre-create checkout dirs so Docker doesn't create them root-owned,
# which would break git clone / composer running as a non-root user.
for i in "${INSTANCES[@]}"; do
  mkdir -p "$DEVBOX_DIR/src/$i"
done

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc up -d
else
  mapfile -t svc < <(resolve_targets "$target")
  dc up -d proxy "${svc[@]}"
fi

dc ps
```

- [ ] **Step 2: Create `devbox/bin/down.sh`**

```bash
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
  mapfile -t svc < <(resolve_targets "$target")
  dc stop "${svc[@]}"
  dc rm -f "${svc[@]}"
fi
```

- [ ] **Step 3: Syntax check both**

Run: `bash -n devbox/bin/up.sh && bash -n devbox/bin/down.sh && echo OK`
Expected: `OK`

- [ ] **Step 4: Guard check — down.sh never removes volumes**

Run: `grep -n '\-v\b\|--volumes' devbox/bin/down.sh || echo "no volume-deletion flags: good"`
Expected: `no volume-deletion flags: good`

- [ ] **Step 5: Commit**

```bash
chmod +x devbox/bin/up.sh devbox/bin/down.sh
git add devbox/bin/up.sh devbox/bin/down.sh
git commit -m "feat(devbox): up/down lifecycle scripts"
```

---

### Task 7: `bin/deploy.sh` — git-pull plugin deploy

**Files:**
- Create: `devbox/bin/deploy.sh`

**Interfaces:**
- Consumes: `_lib.sh` (`DEVBOX_DIR`, `load_env`, `resolve_targets`, `git_remote_url`, `dc`), and `.env` (`GIT_REMOTE`, `GITLAB_TOKEN`).
- `deploy.sh <all|swXX> [ref]`, `ref` defaults to `master`. Detects branch vs commit, syncs `src/swXX`, runs composer + Shopware scripts in the container, scrubs the token from the checkout's git config afterward.

- [ ] **Step 1: Create `devbox/bin/deploy.sh`**

```bash
#!/usr/bin/env bash
# Deploy a git ref of the TWINT plugin into one/all instances.
# Usage: deploy.sh <all|sw65|sw66|sw67> [branch|commit]   (ref defaults to master)
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

TARGET="${1:-}"
REF="${2:-master}"
PLUGIN="TwintPayment"
PLUGIN_PATH="/var/www/html/custom/plugins/${PLUGIN}"

mapfile -t targets < <(resolve_targets "$TARGET")

sync_git() {
  local inst="$1" ref="$2"
  local dir="$DEVBOX_DIR/src/$inst"
  local authed clean
  authed="$(git_remote_url)"
  clean="https://$GIT_REMOTE"

  if [ ! -d "$dir/.git" ]; then
    echo "==> [$inst] cloning $GIT_REMOTE"
    git clone "$authed" "$dir"
  fi

  echo "==> [$inst] fetching"
  git -C "$dir" remote set-url origin "$authed"
  git -C "$dir" fetch --all --prune

  if git -C "$dir" rev-parse --verify --quiet "origin/$ref" >/dev/null; then
    echo "==> [$inst] checkout branch: $ref"
    git -C "$dir" checkout -B "$ref" "origin/$ref"
    git -C "$dir" reset --hard "origin/$ref"
  elif git -C "$dir" rev-parse --verify --quiet "${ref}^{commit}" >/dev/null; then
    echo "==> [$inst] checkout commit: $ref"
    git -C "$dir" checkout --detach "$ref"
  else
    # Restore clean remote before failing so no token lingers.
    git -C "$dir" remote set-url origin "$clean" || true
    echo "ERROR: [$inst] ref '$ref' is neither a remote branch nor a known commit" >&2
    exit 1
  fi

  # Scrub the token from on-disk git config.
  git -C "$dir" remote set-url origin "$clean"
}

deploy_plugin() {
  local inst="$1"

  echo "==> [$inst] composer install"
  dc exec -T "$inst" composer install -d "$PLUGIN_PATH" --no-interaction --no-progress

  echo "==> [$inst] plugin refresh"
  dc exec -T "$inst" php bin/console plugin:refresh

  echo "==> [$inst] install/activate (or update if already installed)"
  if ! dc exec -T "$inst" php bin/console plugin:install --activate "$PLUGIN"; then
    dc exec -T "$inst" php bin/console plugin:update "$PLUGIN"
  fi

  echo "==> [$inst] build administration + storefront"
  dc exec -T "$inst" bash -lc 'bin/build-administration.sh && bin/build-storefront.sh'

  echo "==> [$inst] cache:clear"
  dc exec -T "$inst" php bin/console cache:clear
}

for inst in "${targets[@]}"; do
  sync_git "$inst" "$REF"
  deploy_plugin "$inst"
  echo "==> [$inst] deployed ref '$REF'"
done
```

- [ ] **Step 2: Syntax check**

Run: `bash -n devbox/bin/deploy.sh && echo OK`
Expected: `OK`

- [ ] **Step 3: Guard check — token is scrubbed and never echoed**

Run: `grep -n 'git_remote_url\|set-url origin "\$clean"\|echo.*GITLAB_TOKEN' devbox/bin/deploy.sh`
Expected: shows the `git_remote_url` use and the two `set-url origin "$clean"` scrub lines; NO line that echoes `$GITLAB_TOKEN`.

- [ ] **Step 4: Commit**

```bash
chmod +x devbox/bin/deploy.sh
git add devbox/bin/deploy.sh
git commit -m "feat(devbox): git-pull plugin deploy script"
```

> Implementation note for the on-box pass (Task 11): the branch-vs-`plugin:update` fallback in `deploy_plugin` and the dockware paths (`bin/build-administration.sh`, `bin/build-storefront.sh`) are the two spots most likely to need a small per-version tweak. Verify them on `twint-dev` and adjust in this file if a version differs.

---

### Task 8: `bin/provision.sh` — set sales-channel domain

**Files:**
- Create: `devbox/bin/provision.sh`

**Interfaces:**
- Consumes: `_lib.sh` (`load_env`, `resolve_targets`, `dc`), `.env` (`DOMAIN_BASE`).
- `provision.sh [all|swXX]` sets each instance's sales-channel domain to `swXX.$DOMAIN_BASE`. Idempotent.

- [ ] **Step 1: Create `devbox/bin/provision.sh`**

```bash
#!/usr/bin/env bash
# One-time-per-instance: point the sales-channel domain at the subdomain so
# Shopware emits correct URLs behind Traefik. Idempotent.
# Usage: provision.sh [all|sw65|sw66|sw67]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

mapfile -t targets < <(resolve_targets "${1:-all}")
for inst in "${targets[@]}"; do
  host="${inst}.${DOMAIN_BASE}"
  echo "==> [$inst] set sales-channel domain -> http://$host"
  dc exec -T "$inst" php bin/console sales-channel:update:domain "$host"
done
```

- [ ] **Step 2: Syntax check**

Run: `bash -n devbox/bin/provision.sh && echo OK`
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
chmod +x devbox/bin/provision.sh
git add devbox/bin/provision.sh
git commit -m "feat(devbox): provision sales-channel domains"
```

> On-box note (Task 11): confirm the command name is `sales-channel:update:domain` on each version (`dc exec swXX php bin/console list sales-channel`); adjust if a version differs.

---

### Task 9: `bin/logs.sh` + `bin/shell.sh` — utilities

**Files:**
- Create: `devbox/bin/logs.sh`
- Create: `devbox/bin/shell.sh`

**Interfaces:**
- Consumes: `_lib.sh` (`load_env`, `resolve_targets`, `is_instance`, `INSTANCES`, `dc`).
- `logs.sh [all|swXX]` follows logs; `shell.sh <swXX>` opens a bash shell in an instance.

- [ ] **Step 1: Create `devbox/bin/logs.sh`**

```bash
#!/usr/bin/env bash
# Follow logs for the whole stack or one instance. Usage: logs.sh [all|sw65|sw66|sw67]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

target="${1:-all}"
if [ "$target" = "all" ]; then
  dc logs -f
else
  mapfile -t svc < <(resolve_targets "$target")
  dc logs -f "${svc[@]}"
fi
```

- [ ] **Step 2: Create `devbox/bin/shell.sh`**

```bash
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
```

- [ ] **Step 3: Syntax check both**

Run: `bash -n devbox/bin/logs.sh && bash -n devbox/bin/shell.sh && echo OK`
Expected: `OK`

- [ ] **Step 4: Commit**

```bash
chmod +x devbox/bin/logs.sh devbox/bin/shell.sh
git add devbox/bin/logs.sh devbox/bin/shell.sh
git commit -m "feat(devbox): logs + shell utility scripts"
```

---

### Task 10: Documentation (`README.md` + `docs/*.md`)

**Files:**
- Create: `devbox/README.md`
- Create: `devbox/docs/setup.md`
- Create: `devbox/docs/deploy.md`
- Create: `devbox/docs/operations.md`
- Create: `devbox/docs/dns-tls.md`
- Create: `devbox/docs/troubleshooting.md`
- Create: `devbox/docs/architecture.md`

- [ ] **Step 1: Create `devbox/README.md`**

````markdown
# devbox — multi-version Shopware dev box

Runs Shopware **6.5, 6.6, and 6.7 side by side** on the `twint-dev` EC2 box
behind a Traefik reverse proxy, so the TWINT plugin can be validated against
every supported version at once. Deploys pull any GitLab branch or commit into
a chosen instance.

> This folder is internal tooling. It is excluded from the public GitHub mirror
> (`.gitattributes` + `bin/sync.sh`). Do not commit `.env` or `src/`.

## Quickstart

```bash
# on twint-dev (fresh host), once:
bin/bootstrap.sh                 # install Docker + compose + git, then re-login
cp .env.example .env             # set DOMAIN_BASE, GIT_REMOTE, GITLAB_TOKEN
bin/up.sh                        # start traefik + all three instances
bin/provision.sh all             # set sales-channel domains
bin/deploy.sh all master         # deploy the plugin to all three

# on your laptop, once — /etc/hosts:
#   <ec2-ip> sw65.twint-dev sw66.twint-dev sw67.twint-dev

# iterate:
bin/deploy.sh sw66 my-feature-branch
bin/deploy.sh sw67 <commit-hash>
```

Then open `http://sw65.twint-dev`, `http://sw66.twint-dev`, `http://sw67.twint-dev`.

## Docs

| Doc | What's inside |
|-----|---------------|
| [docs/setup.md](docs/setup.md) | Fresh-host bootstrap and first run |
| [docs/deploy.md](docs/deploy.md) | `deploy.sh` in depth: refs, per-instance, what runs |
| [docs/operations.md](docs/operations.md) | Day-to-day up/down/logs/shell + data safety |
| [docs/dns-tls.md](docs/dns-tls.md) | `/etc/hosts` now → Route53 + TLS later |
| [docs/troubleshooting.md](docs/troubleshooting.md) | Common failures and fixes |
| [docs/architecture.md](docs/architecture.md) | How it fits together and why |
````

- [ ] **Step 2: Create `devbox/docs/setup.md`**

````markdown
# Setup (fresh EC2, Ubuntu)

## 0. Get the repo onto the box
A fresh Ubuntu image may lack `git`. Either `sudo apt-get install -y git` first,
or run `bootstrap.sh` (it installs git) after copying it over. Then clone this
repo and `cd` into `devbox/`.

## 1. Bootstrap the host
```bash
bin/bootstrap.sh
```
Installs Docker Engine + Compose plugin + git from Docker's official apt repo and
adds you to the `docker` group. **Log out and back in** (or `newgrp docker`) so
you can run `docker` without sudo.

## 2. Configure
```bash
cp .env.example .env
```
Set:
- `DOMAIN_BASE` — keep `twint-dev` for local `/etc/hosts` testing.
- `GIT_REMOTE` — GitLab host+path of the plugin repo (no scheme).
- `GITLAB_TOKEN` — a deploy token / PAT with `read_repository`.
- `SW65_IMAGE` / `SW66_IMAGE` / `SW67_IMAGE` — pinned dockware tags (defaults provided).

## 3. Start + provision + deploy
```bash
bin/up.sh                # traefik + sw65 + sw66 + sw67
bin/provision.sh all     # sales-channel domains -> swXX.$DOMAIN_BASE
bin/deploy.sh all master # clone + composer + install/activate + build
```

## 4. Laptop DNS (local testing)
Add to your laptop's `/etc/hosts` (get `<ec2-ip>` from AWS):
```
<ec2-ip> sw65.twint-dev sw66.twint-dev sw67.twint-dev
```
Open `http://sw66.twint-dev`, etc. For the real-domain path see
[dns-tls.md](dns-tls.md).
````

- [ ] **Step 3: Create `devbox/docs/deploy.md`**

````markdown
# Deploying the plugin

```
bin/deploy.sh <all|sw65|sw66|sw67> [branch|commit]
```
`ref` defaults to `master`.

## What it does, per instance
1. **Sync git** into `src/<instance>/` — clone on first run, then `fetch`, then
   check out the ref. A branch is reset hard to `origin/<branch>`; a commit hash
   is checked out detached. The GitLab token is used only during fetch and is
   scrubbed from `src/<instance>/.git/config` afterward.
2. **`composer install`** inside the container (so PHP-version-correct deps land
   in the mounted checkout's `vendor/`).
3. **Shopware**: `plugin:refresh` → `plugin:install --activate TwintPayment`
   (falls back to `plugin:update` if already installed) → build administration +
   storefront → `cache:clear`.

Because `src/<instance>` is bind-mounted, no container restart is needed.

## Examples
```bash
bin/deploy.sh all master                 # everyone on master
bin/deploy.sh sw66 feature/express-x     # 6.6 on a feature branch
bin/deploy.sh sw67 e8737e40              # 6.7 pinned to a commit
bin/deploy.sh sw65                       # 6.5 back to master (default)
```
Each instance keeps its own ref — deploying sw66 does not touch sw65/sw67.

## Idempotency
Re-running `deploy.sh` on an already-deployed instance updates rather than errors.
````

- [ ] **Step 4: Create `devbox/docs/operations.md`**

````markdown
# Operations

## Lifecycle
```bash
bin/up.sh [all|swXX]     # start (creates src/ dirs first)
bin/down.sh [all|swXX]   # stop — DATA IS PRESERVED (never uses -v)
bin/logs.sh [all|swXX]   # follow logs
bin/shell.sh <swXX>      # bash inside an instance
```

## Data safety
- DB and media live in per-instance named volumes (`swXX_db`, `swXX_media`).
- `bin/down.sh` stops containers but **keeps** volumes. `up.sh` again restores state.
- Data is destroyed only by an explicit manual action:
  ```bash
  docker volume rm devbox_sw66_db devbox_sw66_media   # wipe 6.6 only
  ```
- To reset one instance from scratch: `docker volume rm devbox_swXX_db devbox_swXX_media`
  then `bin/up.sh swXX && bin/provision.sh swXX && bin/deploy.sh swXX`.

## Traefik dashboard
Bound to `127.0.0.1:8080` on the box. View it via an SSH tunnel:
```bash
ssh -L 8080:localhost:8080 twint-dev    # then open http://localhost:8080
```
````

- [ ] **Step 5: Create `devbox/docs/dns-tls.md`**

````markdown
# DNS & TLS

## Now — local /etc/hosts (HTTP only)
`DOMAIN_BASE=twint-dev`. On each developer's laptop, add to `/etc/hosts`:
```
<ec2-ip> sw65.twint-dev sw66.twint-dev sw67.twint-dev
```
No TLS; access over `http://`.

## Later — Route53 + Let's Encrypt
1. **DNS:** create a wildcard record `*.<domain>` → EC2 IP in Route53.
2. **Config:** set `DOMAIN_BASE=<domain>` in `.env`; re-run
   `bin/provision.sh all` so sales-channel domains follow.
3. **TLS in `compose.yaml`** (proxy service): add a `websecure` entrypoint on
   `:443`, publish `443:443`, add a Let's Encrypt resolver, e.g.:
   ```yaml
   command:
     - "--entrypoints.websecure.address=:443"
     - "--certificatesresolvers.le.acme.tlschallenge=true"
     - "--certificatesresolvers.le.acme.email=ops@<domain>"
     - "--certificatesresolvers.le.acme.storage=/letsencrypt/acme.json"
   ```
   and per instance:
   ```yaml
   - "traefik.http.routers.swXX.entrypoints=websecure"
   - "traefik.http.routers.swXX.tls.certresolver=le"
   ```
   Persist `/letsencrypt` on the proxy with a named volume.

No change to shop services, volumes, or the bin/ scripts is required.
````

- [ ] **Step 6: Create `devbox/docs/troubleshooting.md`**

````markdown
# Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Storefront links/redirects point to `localhost` or wrong host | sales-channel domain not set | `bin/provision.sh <instance>` |
| Traefik 404 for `swXX.$DOMAIN_BASE` | hostname doesn't resolve, or label/`DOMAIN_BASE` mismatch | check laptop `/etc/hosts`; `docker compose config \| grep Host`; dashboard at `:8080` |
| Traefik 502 | instance still booting or Apache down | `bin/logs.sh <instance>`; wait for dockware to finish init |
| `bind: address already in use` on `:80` | something else owns port 80 on the host | stop it, or change the proxy's published port |
| `git clone`/`composer` permission denied in `src/swXX` | dir created root-owned before `up.sh` | `sudo chown -R $USER src/`; ensure `up.sh` ran first |
| `fatal: Authentication failed` on deploy | bad/expired `GITLAB_TOKEN` or wrong `GIT_REMOTE` | fix `.env`; token needs `read_repository` |
| plugin not visible after deploy | build or refresh failed mid-run | re-run `bin/deploy.sh <instance>`; check `bin/logs.sh` |
| DB empty after recreate | volume was removed (`docker volume rm` / `down -v`) | expected; re-provision + re-deploy |
````

- [ ] **Step 7: Create `devbox/docs/architecture.md`**

````markdown
# Architecture

```
                          ┌─ Host(sw65.$DOMAIN_BASE) → sw65 :80  (dockware 6.5.8.0)
Browser ─:80→ Traefik ────┼─ Host(sw66.$DOMAIN_BASE) → sw66 :80  (dockware 6.6.7.0)
                          └─ Host(sw67.$DOMAIN_BASE) → sw67 :80  (dockware 6.7.2.2)
```

## Why these choices
- **Traefik reverse proxy** — only the proxy publishes `:80`; shop containers
  publish nothing and are routed by Host header over the shared `web` network.
  This is what lets all three versions run at once (the legacy `infra/` setup
  collided on `80/443/3306`). Scales to more versions by adding a service +
  labels; enables a clean TLS cutover later.
- **dockware/dev all-in-one** — Apache + MySQL + Shopware in one container per
  version. Simplest thing to route and to run `bin/console` against; matches the
  legacy demo setup.
- **Named volumes for DB + media** — on first `up`, an empty volume mounted over
  a populated image path makes Docker copy the image's data in, so dockware's
  pre-installed DB is seeded and then persists. Core code/assets are not
  persisted (regenerated by `deploy.sh`), keeping recreates cheap.
- **Git-checkout mount (`src/swXX`)** — deploys come from a pushed GitLab ref,
  not the developer's working tree, so each instance can sit on a different
  branch/commit and deploys are reproducible.

## Files
- `compose.yaml` — proxy + three instances, volumes, network.
- `bin/_lib.sh` — env loading, instance map, compose wrapper, git URL builder.
- `bin/{bootstrap,up,down,deploy,provision,logs,shell}.sh` — see README table.
````

- [ ] **Step 8: Verify all docs exist and links resolve**

Run:
```bash
ls devbox/README.md devbox/docs/{setup,deploy,operations,dns-tls,troubleshooting,architecture}.md
```
Expected: all seven paths listed, no "No such file".

- [ ] **Step 9: Commit**

```bash
git add devbox/README.md devbox/docs
git commit -m "docs(devbox): README + operational docs"
```

---

### Task 11: On-box acceptance on `twint-dev`

Run the whole thing end-to-end on the real box and confirm each success criterion. This is the only task that proves functional behavior; earlier tasks only proved syntax/config validity. Fix any issue in the relevant script/file and re-commit (note the two flagged spots: deploy's install/update fallback + dockware build paths, and provision's command name).

**Preconditions:** you can `ssh twint-dev`; the repo is cloned there; you have sudo; `.env` has a real `GIT_REMOTE` + `GITLAB_TOKEN`; laptop `/etc/hosts` has the three subdomains.

- [ ] **Step 1: Bootstrap the host**

Run (on twint-dev): `devbox/bin/bootstrap.sh` then re-login.
Expected: prints Docker + Compose versions; `docker ps` works without sudo after re-login.

- [ ] **Step 2: Configure and start**

Run: `cp devbox/.env.example devbox/.env` and edit real values, then `devbox/bin/up.sh`.
Expected: `dc ps` shows `devbox_proxy`, `sw65`, `sw66`, `sw67` all `Up`. No port-conflict errors — **success criterion: no host-port conflicts**.

- [ ] **Step 3: Provision + deploy all**

Run: `devbox/bin/provision.sh all && devbox/bin/deploy.sh all master`
Expected: each instance clones, `composer install` succeeds, plugin installs+activates, admin+storefront build, cache clears — no errors.

- [ ] **Step 4: Verify three instances serve concurrently**

Run (from laptop):
```bash
for h in sw65 sw66 sw67; do echo -n "$h: "; curl -s -o /dev/null -w '%{http_code}\n' http://$h.twint-dev/; done
```
Expected: three `200` (or `30x` to the storefront) responses — **success criterion: all three load concurrently**. Open one admin (`http://sw66.twint-dev/admin`) and confirm the TWINT plugin shows as active — **success criterion: plugin installed/activated/built**.

- [ ] **Step 5: Verify per-instance refs**

Run: `devbox/bin/deploy.sh sw67 <some-known-commit-hash>`
Expected: sw67 checks out that commit; `git -C devbox/src/sw67 rev-parse HEAD` matches; sw65/sw66 unchanged (`git -C devbox/src/sw66 rev-parse --abbrev-ref HEAD` still `master`) — **success criteria: per-instance branch + commit deploys**.

- [ ] **Step 6: Verify idempotent re-deploy**

Run: `devbox/bin/deploy.sh sw66 master` a second time.
Expected: completes without error (updates rather than failing on already-installed) — **success criterion: idempotent re-deploy**.

- [ ] **Step 7: Verify persistence**

Run: upload a product image via sw66 admin (creates media), then `devbox/bin/down.sh && devbox/bin/up.sh`.
Expected: after restart, sw66 is still installed and the uploaded media is still present — **success criterion: DB + media survive down/up**.

- [ ] **Step 8: Verify token not persisted**

Run: `grep -r 'oauth2:' devbox/src/*/.git/config || echo "no token in checkouts: good"`
Expected: `no token in checkouts: good` — **constraint: token never left in checkout config**.

- [ ] **Step 9: Commit any on-box fixes**

If Steps 3–8 required edits, commit them:
```bash
git add devbox/bin devbox/compose.yaml
git commit -m "fix(devbox): on-box acceptance adjustments"
```

---

## Self-Review

**Spec coverage** — every spec section maps to a task:
- Access/routing (Traefik, subdomains, no shop host ports) → Task 3, verified Task 11 Step 4.
- DNS `/etc/hosts` now, Route53 later → `.env` `DOMAIN_BASE` (Task 2), docs/dns-tls.md (Task 10).
- Container base dockware all-in-one → Task 3.
- Plugin deploy via git ref (branch default master / commit) → Task 7.
- Checkout model fixed per-instance dir, fetch-in-place → Task 7 `sync_git`.
- GitLab auth HTTPS+token, scrubbed from config → Task 4 `git_remote_url` + Task 7 scrub, verified Task 11 Step 8.
- Versions 6.5/6.6/6.7 pinned → Global Constraints + Task 2 `.env.example` + Task 3.
- Persistence DB + media per instance, first-run volume seeding → Task 3 volumes, docs Task 10, verified Task 11 Step 7.
- Location new `devbox/`, legacy `infra/` untouched → all tasks scoped to `devbox/`; Task 1 only edits `.gitattributes`/`sync.sh`.
- Host bootstrap fresh Ubuntu → Task 5.
- `src/swXX` dir pre-creation / ownership gotcha → Task 6 `up.sh`.
- `.env` sourcing in scripts → Task 4 `load_env`.
- deploy.sh composer (absolute `-d`), refresh/install/build/cache → Task 7.
- provision.sh sales-channel domain → Task 8.
- logs/shell utilities → Task 9.
- Documentation set (README + 6 docs) → Task 10.
- Public-mirror exclusion of `devbox/` → Task 1.
- All success criteria → Task 11 steps, each annotated.

**Placeholder scan** — no "TBD/TODO-implement-later" in steps; the only literal `TODO` is inside `.env.example` telling the operator to insert the real `GIT_REMOTE`, which is intended config guidance, not a plan gap.

**Type/name consistency** — `_lib.sh` exposes `DEVBOX_DIR`, `ENV_FILE`, `INSTANCES`, `load_env`, `is_instance`, `resolve_targets`, `git_remote_url`, `dc`; every consuming script (Tasks 6–9) uses exactly those names. Service names `proxy`/`sw65`/`sw66`/`sw67` and volume names `swXX_db`/`swXX_media` are identical across `compose.yaml`, scripts, and docs. Plugin path `/var/www/html/custom/plugins/TwintPayment` is identical in `compose.yaml` and `deploy.sh`.

## Open config values (operator fills in, not plan gaps)
- `GIT_REMOTE` real GitLab host/path — placeholder in `.env.example`.
- `GITLAB_TOKEN` real token — placeholder in `.env.example`.
- 6.6 tag pinned to `6.6.7.0` (matches `ci66`); change in `.env` if a different 6.6 is wanted.
