# infra/ Local + Local-CI Rework Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the messy `infra/` with a clean woo-style `infra/local` (Shopware 6.5/6.6/6.7, pick-one, isolated data, browser-trusted HTTPS, auto TWINT-ready) plus `infra/ci/local-ci.sh` mirroring the GitLab `test` job.

**Architecture:** One `docker compose` file with three `dockware/dev` services gated by compose profiles (`sw65`/`sw66`/`sw67`) — only one runs at a time, each on its own named volumes. A Caddy proxy terminates TLS with an mkcert cert at the single stable host `https://shopware.twint.local`, reverse-proxying whichever container is up (shared network alias `shop`). `bin/up.sh [65|66|67]` boots the chosen version, waits for readiness, then pipes `bin/provision.sh` into the container to install/activate the plugin, build assets, and wire CHF/CH + payment + domain. `infra/ci/local-ci.sh` runs the CI `test` job (ecs + rector, PHP 8.1–8.5) in Docker.

**Tech Stack:** Docker Compose, `dockware/dev` images, Caddy 2, mkcert, MariaDB (bundled in dockware), Bash, `shivammathur/node:jammy` + `spc` (for CI).

## Global Constraints

- Plugin technical name: **`TwintPayment`** (class `Twint\TwintPayment`). DB `plugin.name = 'TwintPayment'`.
- Supported Shopware: **6.5, 6.6, 6.7** only.
- Single stable host: **`https://shopware.twint.local`** — https, no port, browser-trusted (TWINT-clean).
- dockware defaults: DB `root`/`root`, DB name `shopware`, admin `admin`/`shopware`, Shopware root `/var/www/html`, Apache on `:80`.
- One version up at a time (pick-one). Each version has **separate** named volumes (`swXX_html`, `swXX_db`).
- Private SDK auth (`git.nfq.asia`) only on dev branches: pass via `COMPOSER_AUTH` / `GITLAB_TOKEN`, applied **only when set** (empty = harmless no-op). Never commit secrets.
- local-CI mirrors the GitLab `test` job ONLY: `spc` → `composer install --optimize-autoloader` → `vendor/bin/ecs` → `vendor/bin/rector process src --dry-run`, matrix PHP **8.1–8.5**. No phpunit, no release.
- Everything self-contained under `infra/`; do not touch `devbox/`.

## File Structure

```
infra/
  .gitignore                 # NEW — block .env, certs, ci logs
  local/
    compose.yaml             # NEW — 3 dockware services (profiles) + caddy proxy
    .env.example             # NEW — GITLAB_USERNAME / GITLAB_TOKEN template
    proxy/Caddyfile          # NEW — TLS termination -> shop:80
    certs/.gitkeep           # NEW — mkcert output lands here (git-ignored)
    bin/up.sh                # NEW — up.sh [65|66|67]: boot + wait + provision
    bin/down.sh              # NEW — down [--volumes]
    bin/provision.sh         # NEW — runs INSIDE container (piped via stdin), idempotent
    bin/shell.sh             # NEW — shell into active container
    bin/logs.sh              # NEW — follow active container logs
    README.md                # NEW — one-time host setup + everyday use
  ci/
    local-ci.sh              # NEW — dockerized `test` job, PHP 8.1–8.5
    README.md                # NEW
    logs/.gitkeep            # NEW (git-ignored contents)
```

Deleted: `infra/demo65/`, `infra/demo66/`, `infra/demo67/`, `infra/local-67/`, `infra/ci/compose.yaml`, and stray `infra/demo66/*.bk|*.swp|env.backup`.

---

### Task 1: Remove old infra + scaffold new tree

**Files:**
- Delete: `infra/demo65/`, `infra/demo66/`, `infra/demo67/`, `infra/local-67/`, `infra/ci/compose.yaml`
- Create: `infra/.gitignore`, `infra/local/certs/.gitkeep`, `infra/ci/logs/.gitkeep`

- [ ] **Step 1: Delete the old infra dirs/files**

```bash
cd /Users/ngoctai.tran/TaiTran/NFQ/projects/twint/sw-plugin
git rm -r --quiet infra/demo65 infra/demo66 infra/demo67 infra/local-67 infra/ci/compose.yaml
# stray untracked backups under demo66 (if any remain on disk)
rm -f infra/demo66/.env.local.bk infra/demo66/.env.swp infra/demo66/env.backup 2>/dev/null || true
```

- [ ] **Step 2: Scaffold new directories**

```bash
mkdir -p infra/local/proxy infra/local/certs infra/local/bin infra/ci/logs
touch infra/local/certs/.gitkeep infra/ci/logs/.gitkeep
```

- [ ] **Step 3: Write `infra/.gitignore`**

```gitignore
# Local secrets + generated artifacts — never commit.
local/.env
local/certs/*
!local/certs/.gitkeep
ci/logs/*
!ci/logs/.gitkeep
```

- [ ] **Step 4: Verify old infra is gone and tree exists**

Run:
```bash
ls infra/demo65 infra/demo66 infra/demo67 infra/local-67 2>&1 | grep -c 'No such file'
ls -d infra/local/proxy infra/local/certs infra/local/bin infra/ci/logs
```
Expected: first command prints `4`; second lists all four dirs with no error.

- [ ] **Step 5: Commit**

```bash
git add -A infra
git commit -m "chore(infra): remove demo65/66/67 + local-67 + ci/compose, scaffold new tree"
```

---

### Task 2: compose.yaml + .env.example

**Files:**
- Create: `infra/local/compose.yaml`
- Create: `infra/local/.env.example`

**Interfaces:**
- Produces: services `sw65`/`sw66`/`sw67` (profiles `sw65`/`sw66`/`sw67`, network alias `shop`, volumes `swXX_html`+`swXX_db`), service `proxy` (publishes `443`). Consumed by `bin/up.sh`, `bin/down.sh`, `proxy/Caddyfile`.

- [ ] **Step 1: Write `infra/local/compose.yaml`**

```yaml
name: twint-sw-local

# Shared env for all three Shopware versions. COMPOSER_AUTH is only meaningful on
# dev branches that pin the private SDK (git.nfq.asia); empty creds = harmless.
x-sw-env: &sw-env
  XDEBUG_ENABLED: 0
  APP_URL: https://shopware.twint.local
  TRUSTED_PROXIES: "0.0.0.0/0"
  COMPOSER_AUTH: '{"http-basic":{"git.nfq.asia":{"username":"${GITLAB_USERNAME:-}","password":"${GITLAB_TOKEN:-}"}}}'

# Ports are identical across versions (only ONE runs at a time — no clash).
x-sw-ports: &sw-ports
  - "80:80"       # apache http (proxy reaches this internally too)
  - "3306:3306"   # mariadb
  - "8888:8888"   # watch administration
  - "9998:9998"   # watch storefront proxy
  - "9999:9999"   # watch storefront
  - "8025:8025"   # mailpit UI

services:
  sw65:
    profiles: ["sw65"]
    container_name: sw65
    image: dockware/dev:6.5.8.0
    restart: unless-stopped
    networks:
      web:
        aliases: [shop]
    ports: *sw-ports
    environment:
      <<: *sw-env
      PHP_VERSION: "8.2"
    volumes:
      - "sw65_html:/var/www/html"
      - "sw65_db:/var/lib/mysql"
      - "../../:/var/www/html/custom/plugins/TwintPayment:rw"
      # container-local vendor (host has none / a different PHP)
      - "/var/www/html/custom/plugins/TwintPayment/vendor"

  sw66:
    profiles: ["sw66"]
    container_name: sw66
    image: dockware/dev:6.6.7.0
    restart: unless-stopped
    networks:
      web:
        aliases: [shop]
    ports: *sw-ports
    environment:
      <<: *sw-env
      PHP_VERSION: "8.2"
    volumes:
      - "sw66_html:/var/www/html"
      - "sw66_db:/var/lib/mysql"
      - "../../:/var/www/html/custom/plugins/TwintPayment:rw"
      - "/var/www/html/custom/plugins/TwintPayment/vendor"

  sw67:
    profiles: ["sw67"]
    container_name: sw67
    image: dockware/dev:6.7.2.2
    restart: unless-stopped
    networks:
      web:
        aliases: [shop]
    ports: *sw-ports
    environment:
      <<: *sw-env
      PHP_VERSION: "8.3"
    volumes:
      - "sw67_html:/var/www/html"
      - "sw67_db:/var/lib/mysql"
      - "../../:/var/www/html/custom/plugins/TwintPayment:rw"
      - "/var/www/html/custom/plugins/TwintPayment/vendor"

  # TLS termination: https://shopware.twint.local -> whichever version is up
  # (all three share the network alias `shop`). Starts with any version profile.
  proxy:
    profiles: ["sw65", "sw66", "sw67"]
    container_name: sw-proxy
    image: caddy:2-alpine
    restart: unless-stopped
    networks:
      - web
    ports:
      - "443:443"
    volumes:
      - "./proxy/Caddyfile:/etc/caddy/Caddyfile:ro"
      - "./certs:/certs:ro"

volumes:
  sw65_html: {}
  sw65_db: {}
  sw66_html: {}
  sw66_db: {}
  sw67_html: {}
  sw67_db: {}

networks:
  web:
    driver: bridge
```

- [ ] **Step 2: Write `infra/local/.env.example`**

```bash
# infra/local/.env — copy to .env (git-ignored). Values are safe local defaults.
#
# Private TWINT SDK (twint-ag/sdk from git.nfq.asia) — ONLY needed on dev branches
# that pin the dev SDK, and only then with VPN. Leave BLANK on stable branches
# (public packagist SDK). dockware supplies DB (root/root) + admin (admin/shopware)
# itself, so nothing else is required here.
GITLAB_USERNAME=
GITLAB_TOKEN=
```

- [ ] **Step 3: Verify compose is valid**

Run:
```bash
cd infra/local && docker compose --profile sw67 config >/dev/null && echo OK; cd -
```
Expected: prints `OK` (no YAML/interpolation errors). A missing `.env` is fine — `${GITLAB_USERNAME:-}` defaults to empty.

- [ ] **Step 4: Commit**

```bash
git add infra/local/compose.yaml infra/local/.env.example
git commit -m "feat(infra): compose with sw65/66/67 profiles + caddy proxy"
```

---

### Task 3: TLS proxy config (Caddyfile)

**Files:**
- Create: `infra/local/proxy/Caddyfile`

**Interfaces:**
- Consumes: mkcert cert files `certs/shopware.twint.local.pem` + `certs/shopware.twint.local-key.pem` (mounted at `/certs`), upstream `shop:80` (network alias from Task 2).

- [ ] **Step 1: Write `infra/local/proxy/Caddyfile`**

```caddyfile
# Local TLS termination so the shop is https://shopware.twint.local (no port) —
# which TWINT accepts as a callback URL. Cert is mkcert (browser-trusted); Caddy
# does not attempt ACME. `shop` is a network alias shared by sw65/sw66/sw67, so
# this proxies whichever version is currently up.
{
	auto_https off
	admin off
}

shopware.twint.local:443 {
	tls /certs/shopware.twint.local.pem /certs/shopware.twint.local-key.pem
	reverse_proxy shop:80 {
		header_up Host {host}
		header_up X-Forwarded-Proto https
		header_up X-Forwarded-Host {host}
	}
}
```

- [ ] **Step 2: Verify Caddyfile syntax**

Run:
```bash
docker run --rm -v "$PWD/infra/local/proxy/Caddyfile":/etc/caddy/Caddyfile:ro \
  caddy:2-alpine caddy validate --config /etc/caddy/Caddyfile
```
Expected: ends with `Valid configuration` (warnings about missing cert files are fine — they exist only after mkcert runs).

- [ ] **Step 3: Commit**

```bash
git add infra/local/proxy/Caddyfile
git commit -m "feat(infra): caddy TLS termination for shopware.twint.local"
```

---

### Task 4: provision.sh (runs inside the container)

**Files:**
- Create: `infra/local/bin/provision.sh`

**Interfaces:**
- Consumes: nothing from the host — it is piped into the container via `docker compose exec -T <svc> bash -s` by `up.sh` (Task 5), so it needs no in-container path and is unaffected by the shadowed `vendor` volume.
- Produces: an installed+activated `TwintPayment`, built assets, and a shop wired to CHF/CH + payment method + the https domain. Idempotent (safe to re-run).

- [ ] **Step 1: Write `infra/local/bin/provision.sh`**

```bash
#!/usr/bin/env bash
# Runs INSIDE a dockware container (piped via `bash -s`). Idempotent.
set -euo pipefail

PLUGIN=TwintPayment
PLUGIN_DIR="/var/www/html/custom/plugins/${PLUGIN}"
HOST="https://shopware.twint.local"
cd /var/www/html

echo "== [1/6] composer install (plugin deps) =="
if [ ! -d "${PLUGIN_DIR}/vendor" ] || [ "${PLUGIN_DIR}/composer.json" -nt "${PLUGIN_DIR}/vendor" ]; then
  composer install -d "${PLUGIN_DIR}" --no-interaction --no-progress
else
  echo "   vendor up to date — skip"
fi

echo "== [2/6] plugin refresh + install/activate =="
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache "${PLUGIN}" 2>/dev/null \
  || php bin/console plugin:activate "${PLUGIN}" 2>/dev/null \
  || echo "   already installed + active"

echo "== [3/6] build admin + storefront assets =="
php bin/console bundle:dump
if [ -f bin/build-administration.sh ]; then bash bin/build-administration.sh; fi
if [ -f bin/build-storefront.sh ]; then bash bin/build-storefront.sh; fi
php bin/console assets:install

echo "== [4/6] shop config: CHF currency / CH country / payment / domain =="
mysql -uroot -proot shopware <<'SQL'
-- Ensure CHF currency exists (Shopware usually seeds it; create if absent).
INSERT INTO currency (id, iso_code, factor, symbol, position, item_rounding, total_rounding, created_at)
SELECT UNHEX('B7D2554B0CE847CD82F3AC7738289246'), 'CHF', 1, 'CHF', 1,
       '{"decimals":2,"interval":0.05,"roundForNet":true}',
       '{"decimals":2,"interval":0.05,"roundForNet":true}', NOW()
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE iso_code = 'CHF');

INSERT INTO currency_translation (currency_id, language_id, name, short_name, created_at)
SELECT c.id, l.id, 'Swiss franc', 'CHF', NOW()
FROM currency c CROSS JOIN language l
WHERE c.iso_code = 'CHF'
  AND NOT EXISTS (
    SELECT 1 FROM currency_translation ct WHERE ct.currency_id = c.id AND ct.language_id = l.id
  );

-- Resolve the default Storefront sales channel + CHF + CH.
SET @sc  := (SELECT sc.id FROM sales_channel sc
             WHERE sc.type_id = UNHEX('8A243080F92E4C719546314B577CF82B') LIMIT 1);
SET @chf := (SELECT id FROM currency WHERE iso_code = 'CHF' LIMIT 1);
SET @ch  := (SELECT id FROM country  WHERE iso = 'CH' LIMIT 1);

-- Activate CH; make it + CHF part of the sales channel and its defaults.
UPDATE country SET active = 1 WHERE id = @ch;
UPDATE sales_channel SET currency_id = @chf, country_id = @ch WHERE id = @sc;
INSERT IGNORE INTO sales_channel_currency (sales_channel_id, currency_id) VALUES (@sc, @chf);
INSERT IGNORE INTO sales_channel_country  (sales_channel_id, country_id)  VALUES (@sc, @ch);

-- Point the sales-channel domain at the https host (and CHF).
UPDATE sales_channel_domain
   SET url = 'https://shopware.twint.local', currency_id = @chf
 WHERE sales_channel_id = @sc
 ORDER BY (url LIKE 'http://localhost%') DESC
 LIMIT 1;

-- Activate all TwintPayment payment methods and assign them to the sales channel.
UPDATE payment_method SET active = 1
 WHERE plugin_id = (SELECT id FROM plugin WHERE name = 'TwintPayment');
INSERT IGNORE INTO sales_channel_payment_method (sales_channel_id, payment_method_id)
SELECT @sc, pm.id FROM payment_method pm
 WHERE pm.plugin_id = (SELECT id FROM plugin WHERE name = 'TwintPayment');
SQL

echo "== [5/6] cache clear =="
php bin/console cache:clear

echo "== [6/6] done -> ${HOST} =="
```

- [ ] **Step 2: Make it executable**

```bash
chmod +x infra/local/bin/provision.sh
```

- [ ] **Step 3: Syntax-check the script**

Run:
```bash
bash -n infra/local/bin/provision.sh && echo "syntax OK"
```
Expected: prints `syntax OK`.

- [ ] **Step 4: Commit**

```bash
git add infra/local/bin/provision.sh
git commit -m "feat(infra): idempotent in-container provisioning (plugin, assets, CHF/CH, domain)"
```

---

### Task 5: Wrapper scripts (up / down / shell / logs)

**Files:**
- Create: `infra/local/bin/up.sh`, `infra/local/bin/down.sh`, `infra/local/bin/shell.sh`, `infra/local/bin/logs.sh`

**Interfaces:**
- Consumes: compose services from Task 2, `provision.sh` from Task 4 (piped via stdin).
- Produces: `up.sh [65|66|67]`, `down.sh [--volumes]`, `shell.sh [65|66|67]`, `logs.sh [65|66|67]` — the documented entry points.

- [ ] **Step 1: Write `infra/local/bin/up.sh`**

```bash
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
```

- [ ] **Step 2: Write `infra/local/bin/down.sh`**

```bash
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
```

- [ ] **Step 3: Write `infra/local/bin/shell.sh`**

```bash
#!/usr/bin/env bash
# shell.sh [65|66|67] — open a bash shell in the running version's container.
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
V="${1:-67}"
docker compose exec "sw${V}" bash
```

- [ ] **Step 4: Write `infra/local/bin/logs.sh`**

```bash
#!/usr/bin/env bash
# logs.sh [65|66|67] — follow the running version's container logs.
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
V="${1:-67}"
docker compose logs -f "sw${V}"
```

- [ ] **Step 5: Make them executable + syntax-check**

Run:
```bash
chmod +x infra/local/bin/up.sh infra/local/bin/down.sh infra/local/bin/shell.sh infra/local/bin/logs.sh
for f in up down shell logs; do bash -n "infra/local/bin/$f.sh" || exit 1; done && echo "all syntax OK"
```
Expected: prints `all syntax OK`.

- [ ] **Step 6: Verify bad arg is rejected**

Run:
```bash
infra/local/bin/up.sh 99; echo "exit=$?"
```
Expected: prints `usage: up.sh [65|66|67]` and `exit=2`.

- [ ] **Step 7: Commit**

```bash
git add infra/local/bin/up.sh infra/local/bin/down.sh infra/local/bin/shell.sh infra/local/bin/logs.sh
git commit -m "feat(infra): up/down/shell/logs wrappers for pick-one versions"
```

---

### Task 6: local-CI (mirror the GitLab `test` job)

**Files:**
- Create: `infra/ci/local-ci.sh`
- Create: `infra/ci/README.md`

**Interfaces:**
- Consumes: `infra/local/.env` (optional `GITLAB_TOKEN`), the repo root (mounted read-only).
- Produces: `local-ci.sh tests [versions...]`; per-version logs in `infra/ci/logs/`.

- [ ] **Step 1: Write `infra/ci/local-ci.sh`**

```bash
#!/usr/bin/env bash
#
# Run the GitLab `test` job locally, in Docker, using the SAME image + spc as CI
# (shivammathur/node:jammy) — so a red result means a real problem, not a flaky
# shared runner. Mirrors `.gitlab-ci.yml` (test): spc -> composer install -> ecs
# -> rector --dry-run, matrix PHP 8.1-8.5.
#
# Usage:
#   infra/ci/local-ci.sh tests            # all versions, in parallel
#   infra/ci/local-ci.sh tests 8.3        # only PHP 8.3 (or a few)
#
set -uo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
LOGS="$HERE/logs"
IMAGE="shivammathur/node:jammy"
ALL_VERSIONS=(8.1 8.2 8.3 8.4 8.5)
EXT="mbstring, curl, dom, fileinfo, gd, iconv, intl, json, xml, pdo, phar, zip, sodium, pdo_mysql, bcmath, soap"

# Optional private-SDK token (dev branches) from infra/local/.env.
GITLAB_TOKEN=""
# shellcheck disable=SC1091
[ -f "$ROOT/infra/local/.env" ] && { set -a; . "$ROOT/infra/local/.env"; set +a; }

mkdir -p "$LOGS"

# Copy the repo into a container-local /work (exclude heavy dirs) so parallel jobs
# never clobber each other's composer.lock/vendor and the working tree is untouched.
COPY_SRC='mkdir -p /work && tar cf - -C /src --exclude=./.git --exclude=./vendor --exclude=./node_modules --exclude=./dist --exclude=./build . | tar xf - -C /work && cd /work'

tests_script() {
  cat <<EOF
set -euo pipefail
$COPY_SRC
spc -U
spc --php-version "\$PHP_VERSION" --extensions "$EXT"
[ -n "\${GITLAB_TOKEN:-}" ] && composer config --global gitlab-token.git.nfq.asia "\$GITLAB_TOKEN"
[ "\$PHP_VERSION" = "8.1" ] && composer config --global audit.block-insecure false
rm -f composer.lock
composer install --no-progress --optimize-autoloader
vendor/bin/ecs
vendor/bin/rector process src --dry-run
EOF
}

run_one() {
  local v="$1"
  docker run --rm \
    -e PHP_VERSION="$v" -e GITLAB_TOKEN="$GITLAB_TOKEN" \
    -v "$ROOT":/src:ro \
    "$IMAGE" bash -c "$(tests_script)" >"$LOGS/tests-$v.log" 2>&1
}

cmd_tests() {
  local versions=("$@")
  [ ${#versions[@]} -eq 0 ] && versions=("${ALL_VERSIONS[@]}")
  echo "== tests: ${versions[*]} (logs in infra/ci/logs/) =="
  local pids=() vers=() rc=0
  for v in "${versions[@]}"; do
    run_one "$v" &
    pids+=("$!"); vers+=("$v")
  done
  for i in "${!pids[@]}"; do
    if wait "${pids[$i]}"; then
      echo "  PASS  tests ${vers[$i]}"
    else
      echo "  FAIL  tests ${vers[$i]}  -> infra/ci/logs/tests-${vers[$i]}.log"; rc=1
    fi
  done
  return $rc
}

case "${1:-tests}" in
  tests) shift; cmd_tests "$@" ;;
  *) echo "usage: local-ci.sh tests [versions...]" >&2; exit 2 ;;
esac
```

- [ ] **Step 2: Write `infra/ci/README.md`**

````markdown
# Local CI (`infra/ci/`)

Run the GitLab `test` job on your machine, in Docker, using the **same image and
`spc` as CI** (`shivammathur/node:jammy`). Verify a branch before pushing — so a
red pipeline means a real problem, not a flaky shared runner.

## Usage

```bash
infra/ci/local-ci.sh tests            # all PHP 8.1–8.5 in parallel
infra/ci/local-ci.sh tests 8.3        # just one (or a few) versions
```

Per-version logs land in `infra/ci/logs/` (git-ignored). The command prints a
`PASS`/`FAIL` line per version.

## What it reproduces (from `.gitlab-ci.yml` → `test`)

Matrix PHP 8.1–8.5: `spc -U` → `spc --php-version X --extensions "…"` →
`composer install --optimize-autoloader` → `vendor/bin/ecs` →
`vendor/bin/rector process src --dry-run`. (No phpunit / no release — CI's `test`
job doesn't run them.)

## Notes

- Each job copies the repo into a container-local workdir (excluding
  `.git/vendor/node_modules/dist/build`), so parallel jobs never clobber each
  other and your working tree is untouched.
- Private-SDK auth (dev branches): `GITLAB_TOKEN` from `infra/local/.env` maps to
  CI's `gitlab-token.git.nfq.asia`. Blank on stable branches.
- If `.gitlab-ci.yml` changes, update `local-ci.sh` to match (extension list +
  step order live in both).
````

- [ ] **Step 3: Make executable + syntax-check**

Run:
```bash
chmod +x infra/ci/local-ci.sh
bash -n infra/ci/local-ci.sh && echo "syntax OK"
infra/ci/local-ci.sh bogus; echo "exit=$?"
```
Expected: `syntax OK`, then `usage: local-ci.sh tests [versions...]` and `exit=2`.

- [ ] **Step 4: Run one version end-to-end (real check)**

Run:
```bash
infra/ci/local-ci.sh tests 8.3
```
Expected: `PASS  tests 8.3`. (Pulls `shivammathur/node:jammy` on first run.) If it FAILs, open `infra/ci/logs/tests-8.3.log` — a genuine ecs/rector failure is a real finding, not an infra bug.

- [ ] **Step 5: Commit**

```bash
git add infra/ci/local-ci.sh infra/ci/README.md
git commit -m "feat(infra): local-ci.sh mirroring GitLab test job (ecs + rector, PHP 8.1-8.5)"
```

---

### Task 7: local/README.md (host setup + everyday use)

**Files:**
- Create: `infra/local/README.md`

- [ ] **Step 1: Write `infra/local/README.md`**

````markdown
# Local dev environment (`infra/local/`)

One Shopware version at a time (6.5, 6.6, or 6.7) via `dockware/dev`, with the
TWINT plugin **live-mounted** and auto-provisioned, served over browser-trusted
**HTTPS** at a single stable host.

| Command | Version | Image |
|---------|---------|-------|
| `bin/up.sh 65` | 6.5 | `dockware/dev:6.5.8.0` (PHP 8.2) |
| `bin/up.sh 66` | 6.6 | `dockware/dev:6.6.7.0` (PHP 8.2) |
| `bin/up.sh 67` | 6.7 | `dockware/dev:6.7.2.2` (PHP 8.3) |

Only one runs at a time; each keeps its **own** DB + Shopware files (per-version
Docker volumes). All are reached at **https://shopware.twint.local**.

## Prerequisites

- Docker + Docker Compose.
- **VPN + `GITLAB_TOKEN`** only if the checked-out branch pins the private dev SDK
  (`twint-ag/sdk` from `git.nfq.asia`). Stable branches (public SDK) need neither.
- TWINT **test** credentials (Store UUID + `.p12` + password) to run a payment.

## One-time host setup (HTTPS + hostname)

TWINT rejects a callback URL that is `localhost`, has a non-standard `:port`, or
is not `https`. So the shop is served as **`https://shopware.twint.local`** via
Caddy (`:443`) using a locally-trusted **mkcert** cert.

```bash
# 1. hostname alias
echo "127.0.0.1 shopware.twint.local" | sudo tee -a /etc/hosts

# 2. trusted local cert (installs a local CA your browser trusts)
brew install mkcert            # if not installed
mkcert -install                # one-time: add local CA to the trust store
cd infra/local/certs && mkcert shopware.twint.local && cd -
```

`certs/` is git-ignored. mkcert writes `shopware.twint.local.pem` +
`shopware.twint.local-key.pem` — the names the Caddyfile expects.

## Quickstart

```bash
cd infra/local
cp .env.example .env        # fill GITLAB_USERNAME/GITLAB_TOKEN only on a dev-SDK branch
bin/up.sh 67                # or 65 / 66
```

`up.sh` starts the version + proxy, waits until the shop answers, then provisions:
installs + activates **TwintPayment**, builds admin + storefront assets, and wires
the shop to **CHF** currency, **CH** country, the TWINT payment method, and the
https domain. First boot pulls the dockware image (a few GB) and builds assets —
give it a few minutes.

Then open:
- Storefront: **https://shopware.twint.local**
- Admin: **https://shopware.twint.local/admin** — `admin` / `shopware`

## Enter TWINT credentials (manual)

Admin → TWINT settings: enter the Store UUID, upload the `.p12`, enter the
password, Save. Then enable TWINT Checkout / Express Checkout. Credentials are
per-instance; secrets are never committed.

## Everyday use

- **Edit plugin PHP** → reflected immediately (source is bind-mounted).
- **Edit JS/SCSS** → rebuild assets: `bin/shell.sh 67` then
  `bash bin/build-administration.sh` / `bash bin/build-storefront.sh` (or re-run
  `bin/up.sh 67`, which rebuilds).
- **Console:** `bin/shell.sh 67` → `php bin/console <cmd>`.
- **Logs:** `bin/logs.sh 67`.
- **Switch version:** `bin/down.sh` then `bin/up.sh 65` — different volume, own
  data; the two never share state.
- **Stop (keep data):** `bin/down.sh`.
- **Full reset (drop all volumes):** `bin/down.sh --volumes`.

## Notes

- Shopware core comes from the dockware image, never committed.
- Each version builds its own plugin `vendor/` inside a container-local volume
  (the host tree is not used for vendor).
- This folder is self-contained and independent of `devbox/`.
````

- [ ] **Step 2: Verify no broken command references**

Run:
```bash
grep -oE 'bin/[a-z]+\.sh' infra/local/README.md | sort -u
ls infra/local/bin
```
Expected: every `bin/*.sh` named in the README exists in `infra/local/bin` (`up.sh`, `down.sh`, `shell.sh`, `logs.sh`).

- [ ] **Step 3: Commit**

```bash
git add infra/local/README.md
git commit -m "docs(infra): local README — host setup + everyday use"
```

---

### Task 8: End-to-end boot verification (integration)

**Files:** none created — this is the real-world acceptance run.

**Interfaces:**
- Consumes: everything from Tasks 1–7 + the one-time host setup (hosts entry + mkcert cert).

- [ ] **Step 1: Ensure one-time host setup is done**

Run:
```bash
grep -q 'shopware.twint.local' /etc/hosts || \
  echo "127.0.0.1 shopware.twint.local" | sudo tee -a /etc/hosts
ls infra/local/certs/shopware.twint.local.pem infra/local/certs/shopware.twint.local-key.pem 2>&1
```
Expected: both cert files listed. If missing: `mkcert -install` then
`cd infra/local/certs && mkcert shopware.twint.local && cd -`.

- [ ] **Step 2: Boot 6.7 + provision**

Run:
```bash
infra/local/bin/up.sh 67
```
Expected: ends with `Ready: https://shopware.twint.local` and no provisioning error (watch the `[1/6]`…`[6/6]` markers).

- [ ] **Step 3: Verify HTTPS storefront + admin (browser-trusted)**

Run:
```bash
curl -sS -o /dev/null -w '%{http_code}\n' https://shopware.twint.local/
curl -sS -o /dev/null -w '%{http_code}\n' https://shopware.twint.local/admin
```
Expected: `200` (or `30x` redirect) for both, with **no** TLS error (cert is trusted). If curl complains about the CA, the shell predates `mkcert -install` — re-open it.

- [ ] **Step 4: Verify plugin active + CHF wired**

Run:
```bash
docker compose -f infra/local/compose.yaml exec -T sw67 bash -lc \
  "php bin/console plugin:list | grep -i twint"
docker compose -f infra/local/compose.yaml exec -T sw67 bash -lc \
  "mysql -uroot -proot shopware -N -e \"SELECT c.iso_code FROM sales_channel sc JOIN currency c ON c.id=sc.currency_id WHERE sc.type_id=UNHEX('8A243080F92E4C719546314B577CF82B');\""
```
Expected: the `plugin:list` line shows `TwintPayment` **active/installed = Yes**; the SQL prints `CHF`.

- [ ] **Step 5: Verify version isolation (switch to 6.5)**

Run:
```bash
infra/local/bin/down.sh
infra/local/bin/up.sh 65
docker compose -f infra/local/compose.yaml exec -T sw65 bash -lc \
  "php bin/console plugin:list | grep -i twint"
```
Expected: 6.5 boots on its **own** volume (`sw65_db`/`sw65_html`), provisions, and shows `TwintPayment` active — independent of the 6.7 run.

- [ ] **Step 6: Tear down**

Run:
```bash
infra/local/bin/down.sh
```
Expected: all containers stopped; named volumes retained (data preserved for next boot).

- [ ] **Step 7: Commit any doc fixes surfaced during the run**

If the run revealed a doc/script discrepancy (e.g. a dockware admin password or an
image tag that resolved differently), fix it inline and commit:
```bash
git add -A infra
git commit -m "fix(infra): corrections from end-to-end boot verification"
```

---

## Self-Review Notes

- **Spec coverage:** delete old infra (T1) ✓; compose 3-versions/profiles/isolated volumes (T2) ✓; TLS single host + mkcert + Caddy (T3, T7, T8) ✓; provisioning full TWINT-ready incl. CHF/CH/payment/domain (T4) ✓; pick-one wrappers (T5) ✓; local-CI mirror of `test` job (T6) ✓; private-SDK auth optional (T2 COMPOSER_AUTH, T6 token) ✓; validation (T8) ✓.
- **Placeholders:** none — every file has complete content.
- **Type/name consistency:** service names `swXX`, network alias `shop`, volumes `swXX_html`/`swXX_db`, host `shopware.twint.local`, plugin `TwintPayment`, cert filenames `shopware.twint.local(.pem/-key.pem)` used identically across compose, Caddyfile, provision.sh, wrappers, README, and verification.
- **Known runtime risk (flagged for executor):** the provisioning SQL targets Shopware core tables directly. Column names (`item_rounding`/`total_rounding`, `sales_channel_country`, `sales_channel_payment_method`) and the Storefront type id `8A24…F82B` are stable across 6.5–6.7, but if a query fails on a specific dockware build, Step 4/5 of Task 8 will surface it — fix the SQL and re-run `up.sh` (idempotent).
- **dockware image tags** (`6.5.8.0`, `6.6.7.0`, `6.7.2.2`) come from the current `infra/ci/compose.yaml`; if a tag no longer resolves on Docker Hub, pick the nearest available patch of the same minor and update `compose.yaml` + README.
