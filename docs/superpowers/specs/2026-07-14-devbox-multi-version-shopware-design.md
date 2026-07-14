# Devbox: Multi-version Shopware dev box — Design

**Date:** 2026-07-14
**Status:** Approved — proceeding to implementation plan
**Topic:** Run multiple Shopware versions (6.5 / 6.6 / 6.7) concurrently on the EC2 dev box (`ssh twint-dev`) via Docker Compose, with persistent data + media and fast plugin deploy scripts.

## Problem

The `twint-shopware-plugin` must be validated against Shopware 6.5, 6.6, and 6.7. The existing `infra/` setup (`demo65`, `demo66`, `demo67`, `local-67`) is legacy/local-only: each instance hard-codes host ports `80/443/3306`, so **only one version can run at a time**. We want all three running simultaneously on a shared EC2 dev box, reachable in a browser, with data + uploads that survive container recreates, and a one-command plugin deploy.

## Decisions (locked)

| Topic | Decision |
|-------|----------|
| Access/routing | Subdomains via a **Traefik** reverse proxy (`sw65.*`, `sw66.*`, `sw67.*`) sharing `:80` (`:443` later) |
| DNS | `/etc/hosts` → EC2 IP **now**; switch to AWS Route53 wildcard + Let's Encrypt TLS **later** (config-only change) |
| Container base | `dockware/dev` all-in-one (Apache + MySQL + Shopware) per version |
| Plugin deploy | Pull a git ref (branch, default `master`, or commit hash) from GitLab into a per-instance checkout, bind-mounted into the container, then run Shopware scripts |
| Checkout model | Fixed per-instance dir (`devbox/src/swXX`), **fetch-in-place** (`git fetch` + `checkout`), no container recreate — each instance can sit on a different ref |
| GitLab auth | HTTPS + token (`GITLAB_TOKEN` in `.env`); remote `https://oauth2:$GITLAB_TOKEN@<gitlab-host>/twint-ag/twint-shopware-plugin.git` |
| Versions | 6.5 (`dockware/dev:6.5.8.0`), 6.6 (`dockware/dev:6.6.x`), 6.7 (`dockware/dev:6.7.2.2`) |
| Persistence | Per-instance named volumes for **DB** and **media/uploads** (not full filesystem) |
| Location | New top-level `devbox/` folder; legacy `infra/` untouched |

## Architecture

One Docker Compose project brings up **one Traefik proxy + three dockware instances** on a shared network. Traefik listens on `:80` (later `:443`) and routes by `Host` header to each instance's internal port 80:

```
                          ┌─ Host(sw65.twint-dev) → sw65 :80  (dockware 6.5.8.0)
Browser ─:80→ Traefik ────┼─ Host(sw66.twint-dev) → sw66 :80  (dockware 6.6.x)
                          └─ Host(sw67.twint-dev) → sw67 :80  (dockware 6.7.2.2)
```

- **Only Traefik publishes host ports** (`80`, later `443`, plus `8080` dashboard bound to localhost). The shop containers publish **no** host ports — they are reached only through Traefik over the shared Docker network. This is what removes the port collision that limited the legacy setup to one instance.
- Hostnames are parameterized by `DOMAIN_BASE` in `.env`. Today `DOMAIN_BASE=twint-dev` and each `swXX.twint-dev` resolves via the developer's `/etc/hosts` → EC2 IP. Later, `DOMAIN_BASE` becomes the real domain, a `*.` record lands in Route53, and Traefik's Let's Encrypt resolver is enabled — no change to service structure.

## Host bootstrap (fresh EC2, Ubuntu)

The `twint-dev` box starts empty, so `bin/bootstrap.sh` provisions it (idempotent, safe to re-run):

1. `apt-get update` and install prerequisites (`ca-certificates`, `curl`, `git`).
2. Install **Docker Engine + Compose v2 plugin** from Docker's official apt repository (not the older `docker.io` package), so `docker compose` (v2 subcommand) is available.
3. `systemctl enable --now docker`.
4. Add the login user to the `docker` group (`usermod -aG docker $USER`) so `docker` runs without sudo — the script notes that a re-login (or `newgrp docker`) is needed for the group to take effect.
5. Verify: print `docker --version` and `docker compose version`.

Assumptions: sudo access on the box; outbound internet for apt + pulling images. The script targets current Ubuntu LTS and fails fast with a clear message if run on a non-apt distro. Everything else (`up.sh`, `deploy.sh`, …) assumes bootstrap has run.

## Directory layout

```
devbox/
  compose.yaml          # traefik + sw65 + sw66 + sw67 on a shared network
  .env                  # DOMAIN_BASE, GIT_REMOTE, GITLAB_TOKEN, image tags (gitignored, host-specific)
  .env.example          # committed template
  .gitignore            # ignores src/ and .env
  src/                  # deploy-managed per-instance git checkouts (gitignored)
    sw65/  sw66/  sw67/ #   each = a clone of the plugin repo, checked out at its ref
  bin/
    bootstrap.sh        # one-time host setup: install Docker Engine + compose plugin + git (Ubuntu)
    up.sh               # docker compose up -d  [whole stack | one instance]
    down.sh             # docker compose down    [whole stack | one instance]
    deploy.sh           # pull git ref -> checkout -> composer install -> Shopware scripts
    provision.sh        # one-time per instance: set sales-channel domain to the subdomain
    logs.sh             # tail logs for an instance
    shell.sh            # exec a shell into an instance
    _lib.sh             # shared helpers (instance→container map, git remote, guards)
  README.md             # entry point: what devbox is, quickstart, links to docs/
  docs/
    setup.md            # fresh-host bootstrap + first-run walkthrough (Ubuntu)
    deploy.md           # deploy.sh usage: refs, per-instance, composer, what runs
    operations.md       # day-to-day: up/down/logs/shell, persistence & data safety
    dns-tls.md          # /etc/hosts now → Route53 + Let's Encrypt cutover steps
    troubleshooting.md  # common failures (APP_URL/domain, ports, token, volumes)
    architecture.md     # the diagram + why (proxy, dockware, volumes) for maintainers
```

The legacy `infra/` tree is not modified. The `devbox/src/` checkouts and `devbox/.env` are gitignored — they are host-specific and deploy-managed.

> Implementation gotcha: the `src/swXX` bind-mount source dirs must exist **before** `up.sh` runs, otherwise Docker auto-creates them **root-owned** and the later `git clone`/`composer install` (run as a non-root user) fail on permissions. `up.sh` (or `bootstrap.sh`) creates `src/sw65 src/sw66 src/sw67` up front with the invoking user's ownership.

`devbox/` is internal deployment tooling and is **excluded from the public GitHub mirror**: it is added to `.gitattributes` as `export-ignore` (so `release.sh`'s `git archive` zip omits it) and to the `EXCLUDE_PATHS` scrub list in `bin/sync.sh` (so the `git push` mirror omits it), alongside `infra/`.

## Per-instance service (dockware behind Traefik)

Each `swXX` service in `compose.yaml`:

- **Image**: pinned dockware tag per version, from `.env` (e.g. `SW65_IMAGE=dockware/dev:6.5.8.0`).
- **No published host ports.** Attached to the shared `web` network only.
- **Traefik labels**: router rule `Host(\`swXX.${DOMAIN_BASE}\`)`, service port `80`. `traefik.enable=true`.
- **Plugin bind-mount**: `./src/swXX:/var/www/html/custom/plugins/TwintPayment:rw` — the deploy-managed git checkout, not the developer's working tree. No `infra/`/`vendor/` anonymous-volume shields are needed: `src/swXX` is a clean checkout of the plugin repo, and its `vendor/` is populated in-container by `composer install` during deploy (so dependencies match the instance's PHP version).
- **Environment**: `APP_URL=http://swXX.${DOMAIN_BASE}`, `XDEBUG_ENABLED=0`, dockware's default DB (`root:root`, database `shopware`).

## Persistence

Per-instance named volumes:

- `swXX_db → /var/lib/mysql` — On first `up`, an empty named volume mounted over an image path that already contains data triggers Docker to **copy dockware's baked, pre-installed database into the volume**; it then persists across recreates, preserving install state (shop config, plugin install/activate state, test orders).
- `swXX_media → /var/www/html/public/media` (and thumbnails) — uploaded media survives recreates.

Shopware core code and compiled plugin assets are **not** persisted: they come from the image and are (re)generated by `deploy.sh`. This keeps container recreates cheap while DB + uploads are durable.

> Note: because DB is per-instance and lives in a named volume, `docker compose down` (without `-v`) preserves it. Data is only lost on an explicit `down -v` / volume prune — documented in the README.

## `deploy.sh <instance> [ref]` — git-pull plugin deploy

Signature: `deploy.sh <sw65|sw66|sw67|all> [<branch|commit-hash>]`. `ref` defaults to `master`.

Steps:

1. **Resolve** `<instance>` → checkout dir (`devbox/src/swXX`) and container name.
2. **Sync git** (fetch-in-place, on the host):
   - If `src/swXX` is empty, `git clone $GIT_REMOTE src/swXX` (remote built from `GIT_REMOTE`/`GITLAB_TOKEN` in `.env`).
   - `git -C src/swXX fetch --all --prune`
   - Checkout the ref: for a branch → `git checkout -B <branch> origin/<branch>` then `git reset --hard origin/<branch>`; for a commit hash → `git checkout --detach <hash>`. (The script detects branch vs. hash.)
3. **Install plugin deps** in the container (matches the instance PHP version), writing to the mounted checkout's `vendor/`:
   `docker compose exec swXX composer install -d /var/www/html/custom/plugins/TwintPayment --no-interaction`
   (absolute `-d` path — `exec`'s working directory is not guaranteed).
4. **Run Shopware scripts** in the container (mirroring the existing `infra/*/bin/` scripts):
   ```
   bin/console plugin:refresh
   bin/console plugin:install --activate TwintPayment   # plugin:update --clearCache if already installed
   bin/build-administration.sh && bin/build-storefront.sh
   bin/console cache:clear
   ```

Because `src/swXX` is bind-mounted, the checked-out code is immediately present in the container — no recreate. `deploy.sh all [ref]` applies the same ref to every instance; per-instance refs are set by separate calls (`deploy.sh sw66 feature-x`).

The exact `plugin:install` vs `plugin:update` branch is decided at implementation time by checking install state (idempotent re-deploys).

## `provision.sh <instance>` — one-time per instance

After an instance's first `up`, set its sales-channel domain to the subdomain so Shopware emits correct storefront/admin URLs behind Traefik:

```
bin/console sales-channel:update:domain  swXX.${DOMAIN_BASE}   # or equivalent DB update
```

Run once per instance; `deploy.sh` covers everything afterward. `provision.sh` is idempotent (re-running just re-asserts the domain).

## GitLab access

- `.env` holds `GIT_REMOTE` (host/path of the plugin repo) and `GITLAB_TOKEN` (a GitLab deploy/CI token or PAT with read access).
- The clone/fetch URL is assembled as `https://oauth2:${GITLAB_TOKEN}@${GIT_REMOTE}` so the token never lives in the checkout's git config on disk in plaintext beyond `.env` (which is gitignored). Implementation will use a per-command credential (`git -c` or `GIT_ASKPASS`/`url.insteadOf`) rather than baking the token into `src/swXX/.git/config`.
- `.env` is gitignored; `.env.example` documents both variables with placeholders.
- Compose auto-reads `devbox/.env` for `${...}` substitution in `compose.yaml`. The bash scripts additionally source `devbox/.env` (via `_lib.sh`) so `GIT_REMOTE`/`GITLAB_TOKEN` are available to `git` outside compose.

## Usage flow

```
# on the EC2 box, one time (fresh host):
# git may be absent on a fresh image; either `sudo apt-get install -y git` first,
# or curl bootstrap.sh down standalone (it installs git itself), then clone.
git clone <this-repo> && cd <repo>/devbox
bin/bootstrap.sh                      # install Docker + compose + git, then re-login
cp devbox/.env.example devbox/.env    # set DOMAIN_BASE, GIT_REMOTE, GITLAB_TOKEN, confirm image tags
devbox/bin/up.sh                      # start traefik + all three instances
devbox/bin/provision.sh all           # set sales-channel domains
devbox/bin/deploy.sh all master       # clone master, composer install, install+activate+build on all

# on the laptop, one time:
# add to /etc/hosts:  <ec2-ip> sw65.twint-dev sw66.twint-dev sw67.twint-dev

# iterate — deploy any branch/commit to any instance:
devbox/bin/deploy.sh sw66 feature/express-checkout   # 6.6 gets a feature branch
devbox/bin/deploy.sh sw67 e8737e40                    # 6.7 pinned to a commit
devbox/bin/deploy.sh sw65                             # 6.5 back to master (default ref)
```

## Later: Route53 + TLS (out of scope for first implementation, but designed for)

1. Point `*.<domain>` A/AAAA record at the EC2 IP in Route53.
2. Set `DOMAIN_BASE=<domain>` in `.env`.
3. Enable Traefik's Let's Encrypt (TLS-ALPN or DNS-01) resolver and add `:443` + `websecure` entrypoint + `certresolver` label.

No change to shop services, volumes, or scripts.

## Documentation (deliverable, in Markdown)

Everything is documented as `.md` files committed alongside the code, so the setup is maintainable without tribal knowledge. Docs are written as part of implementation (each plan step that adds a script also updates the relevant doc — docs and code land together, never after).

| File | Audience / purpose |
|------|--------------------|
| `devbox/README.md` | First thing a maintainer reads: one-paragraph what/why, quickstart commands, and a table linking to each `docs/*.md`. |
| `devbox/docs/setup.md` | Fresh-host path: `bootstrap.sh`, `.env` fields (`DOMAIN_BASE`, `GIT_REMOTE`, `GITLAB_TOKEN`, image tags), first `up`/`provision`/`deploy`, laptop `/etc/hosts`. |
| `devbox/docs/deploy.md` | `deploy.sh <instance> [ref]` in depth: branch vs commit, per-instance refs, the composer + Shopware steps, idempotent re-deploy, examples. |
| `devbox/docs/operations.md` | Day-to-day: `up/down/logs/shell`, running one instance vs all, **data-safety** (`down` keeps volumes, `down -v` destroys them), backups/reset of a DB or media volume. |
| `devbox/docs/dns-tls.md` | The `/etc/hosts` → Route53 wildcard + Let's Encrypt cutover, with the exact `.env`/Traefik changes. |
| `devbox/docs/troubleshooting.md` | Symptom → cause → fix for the known friction points (wrong `APP_URL`/sales-channel domain, proxy 404/502, `:80` already in use, GitLab token/permission errors, empty/stale volumes). |
| `devbox/docs/architecture.md` | The routing diagram + rationale (why Traefik, why dockware all-in-one, why named volumes, how first-run volume seeding works) for future maintainers. |

Doc conventions: relative links between docs, fenced command blocks that are copy-paste runnable, and no secrets in examples (tokens shown as `$GITLAB_TOKEN`). The design spec in `docs/superpowers/specs/` remains the historical decision record; the `devbox/docs/` files are the living operational docs.

## Non-goals / YAGNI

- No official split-services template (app/db/opensearch/mailer per version) — dockware all-in-one only.
- No zip-artifact packaging/install path in v1 (git checkout + `composer install` only).
- No live-mount of the developer's working tree — deploys always come from a pushed GitLab ref.
- No CI integration — legacy `infra/ci/` remains the CI story.
- No automatic TLS in v1 (HTTP only until Route53 cutover).
- No full-filesystem persistence — DB + media only.

## Success criteria

- `devbox/bin/up.sh` brings up Traefik + all three instances with no host-port conflicts.
- `http://sw65.twint-dev`, `http://sw66.twint-dev`, `http://sw67.twint-dev` each load their respective Shopware storefront + admin from one browser, concurrently.
- `deploy.sh sw66 <branch>` pulls that branch from GitLab into `src/sw66`, runs `composer install` + the Shopware scripts, and the TWINT plugin is installed, activated, and built on 6.6 — while sw65/sw67 remain on their own refs.
- `deploy.sh sw67 <commit-hash>` checks out and deploys a specific commit.
- Re-running `deploy.sh` on an already-deployed instance is idempotent (updates rather than errors).
- `down.sh` then `up.sh` preserves DB and uploaded media per instance.
- Switching `DOMAIN_BASE` + `/etc/hosts` entries changes the URLs with no other edits.
- A new maintainer can go from a fresh EC2 box to three running, plugin-deployed instances using only `devbox/README.md` + `devbox/docs/*.md`, without asking anyone.
