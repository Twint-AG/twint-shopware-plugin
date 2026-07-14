# Architecture

```
                          ┌─ Host(sw65-$DOMAIN_BASE) → sw65 :80  (dockware 6.5.8.0)
Browser ─:80→ Traefik ────┼─ Host(sw66-$DOMAIN_BASE) → sw66 :80  (dockware 6.6.7.0)
                          └─ Host(sw67-$DOMAIN_BASE) → sw67 :80  (dockware 6.7.2.2)
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
