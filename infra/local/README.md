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
