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
