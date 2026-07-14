# Devbox: Multi-version Shopware dev box — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up a `devbox/` toolset that runs Shopware 6.5, 6.6, and 6.7 concurrently on the fresh EC2 dev box (`ssh twint-dev`) behind a Traefik reverse proxy, each instance persisting its full filesystem + DB, and a `deploy.sh [ref]` that installs a chosen GitLab branch/commit of the TWINT plugin — via Composer — onto all three at once.

**Architecture:** One Docker Compose project = one Traefik proxy (`:80`) + three `dockware/dev` all-in-one containers on a shared `web` network. Traefik routes `swXX-$DOMAIN_BASE` (hyphen-joined) by Host header to each container's internal port 80. Each instance persists two named volumes — `swXX_html` (`/var/www/html`) and `swXX_db` (`/var/lib/mysql`). The plugin is delivered by `deploy.sh` running `composer require` of the GitLab package inside each container (no bind-mount, no host checkout). Bash scripts in `devbox/bin/` wrap host bootstrap, compose lifecycle, Composer deploy, and provisioning.

**Tech Stack:** Docker Engine + Compose v2, Traefik v3, dockware/dev images, Bash, Composer (GitLab VCS + gitlab-token), Shopware CLI (`bin/console`).

**Spec:** `docs/superpowers/specs/2026-07-14-devbox-multi-version-shopware-design.md`

## Global Constraints

- Instances and their pinned images: `sw65` → `dockware/dev:6.5.8.0`, `sw66` → `dockware/dev:6.6.7.0`, `sw67` → `dockware/dev:6.7.2.2` (image tags come from `.env`; `6.6.7.0` matches `ci66`).
- Plugin name is `TwintPayment`; in-container path is `/var/www/html/custom/plugins/TwintPayment`.
- dockware default DB is used as-is: user `root` / password `root`, database `shopware`.
- Hostnames are **hyphen-joined**: instance `swXX` is served at `swXX-$DOMAIN_BASE` (Traefik rule `Host(\`swXX-${DOMAIN_BASE}\`)`, `APP_URL=http://swXX-${DOMAIN_BASE}`). With `DOMAIN_BASE=twint-dev` → `sw65-twint-dev`; with `DOMAIN_BASE=twint.dev.nfq-asia.com` → `sw65-twint.dev.nfq-asia.com`.
- v1 is **HTTP only** on `:80`. No TLS, no `:443` (Route53 + Let's Encrypt is a documented later step, not built here).
- Persistence: per-instance named volumes `swXX_html` → `/var/www/html` (full Shopware filesystem, so all config/state/media survive recreates) and `swXX_db` → `/var/lib/mysql`. Media (`public/media`) lives inside the html volume — no separate media volume.
- **Volume-seeding caveat:** a named volume seeds from the image only on first creation. Once `swXX_html` exists, bumping `SWXX_IMAGE` will NOT upgrade that instance's Shopware code — you must remove the instance's volumes to re-seed. Documented in operations/troubleshooting.
- Plugin delivery is **Composer VCS require**, run inside each container — no host checkout, no bind-mount, no copy. `deploy.sh` registers TWO Composer VCS repositories — the plugin (`GIT_REMOTE`) and its private dependency `twint-ag/sdk` (`SDK_REMOTE`) — then runs `composer require twint-ag/twint-shopware-plugin:<constraint>` per instance, so each instance resolves the plugin and its deps (`twint-ag/sdk` private VCS, `chillerlan/php-qrcode` public) into its own `vendor/` for its own PHP version. All instances run the **same** deployed ref.
- `deploy.sh` signature is `deploy.sh [ref]` (ref defaults to `master`): maps the ref to a Composer constraint (branch `x` → `dev-x`; 7–40-hex commit `<sha>` → `dev-master#<sha>`) and runs composer require + Shopware install/build on every instance.
- GitLab auth for Composer: `deploy.sh` first registers the self-hosted host(s) in Composer `gitlab-domains` (REQUIRED for self-hosted GitLab — `gitlab-token` and the GitLab driver default to `gitlab.com` only, so without this the token is ignored and auth fails), then runs `composer config --auth gitlab-token.<host> $GITLAB_TOKEN` for BOTH the plugin host (`GIT_REMOTE` up to first `/`) and the SDK host (`SDK_REMOTE` up to first `/`) inside each container (persists in that instance's `auth.json` within the html volume). VCS URLs = `https://$GIT_REMOTE` and `https://$SDK_REMOTE`. The single `GITLAB_TOKEN` must have access to both repos. `SDK_REMOTE` is required — `deploy.sh` fails fast if unset.
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
  .gitignore                           # Task 2 (ignores /.env)
  .env.example                         # Task 2
  compose.yaml                         # Task 3 — per-instance sw*_html + sw*_db volumes, no plugin mount
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
- Produces: the env variables every script and `compose.yaml` consume — `DOMAIN_BASE`, `GIT_REMOTE`, `SDK_REMOTE`, `GITLAB_TOKEN`, `SW65_IMAGE`, `SW66_IMAGE`, `SW67_IMAGE`.

- [ ] **Step 1: Create `devbox/.gitignore`**

```gitignore
# Host-specific — never commit
/.env
```

- [ ] **Step 2: Create `devbox/.env.example`**

```dotenv
# ── Devbox configuration ─────────────────────────────────────────────
# Copy to `.env` (gitignored) and fill in. Compose auto-reads .env; the
# bin/ scripts source it too (via _lib.sh).

# Base domain. Instances are served at swXX-$DOMAIN_BASE (hyphen-joined):
#   sw65-$DOMAIN_BASE, sw66-$DOMAIN_BASE, sw67-$DOMAIN_BASE
#   Local testing: keep 'twint-dev' -> sw65-twint-dev etc.; add /etc/hosts entries.
#   Real domain (Route53): e.g. twint.dev.nfq-asia.com -> sw65-twint.dev.nfq-asia.com
DOMAIN_BASE=twint-dev

# GitLab plugin repo as host+path WITHOUT scheme.
#   deploy.sh registers it as a Composer VCS repo and derives the GitLab host.
GIT_REMOTE=git.nfq.asia/twint-ag/twint-shopware-plugin.git

# Private VCS dependency of the plugin (twint-ag/sdk), host+path WITHOUT scheme.
#   Registered as a second Composer VCS repo; uses the same GITLAB_TOKEN. Required.
SDK_REMOTE=git.nfq.asia/twint-ag/sdk.git

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

> **AMENDED after implementation (see committed `devbox/compose.yaml`, authoritative):** per-instance volumes are now `swXX_html:/var/www/html` (full filesystem persistence) + `swXX_db:/var/lib/mysql`. There is **no** plugin bind-mount and **no** `swXX_media` volume (media lives inside the html volume; the plugin is copied in by `deploy.sh`). The YAML block below shows the original DB+media+mount design and is retained for history only — do not implement it verbatim.

**Files:**
- Create: `devbox/compose.yaml`

**Interfaces:**
- Consumes: `DOMAIN_BASE`, `SW65_IMAGE`, `SW66_IMAGE`, `SW67_IMAGE` from `.env`.
- Produces: services named `proxy`, `sw65`, `sw66`, `sw67`; named volumes `swXX_html`, `swXX_db`; network `web`. All `bin/` scripts address instances by these exact service names.

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

> **AMENDED after review (see committed `devbox/bin/_lib.sh`, authoritative):** `resolve_targets` no longer prints lines. It runs in the caller's shell, populates a global array `RESOLVED_TARGETS`, and `exit 1`s on invalid/missing input. Callers use it as: `resolve_targets "$x"` then read `"${RESOLVED_TARGETS[@]}"` — never via `$(...)` or `< <(...)`, which would swallow the failure. (Reason: `exit 1` inside a process-substitution subshell is invisible to the parent, so an invalid instance would silently no-op.)

**Interfaces:**
- Produces (sourced by every other script): variables `DEVBOX_DIR`, `ENV_FILE`, array `INSTANCES=(sw65 sw66 sw67)`; functions `load_env`, `is_instance <name>`, `resolve_targets <all|swXX>` (populates array `RESOLVED_TARGETS` in the caller's shell; `exit 1` on bad input), `dc <args...>` (runs `docker compose` pinned to the devbox project). (The `git_remote_url` helper shown in the code block below was removed when delivery switched to Composer — see committed `_lib.sh`.)

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
- Consumes: `_lib.sh` (`load_env`, `resolve_targets` → `RESOLVED_TARGETS`, `dc`).
- `up.sh [all|swXX]` runs `dc up -d` (whole stack, or proxy + one instance). `down.sh [all|swXX]` stops without deleting volumes (never `-v`). No host `src` dir is needed — the plugin is delivered by Composer inside the containers.

- [ ] **Step 1: Create `devbox/bin/up.sh`**

```bash
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
  resolve_targets "$target"
  dc stop "${RESOLVED_TARGETS[@]}"
  dc rm -f "${RESOLVED_TARGETS[@]}"
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
- Consumes: `_lib.sh` (`load_env`, `INSTANCES`, `dc`), and `.env` (`GIT_REMOTE`, `SDK_REMOTE`, `GITLAB_TOKEN`).
- `deploy.sh [ref]`, `ref` defaults to `master`. Maps the ref to a Composer constraint, then for EVERY instance: configures the GitLab VCS repo + token, `composer require`s the plugin, and runs the Shopware install/build sequence. No host checkout; no per-instance target (all instances get the same ref).

- [ ] **Step 1: Create `devbox/bin/deploy.sh`**

```bash
#!/usr/bin/env bash
# Deploy a git ref of the TWINT plugin to ALL instances via Composer.
# Usage: deploy.sh [branch|commit]   (ref defaults to master)
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

REF="${1:-master}"
PLUGIN="TwintPayment"
PLUGIN_PACKAGE="twint-ag/twint-shopware-plugin"
GITLAB_HOST="${GIT_REMOTE%%/*}"        # e.g. git.nfq.asia
VCS_URL="https://${GIT_REMOTE}"        # e.g. https://git.nfq.asia/twint-ag/twint-shopware-plugin.git

# Private VCS dependency of the plugin (twint-ag/sdk). Same GitLab access/token.
SDK_REMOTE="${SDK_REMOTE:?SDK_REMOTE not set in .env (host+path of the private twint-ag/sdk repo)}"
SDK_HOST="${SDK_REMOTE%%/*}"           # e.g. git.nfq.asia
SDK_URL="https://${SDK_REMOTE}"        # e.g. https://git.nfq.asia/twint-ag/sdk.git

# Map a friendly ref to a Composer constraint:
#   7-40 hex chars -> treated as a commit: dev-master#<sha>
#   anything else  -> treated as a branch: dev-<branch>
composer_constraint() {
  local ref="$1"
  if printf '%s' "$ref" | grep -Eq '^[0-9a-f]{7,40}$'; then
    echo "dev-master#${ref}"
  else
    echo "dev-${ref}"
  fi
}

deploy_to() {
  local inst="$1" constraint="$2"

  echo "==> [$inst] configuring Composer GitLab domains + repos + token"
  # Composer only applies gitlab-token / the GitLab driver to hosts listed in
  # gitlab-domains (default: gitlab.com). Register the self-hosted host(s).
  if [ "$SDK_HOST" = "$GITLAB_HOST" ]; then
    dc exec -T "$inst" composer config gitlab-domains "$GITLAB_HOST"
  else
    dc exec -T "$inst" composer config gitlab-domains "$GITLAB_HOST" "$SDK_HOST"
  fi
  dc exec -T "$inst" composer config repositories.twint vcs "$VCS_URL"
  # --auth writes to the project auth.json (persisted in the html volume); the
  # token value is passed as an argument, not echoed by this script.
  dc exec -T "$inst" composer config --auth "gitlab-token.${GITLAB_HOST}" "$GITLAB_TOKEN"

  # Register the plugin's private VCS dependency so Composer can resolve it.
  dc exec -T "$inst" composer config repositories.sdk vcs "$SDK_URL"
  dc exec -T "$inst" composer config --auth "gitlab-token.${SDK_HOST}" "$GITLAB_TOKEN"

  echo "==> [$inst] composer require ${PLUGIN_PACKAGE}:${constraint}"
  dc exec -T "$inst" composer require "${PLUGIN_PACKAGE}:${constraint}" \
    --no-interaction --no-progress --with-all-dependencies

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

CONSTRAINT="$(composer_constraint "$REF")"
echo "==> deploying ${PLUGIN_PACKAGE}:${CONSTRAINT} to all instances"
for inst in "${INSTANCES[@]}"; do
  deploy_to "$inst" "$CONSTRAINT"
  echo "==> [$inst] done"
done
echo "==> all instances on ${CONSTRAINT}"
```

- [ ] **Step 2: Syntax check**

Run: `bash -n devbox/bin/deploy.sh && echo OK`
Expected: `OK`

- [ ] **Step 3: Verify ref→constraint mapping (pure function, no Docker needed)**

Run:
```bash
bash -c '
  set -euo pipefail
  composer_constraint() { local ref="$1"; if printf "%s" "$ref" | grep -Eq "^[0-9a-f]{7,40}$"; then echo "dev-master#${ref}"; else echo "dev-${ref}"; fi; }
  echo "master       -> $(composer_constraint master)"
  echo "feature/x    -> $(composer_constraint feature/x)"
  echo "e8737e40     -> $(composer_constraint e8737e40)"
'
```
Expected:
```
master       -> dev-master
feature/x    -> dev-feature/x
e8737e40     -> dev-master#e8737e40
```

- [ ] **Step 4: Guard check — token passed as arg, never echoed**

Run: `grep -n 'echo.*GITLAB_TOKEN\|echo.*\$GITLAB_TOKEN' devbox/bin/deploy.sh || echo "token never echoed: good"`
Expected: `token never echoed: good`

- [ ] **Step 5: Commit**

```bash
chmod +x devbox/bin/deploy.sh
git add devbox/bin/deploy.sh
git commit -m "feat(devbox): Composer-based plugin deploy to all instances"
```

> Implementation note for the on-box pass (Task 11): these are the spots most likely to need a per-version tweak — (a) `composer require` succeeding against the running Shopware (the plugin's `shopware/*` requires must resolve to the installed platform, and `twint-ag/sdk` must be reachable); (b) whether Shopware detects the composer-managed plugin via `plugin:refresh` under the name `TwintPayment`; (c) the `plugin:install` vs `plugin:update` fallback; (d) the dockware build-script paths. Verify on `twint-dev` and adjust here if a version differs. The `dev-master#<sha>` mapping assumes the commit is reachable from `master`; document deploying an unmerged commit by pushing it to a branch and passing the branch name.

---

### Task 8: `bin/provision.sh` — set sales-channel domain

**Files:**
- Create: `devbox/bin/provision.sh`

**Interfaces:**
- Consumes: `_lib.sh` (`load_env`, `resolve_targets`, `dc`), `.env` (`DOMAIN_BASE`).
- `provision.sh [all|swXX]` sets each instance's sales-channel domain to `swXX-$DOMAIN_BASE`. Idempotent.

- [ ] **Step 1: Create `devbox/bin/provision.sh`**

```bash
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
  resolve_targets "$target"
  dc logs -f "${RESOLVED_TARGETS[@]}"
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
every supported version at once. `deploy.sh` installs a chosen GitLab
branch/commit of the plugin — via Composer — onto all three instances.

> This folder is internal tooling. It is excluded from the public GitHub mirror
> (`.gitattributes` + `bin/sync.sh`). Do not commit `.env`.

## Quickstart

```bash
# on twint-dev (fresh host), once:
bin/bootstrap.sh                 # install Docker + compose + git, then re-login
cp .env.example .env             # set DOMAIN_BASE, GIT_REMOTE, GITLAB_TOKEN
bin/up.sh                        # start traefik + all three instances
bin/provision.sh all             # set sales-channel domains
bin/deploy.sh master             # composer-install the plugin on all three

# on your laptop, once — /etc/hosts:
#   <ec2-ip> sw65-twint-dev sw66-twint-dev sw67-twint-dev

# iterate — deploy any branch/commit to all instances:
bin/deploy.sh my-feature-branch
bin/deploy.sh <commit-hash>
```

Then open `http://sw65-twint-dev`, `http://sw66-twint-dev`, `http://sw67-twint-dev`.

## Docs

| Doc | What's inside |
|-----|---------------|
| [docs/setup.md](docs/setup.md) | Fresh-host bootstrap and first run |
| [docs/deploy.md](docs/deploy.md) | `deploy.sh` in depth: refs → Composer constraints, what runs |
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
- `GITLAB_TOKEN` — a deploy token / PAT with `read_repository` + `read_api` (Composer needs `read_api` for GitLab VCS).
- `SW65_IMAGE` / `SW66_IMAGE` / `SW67_IMAGE` — pinned dockware tags (defaults provided).

## 3. Start + provision + deploy
```bash
bin/up.sh                # traefik + sw65 + sw66 + sw67
bin/provision.sh all     # sales-channel domains -> swXX.$DOMAIN_BASE
bin/deploy.sh master     # composer require + install/activate + build on all instances
```

## 4. Laptop DNS (local testing)
Add to your laptop's `/etc/hosts` (get `<ec2-ip>` from AWS):
```
<ec2-ip> sw65-twint-dev sw66-twint-dev sw67-twint-dev
```
Open `http://sw66-twint-dev`, etc. For the real-domain path see
[dns-tls.md](dns-tls.md).
````

- [ ] **Step 3: Create `devbox/docs/deploy.md`**

````markdown
# Deploying the plugin

```
bin/deploy.sh [branch|commit]
```
`ref` defaults to `master`. The plugin is installed on **all three instances**
(same ref everywhere) — the point is to validate one plugin version across
6.5/6.6/6.7 at once.

## Ref → Composer constraint
`deploy.sh` maps the ref you pass to a Composer version constraint:

| You pass | Composer constraint | Meaning |
|----------|---------------------|---------|
| `master` (default) | `dev-master` | tip of master |
| `feature/x` | `dev-feature/x` | tip of that branch |
| `e8737e40` (7–40 hex) | `dev-master#e8737e40` | that commit (must be reachable from master) |

To deploy an unmerged commit, push it to a branch and pass the branch name.

## What it does, for each instance
1. **Configure Composer**: register the self-hosted GitLab host(s) in
   `gitlab-domains` (required so Composer applies the token to a non-gitlab.com
   host), register the plugin's GitLab repo **and** its private `twint-ag/sdk`
   dependency (`SDK_REMOTE`) as VCS repositories, and set `gitlab-token` (from
   `GITLAB_TOKEN`) for both hosts in the instance's `auth.json`.
2. **`composer require twint-ag/twint-shopware-plugin:<constraint>`** — Composer
   pulls the plugin **and its dependencies** (`twint-ag/sdk` private VCS,
   `chillerlan/php-qrcode` public) into that instance's own `vendor/`, resolved
   for that instance's PHP version. `shopware/*` requirements are satisfied by
   the running platform.
3. **Shopware**: `plugin:refresh` → `plugin:install --activate TwintPayment`
   (falls back to `plugin:update` if already installed) → build administration +
   storefront → `cache:clear`.

Each instance has its own isolated copy (own `vendor/`, own build), persisted in
its `swXX_html` volume. No bind-mount, no host checkout.

## Examples
```bash
bin/deploy.sh master               # all three on master
bin/deploy.sh feature/express-x    # all three on a feature branch
bin/deploy.sh e8737e40             # all three on a specific commit
```

## Idempotency
Re-running `deploy.sh` on already-deployed instances updates rather than errors
(Composer updates the constraint; `plugin:update` runs if already installed).
````

- [ ] **Step 4: Create `devbox/docs/operations.md`**

````markdown
# Operations

## Lifecycle
```bash
bin/up.sh [all|swXX]     # start
bin/down.sh [all|swXX]   # stop — DATA IS PRESERVED (never uses -v)
bin/logs.sh [all|swXX]   # follow logs
bin/shell.sh <swXX>      # bash inside an instance
```

## Data safety
- Each instance persists its **full state** in two named volumes: `swXX_html`
  (`/var/www/html` — Shopware code, config, installed plugin, uploaded media)
  and `swXX_db` (`/var/lib/mysql`).
- `bin/down.sh` stops containers but **keeps** volumes. `up.sh` again restores state.
- Data is destroyed only by an explicit manual action:
  ```bash
  docker volume rm devbox_sw66_html devbox_sw66_db   # wipe 6.6 only
  ```
- To reset one instance from scratch: `docker volume rm devbox_swXX_html devbox_swXX_db`
  then `bin/up.sh swXX && bin/provision.sh swXX && bin/deploy.sh`.

## Upgrading a Shopware version (important)
A named volume seeds from the image **only on first creation**. Once `swXX_html`
exists, bumping `SWXX_IMAGE` in `.env` will **not** upgrade that instance — it
keeps the old code from the volume. To actually move to a new dockware tag:
```bash
bin/down.sh swXX
docker volume rm devbox_swXX_html devbox_swXX_db   # discard old code + data
bin/up.sh swXX && bin/provision.sh swXX && bin/deploy.sh
```

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
`DOMAIN_BASE=twint-dev`. Hostnames are hyphen-joined (`sw65-twint-dev`). On each
developer's laptop, add to `/etc/hosts`:
```
<ec2-ip> sw65-twint-dev sw66-twint-dev sw67-twint-dev
```
No TLS; access over `http://`.

## Later — Route53 + Let's Encrypt
1. **DNS:** create a wildcard record for the parent of the instance label →
   EC2 IP in Route53. With `DOMAIN_BASE=twint.dev.nfq-asia.com` the hosts are
   `sw65-twint.dev.nfq-asia.com`, so the wildcard is `*.dev.nfq-asia.com`.
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
| Traefik 404 for `swXX-$DOMAIN_BASE` | hostname doesn't resolve, or label/`DOMAIN_BASE` mismatch | check laptop `/etc/hosts`; `docker compose config \| grep Host`; dashboard at `:8080` |
| Traefik 502 | instance still booting or Apache down | `bin/logs.sh <instance>`; wait for dockware to finish init |
| `bind: address already in use` on `:80` | something else owns port 80 on the host | stop it, or change the proxy's published port |
| `composer require` fails auth / 404 for the plugin | bad/expired `GITLAB_TOKEN`, wrong `GIT_REMOTE`, or token missing `read_api` | fix `.env`; GitLab VCS needs `read_repository` + `read_api` |
| `composer require` can't find `twint-ag/sdk` | the SDK isn't reachable (packagist / GitLab registry) with this token | ensure the SDK source is configured/reachable in the container |
| plugin not visible after deploy | `plugin:refresh` didn't detect the composer-managed plugin, or build failed | `bin/shell.sh <inst>` then `php bin/console plugin:list`; re-run `bin/deploy.sh` |
| Bumped `SWXX_IMAGE` but Shopware version unchanged | `swXX_html` volume was seeded from the old image and persists | remove the instance's volumes and re-deploy (see operations.md → Upgrading) |
| Client IP shows as the proxy's container IP | no `TRUSTED_PROXIES` set for Shopware behind Traefik | set trusted proxies if IP-based logic matters (not needed for basic v1 HTTP use) |
| DB/media empty after recreate | volume was removed (`docker volume rm` / `down -v`) | expected; re-provision + re-deploy |
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
- **Full-filesystem persistence per instance (`swXX_html` + `swXX_db`)** — on
  first `up`, an empty named volume mounted over a populated image path makes
  Docker copy the image's data in, so dockware's pre-installed Shopware (code +
  DB) is seeded and then persists across recreates. All config, installed
  plugins, uploaded media (inside `/var/www/html/public/media`), and DB survive.
  Trade-off: a seeded volume pins that instance's Shopware code to the image it
  first saw — see operations.md → Upgrading.
- **Composer VCS delivery** — `deploy.sh` runs `composer require` of the plugin
  from the GitLab repo inside each container. Each instance resolves the plugin
  and its deps into its own `vendor/` for its own PHP version, fully isolated;
  all instances run the same deployed ref. Deploys are reproducible from a
  pushed GitLab ref, not a developer's working tree.

## Files
- `compose.yaml` — proxy + three instances, `swXX_html`/`swXX_db` volumes, network.
- `bin/_lib.sh` — env loading, instance map (`resolve_targets` → `RESOLVED_TARGETS`), compose wrapper.
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

Run: `devbox/bin/provision.sh all && devbox/bin/deploy.sh master`
Expected: for each instance, `composer require` of the plugin succeeds, `plugin:install --activate` runs, admin+storefront build, cache clears — no errors. (Watch for the flagged risks: `composer require` resolving against the platform, `twint-ag/sdk` reachability, `plugin:refresh` detecting the composer-managed plugin.)

- [ ] **Step 4: Verify three instances serve concurrently**

Run (from laptop):
```bash
BASE=$(grep '^DOMAIN_BASE=' devbox/.env | cut -d= -f2)
for h in sw65 sw66 sw67; do echo -n "$h: "; curl -s -o /dev/null -w '%{http_code}\n' "http://$h-$BASE/"; done
```
Expected: three `200` (or `30x` to the storefront) responses — **success criterion: all three load concurrently**. Open one admin (`http://sw66-$BASE/admin`) and confirm the TWINT plugin shows as active — **success criterion: plugin installed/activated/built**.

- [ ] **Step 5: Verify a specific commit deploys to all instances**

Run: `devbox/bin/deploy.sh <some-known-commit-hash>` (a commit reachable from master).
Then on each instance confirm the installed plugin matches:
```bash
for i in sw65 sw66 sw67; do echo "== $i =="; devbox/bin/shell.sh "$i" -c 'php bin/console plugin:list | grep -i twint'; done
```
Expected: all three show the TWINT plugin present/active — **success criterion: commit deploy to all instances**. (`shell.sh` opens interactive bash; for a scripted check use `docker compose exec <i> php bin/console plugin:list`.)

- [ ] **Step 6: Verify idempotent re-deploy**

Run: `devbox/bin/deploy.sh master` a second time.
Expected: completes without error on all instances (Composer update + `plugin:update`, not a hard failure on already-installed) — **success criterion: idempotent re-deploy**.

- [ ] **Step 7: Verify persistence**

Run: upload a product image via sw66 admin (creates media under `/var/www/html`), then `devbox/bin/down.sh && devbox/bin/up.sh`.
Expected: after restart, sw66 is still installed, its config intact, and the uploaded media still present — **success criterion: full state (DB + html) survives down/up via `swXX_html` + `swXX_db`**.

- [ ] **Step 8: Verify token not leaked into tracked files**

Run (from repo root): `git grep -I "$(grep GITLAB_TOKEN devbox/.env | cut -d= -f2)" -- . || echo "token not in tracked files: good"`
Also confirm `git status` shows `devbox/.env` is untracked/ignored.
Expected: `token not in tracked files: good`, `.env` ignored — **constraint: token never committed**. (The token intentionally lives in each instance's container `auth.json`, which is inside the non-tracked `swXX_html` volume — that is by design, not a leak.)

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
- Plugin deploy via git ref (branch default master / commit), same ref on all instances → Task 7 (Composer VCS require + ref→constraint mapping).
- Plugin delivery = Composer VCS require inside each container (no host checkout / mount / copy) → Task 7 `deploy_to`.
- GitLab auth for Composer (`gitlab-token`, token passed as arg not echoed) → Task 7; not committed → verified Task 11 Step 8.
- Versions 6.5/6.6/6.7 pinned → Global Constraints + Task 2 `.env.example` + Task 3.
- Persistence: full `/var/www/html` (`swXX_html`) + DB (`swXX_db`) per instance, first-run volume seeding, version-pin caveat → Task 3 volumes, docs Task 10 (operations/architecture), verified Task 11 Step 7.
- Location new `devbox/`, legacy `infra/` untouched → all tasks scoped to `devbox/`; Task 1 only edits `.gitattributes`/`sync.sh`.
- Host bootstrap fresh Ubuntu → Task 5.
- `.env` sourcing in scripts → Task 4 `load_env`.
- `resolve_targets` fails loudly (array-out, no subshell) → Task 4 (amended), consumed by Tasks 6/8/9.
- deploy.sh: composer require → refresh/install/build/cache on all instances → Task 7.
- provision.sh sales-channel domain → Task 8.
- logs/shell utilities → Task 9.
- Documentation set (README + 6 docs) → Task 10.
- Public-mirror exclusion of `devbox/` → Task 1.
- All success criteria → Task 11 steps, each annotated.

**Placeholder scan** — no "TBD/TODO-implement-later" in steps; the only literal `TODO` is inside `.env.example` telling the operator to insert the real `GIT_REMOTE`, which is intended config guidance, not a plan gap.

**Type/name consistency (final code)** — `_lib.sh` exposes `DEVBOX_DIR`, `ENV_FILE`, `INSTANCES`, `load_env`, `is_instance`, `resolve_targets` (→ `RESOLVED_TARGETS`), `dc`; every consuming script uses exactly those names. Service names `proxy`/`sw65`/`sw66`/`sw67` and volume names `swXX_html`/`swXX_db` are identical across `compose.yaml`, scripts, and docs. Plugin in-container path `/var/www/html/custom/plugins/TwintPayment` is referenced by `deploy.sh` (Composer installs the plugin there); it is NOT a compose mount. Env vars `DOMAIN_BASE`/`GIT_REMOTE`/`SDK_REMOTE`/`GITLAB_TOKEN`/`SWxx_IMAGE` are consistent across `.env.example`, `compose.yaml`, and scripts.

## Open config values (operator fills in, not plan gaps)
- `GIT_REMOTE` real GitLab host/path — placeholder in `.env.example`.
- `GITLAB_TOKEN` real token — placeholder in `.env.example`.
- 6.6 tag pinned to `6.6.7.0` (matches `ci66`); change in `.env` if a different 6.6 is wanted.
