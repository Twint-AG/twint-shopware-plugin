# Mailpit for the devbox — capture sent email from all instances

**Date:** 2026-07-15
**Status:** Approved (design)
**Repo:** twint-shopware-plugin (`devbox/`)

## Problem

Developers need to inspect email sent by the demo instances (order
confirmations, password resets, etc.) without those mails leaving the box. A
Mailpit already captures **Magento** mail, but Shopware and WooCommerce send
nowhere shared.

## Current topology (discovered)

Two EC2 boxes, three independent Docker Compose projects:

| Box (IP) | Instances | Compose project (repo) | Docker network | Mailpit today |
|---|---|---|---|---|
| twint-dev (54.251.58.92) | sw65/66/67 | `devbox` (**this repo**) | `devbox_web` | none |
| twint-dev (54.251.58.92) | wc1/2/3 | woo devbox (`twint-woocommerce-extension`) | `woo-internal` | none |
| twint-dev-2 (56.11.0.82) | m246–249, h247 | `magento-devbox` (`twint-magento-extension`) | `magento-devbox_web` | `devbox_mailpit` → `https://mail-twint.dev.nfq-asia.com` |

Relevant facts:
- **Shopware** (dockware) uses Symfony `MAILER_DSN`, currently commented out in
  the baked `/var/www/html/.env`. Symfony reads `MAILER_DSN` from the real OS
  env, which overrides the `.env` value.
- **WooCommerce** (WordPress) sends via PHP `mail()`/sendmail with no SMTP
  plugin; it will not speak SMTP without a `phpmailer_init` hook. Its
  `wp-content` appears baked into the `woo-devbox/wcN` images.
- `devbox_web` and `woo-internal` are both non-internal bridge networks, so a
  single container can attach to both and be reached as `mailpit:1025` from
  either project.
- **DNS:** working hosts (`sw65-`, `home-`, `m247-`) each have an *explicit* A
  record to their box. The wildcard `*-twint.dev.nfq-asia.com` resolves to
  three unrelated IPs, so any new hostname needs its own explicit A record.

## Goal

One Mailpit on **twint-dev** that captures **Shopware + WooCommerce** mail,
exposed at a public URL behind the existing basic-auth. The Magento Mailpit on
twint-dev-2 stays as-is. Two inboxes total (by box); no cross-box networking.

## Scope

### Phase 1 — this repo, ships now
1. **Mailpit service** in `devbox/compose.yaml`.
2. **Shopware wiring** (sw65/66/67 → Mailpit) in the same file.
3. **Launcher** cards for both inboxes.
4. **Docs** note in `devbox/docs/`.

### Phase 2 — follow-up, separate repo
WooCommerce → Mailpit, in `twint-woocommerce-extension` (needs image rebuild).
Out of scope for this spec beyond documenting the intended approach.

## Design — Phase 1

### 1. Mailpit service (`devbox/compose.yaml`)

New service mirroring the Magento one:

```yaml
mailpit:
  image: axllent/mailpit:latest
  container_name: devbox_mailpit
  restart: unless-stopped
  environment:
    - MP_DATABASE=/data/mailpit.db      # persist caught mail across restarts
    - MP_MAX_MESSAGES=5000
  volumes:
    - "mailpit_data:/data"
  labels:
    - "traefik.enable=true"
    - "traefik.http.routers.mail.rule=Host(`mail2-${DOMAIN_BASE}`)"
    - "traefik.http.routers.mail.entrypoints=websecure"
    - "traefik.http.routers.mail.tls.certresolver=le"
    - "traefik.http.middlewares.devbox-auth.basicauth.usersfile=/etc/traefik/auth/htpasswd"
    - "traefik.http.routers.mail.middlewares=devbox-auth"
    - "traefik.http.services.mail.loadbalancer.server.port=8025"
  networks:
    web:
      aliases: [mailpit]
    woo-internal:
      aliases: [mailpit]
```

- SMTP `:1025` stays internal (not host-published). UI `:8025` reachable only
  through Traefik.
- `woo-internal` declared as an **external** network (owned by the woo compose):

```yaml
networks:
  web:
    external: false
  woo-internal:
    external: true
```

- New named volume `mailpit_data`.
- Attaching to `woo-internal` now (Phase 1) is harmless — Woo containers can't
  reach it until Phase 2 wires their mailer, but the network membership is
  ready and does not depend on Woo being up (external network already exists).

### 2. Shopware wiring (`devbox/compose.yaml`)

Add to `environment` of sw65, sw66, sw67:

```yaml
  - MAILER_DSN=smtp://mailpit:1025
```

No `.env` edits (real env overrides the baked file). Applied by recreating the
containers via `bin/up.sh`.

### 3. Launcher (`devbox/dashboard/index.html`)

- Add a `PLATFORMS` entry: `{ id: "tools", label: "Tools", css: "--accent" }`.
- Add two `ENVIRONMENTS` entries with `platform: "tools"` (no admin link):
  - `mail-sw` → `https://mail2-twint.dev.nfq-asia.com` — "Shopware + Woo inbox".
  - `mail-magento` → `https://mail-twint.dev.nfq-asia.com` — "Magento inbox".
- These are tools, not shops; the auto-derived shop count should keep counting
  only real shop platforms (verify the count logic still reads sensibly, adjust
  the header copy if needed).

### 4. Docs

Short note in `devbox/docs/operations.md` (or a new `mail.md`): what Mailpit is,
the two URLs, that Shopware routes via `MAILER_DSN`, and the Phase-2 Woo plan.

## External prerequisite — DNS (cannot be done from the repo)

Add an explicit A record:

```
mail2-twint.dev.nfq-asia.com  A  54.251.58.92
```

Without it the wildcard misroutes the host and Let's Encrypt (TLS-ALPN-01)
cannot issue a cert. This must be added by whoever manages `nfq-asia.com` DNS
(Route53). Flag it to the user as a blocking prerequisite for the public URL;
the service and Shopware wiring still function internally without it.

## Verification (Phase 1)

1. `docker compose up -d` (via `bin/up.sh`); confirm `devbox_mailpit` is running
   and attached to both `devbox_web` and `woo-internal`.
2. From an sw container: `php bin/console mailer:test someone@example.com` (or
   trigger a real store mail) and confirm it appears in the Mailpit UI.
3. Once DNS resolves: open `https://mail2-twint.dev.nfq-asia.com`, pass
   basic-auth, see the message. Confirm TLS cert issued.
4. Confirm the launcher shows both mail cards under "Tools".

## Design — Phase 2 (documented, not built here)

In `twint-woocommerce-extension`: add a mu-plugin (e.g.
`wp-content/mu-plugins/00-mailpit.php`) hooking `phpmailer_init` to
`isSMTP()` with host `mailpit`, port `1025`, no auth; rebuild the `woo-devbox/wcN`
images and redeploy. The Phase-1 Mailpit is already on `woo-internal`, so no
compose/network change is needed there.

## Out of scope

- Changing or migrating the Magento Mailpit.
- A single unified cross-box inbox (rejected: needs exposing SMTP across boxes
  + security-group changes).
- Outbound/real email delivery — Mailpit is a catcher only.
