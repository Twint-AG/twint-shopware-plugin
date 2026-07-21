# infra/ rework — local dev (Shopware 6.5/6.6/6.7) + local-CI

**Date:** 2026-07-21
**Status:** Approved (design)

## Goal

Replace the current inconsistent `infra/` with one clean, woo-style setup:

- `infra/local/` — Docker local dev for **all three supported Shopware versions**
  (6.5, 6.6, 6.7), pick-one at a time, each with **isolated data**, served over
  **browser-trusted HTTPS** at a single TWINT-clean hostname, auto-provisioned
  TWINT-ready.
- `infra/ci/` — a `local-ci.sh` that reproduces the GitLab `test` job in Docker.

Design deliberately mirrors `twint-woocommerce-extension/infra` (the reference):
self-contained `local/` + `ci/`, Caddy TLS proxy, mkcert certs, dockerized CI
matrix.

## Context / current state (being replaced)

`infra/` today holds three unrelated things, all removed by this rework:

- `demo65/`, `demo66/`, `demo67/` — full Shopware app trees committed into the
  repo (large, messy). Confirmed decision: **delete**.
- `local-67/` — single `dockware/dev:6.7.2.2` container, 6.7 only, plugin
  live-mounted. Superseded.
- `ci/compose.yaml` — three bare dockware containers (ci65/66/67), no
  orchestration. Superseded.

Facts that shaped the design:

- Plugin supports `shopware/core: ^6.5 || ^6.6 || ^6.7`.
- Plugin technical name: **`TwintPayment`** (`shopware-plugin-class:
  Twint\TwintPayment`).
- SDK `twint-ag/sdk: ^1.8.0` is **public (packagist)** — local `composer install`
  needs **no** private git auth / VPN (simpler than woo).
- GitLab `test` job (`.gitlab-ci.yml`): image `shivammathur/node:jammy`, matrix
  PHP **8.1–8.5**, steps `spc -U` → `spc --php-version X --extensions "…"` →
  `composer install --optimize-autoloader` → `vendor/bin/ecs` →
  `vendor/bin/rector process src --dry-run`. **No phpunit in CI.**
- TWINT rejects a callback/redirect URL that is `localhost`, carries a
  non-standard `:port`, or is not `https` — so HTTPS + a real hostname is
  required, same constraint as woo.

## Decisions (locked)

| Topic | Decision |
|-------|----------|
| Old infra | Delete `demo65/66/67`, `local-67`, `ci/compose.yaml`. Fresh `infra/local` + `infra/ci`. |
| Run model | Pick-one via compose **profiles** (`sw65`/`sw66`/`sw67`); one version up at a time. |
| Data | **Per-version named volumes** — each version keeps its own Shopware files + DB. |
| HTTPS host | Single stable host **`https://shopware.twint.local`**, mkcert cert, Caddy TLS proxy. |
| Provisioning | Approach A — wrapper `bin/up.sh [65\|66\|67]` + `bin/provision.sh` via `exec`, stock dockware images. |
| Provision depth | Full TWINT-ready: install+activate plugin, build assets, CHF/CH, assign payment method, set SC domain. |
| CHF/CH wiring | Raw SQL against dockware `shopware` DB (root/root) — no clean stock console command. |
| local-CI scope | Mirror GitLab `test` job only (ecs + rector, PHP 8.1–8.5). No phpunit, no release. |

## Target layout

```
infra/
  local/
    compose.yaml
    .env.example
    .env                  # gitignored
    proxy/Caddyfile
    certs/                # gitignored: mkcert output; .gitkeep + .gitignore
    bin/
      up.sh               # up.sh [65|66|67] -> start version+proxy, wait healthy, provision
      down.sh             # down.sh [--volumes]
      provision.sh        # runs INSIDE the active container, idempotent
      shell.sh            # shell into active container
      logs.sh             # follow active container logs
    README.md
  ci/
    local-ci.sh
    README.md
    logs/                 # gitignored
  .gitignore
```

## Component design

### compose.yaml

- Services `sw65`, `sw66`, `sw67`:
  - images `dockware/dev:6.5.8.0`, `dockware/dev:6.6.7.0`, `dockware/dev:6.7.2.2`.
  - `profiles: ["sw65"]` / `["sw66"]` / `["sw67"]` — only the selected one starts.
  - network `web`, each with **alias `shop`** so Caddy always proxies stable
    `shop:80` regardless of which version is up.
  - **Per-version named volumes**: `swXX_html:/var/www/html` and
    `swXX_db:/var/lib/mysql` (isolated data per version).
  - Plugin live-mount: `../../:/var/www/html/custom/plugins/TwintPayment:rw`
    (`../..` from `infra/local/` = plugin root), plus **anonymous volumes**
    shadowing `.../TwintPayment/infra` and `.../TwintPayment/vendor` to prevent
    recursive bind (same trick as current `local-67`).
  - env: `PHP_VERSION` per version (6.5→`8.2`, 6.6→`8.2`, 6.7→`8.3`),
    `XDEBUG_ENABLED=0`, `APP_URL=https://shopware.twint.local`, trusted-proxy set
    so Shopware honours `X-Forwarded-Proto`.
  - published ports (dockware): 80, 8888 (watch admin), 9998/9999 (watch
    storefront), 3306 (mysql), 8025 (mailpit). No conflicts — one up at a time.
- Service `proxy` (`caddy:2-alpine`): `profiles: ["sw65","sw66","sw67"]` so it
  starts with whichever version; publishes `443`; mounts `proxy/Caddyfile` (ro)
  and `certs/` (ro). Caddy retries the `shop` upstream until the container is up.

### proxy/Caddyfile

```
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

### TLS + hostname (one-time host setup, documented in README)

```bash
echo "127.0.0.1 shopware.twint.local" | sudo tee -a /etc/hosts
brew install mkcert && mkcert -install          # once
cd infra/local/certs && mkcert shopware.twint.local && cd -
```

`certs/` is git-ignored. Result: `https://shopware.twint.local` — https, no port,
browser-trusted, TWINT-acceptable, constant across version switches.

### bin/up.sh [65|66|67]

1. Resolve profile `swXX`; default to `67` if omitted.
2. `docker compose --profile swXX up -d` (starts version + proxy).
3. Wait for container health (dockware healthy / Shopware reachable).
4. `docker compose exec swXX bash /var/www/html/custom/plugins/TwintPayment/infra/local/bin/provision.sh`
   — or exec the script by its in-container path.
5. Print the ready URL + admin creds.

### bin/provision.sh (inside container, idempotent, marker-guarded)

Run as the dockware web user, `cd /var/www/html`:

1. `composer install` in `custom/plugins/TwintPayment` (pulls public
   `twint-ag/sdk`) — skip if `vendor/` present and lock unchanged.
2. `bin/console plugin:refresh`
3. `bin/console plugin:install --activate --clearCache TwintPayment`
4. Build assets: `bin/build-administration.sh` + `bin/build-storefront.sh`
   (dockware-provided).
5. Shop config via SQL (dockware `shopware` DB, root/root):
   - set **default currency CHF** (create if missing, point defaults at it),
   - mark **country CH active**,
   - assign **TwintPayment** payment method to the default sales channel,
   - set the sales-channel **domain** to `https://shopware.twint.local`.
6. `bin/console cache:clear`.

Idempotency: guard each stage (plugin already active → skip install; marker file
for one-time SQL) so re-running `up.sh` is safe.

### bin/down.sh / shell.sh / logs.sh

- `down.sh` → `docker compose --profile sw65 --profile sw66 --profile sw67 down`;
  `--volumes` also drops named volumes (full reset).
- `shell.sh [65|66|67]` → `docker compose exec swXX bash`.
- `logs.sh [65|66|67]` → `docker compose logs -f swXX`.

### infra/ci/local-ci.sh

Mirror the GitLab `test` job, in Docker, same image `shivammathur/node:jammy`,
matrix PHP **8.1–8.5** in parallel. Per version:

1. Copy repo into a container-local `/work` (exclude `.git/vendor/node_modules/
   dist/build`) so parallel jobs never clobber each other and the working tree is
   untouched (woo pattern).
2. `spc -U`
3. `spc --php-version "$V" --extensions "mbstring, curl, dom, fileinfo, gd, iconv,
   intl, json, xml, pdo, phar, zip, sodium, pdo_mysql, bcmath, soap"`
4. optional: `composer config --global gitlab-token.git.nfq.asia "$GITLAB_TOKEN"`
   only if `GITLAB_TOKEN` set (read from `infra/local/.env`; repo currently has no
   private repo, so normally a no-op).
5. `rm -f composer.lock && composer install --no-progress --optimize-autoloader`
6. `vendor/bin/ecs`
7. `vendor/bin/rector process src --dry-run`

Prints `PASS`/`FAIL` per version; per-version logs in `infra/ci/logs/`. Usage:

```bash
infra/ci/local-ci.sh test            # all PHP 8.1–8.5 in parallel
infra/ci/local-ci.sh test 8.3        # one/few versions
```

### infra/.gitignore

Block `local/.env`, `local/certs/*` (keep `.gitkeep`), `ci/logs/`. Ensure Shopware
core / dockware data never re-enter the repo.

## Testing / validation

- `bin/up.sh 67` → `https://shopware.twint.local` storefront + admin load,
  TwintPayment **active**, default currency **CHF**, browser-trusted cert.
- `bin/down.sh && bin/up.sh 65` → boots 6.5 on its **own** volume/data,
  independent of 6.7.
- Re-run `bin/up.sh 67` → provision is a no-op (idempotent), site still healthy.
- `infra/ci/local-ci.sh test 8.3` → `ecs` + `rector --dry-run` **PASS**;
  a deliberate style break → **FAIL** with a pointer to the log.

## Out of scope

- phpunit / functional test execution (CI doesn't run it).
- Release/`bin/sync.sh` tag pipeline reproduction.
- Running 2+ versions simultaneously (single-host, pick-one by design; per-version
  hostnames deferred).
- Anything under `devbox/` (separate deploy system, untouched).

## References

- `twint-woocommerce-extension/infra/local/` and `.../ci/local-ci.sh` — the
  pattern this mirrors.
- `.gitlab-ci.yml` — source of truth for the `test` job local-ci reproduces.
