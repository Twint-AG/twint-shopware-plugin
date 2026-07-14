# Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Browser prompts for a username/password on the storefront or `/admin` | Expected — Traefik basic auth protects every instance | enter `BASIC_AUTH_USER` / `BASIC_AUTH_PASSWORD` from `.env`. `/api` and `/store-api` don't prompt — see [architecture.md](architecture.md) |
| `401` calling `/api` or `/store-api` | that's a Shopware auth failure (bad JWT / sw-access-key), not Traefik basic auth — those paths bypass it | check the request's own credentials, not `.env` basic auth |
| All instances 404, dashboard shows no routers for `sw65`/`sw66`/`sw67` | Traefik older than v3.7 running against Docker Engine 29: Docker rejects the old API version (1.24) Traefik ≤3.5 negotiates, so the Docker provider can't read labels | confirm `image: traefik:v3.7` (or newer) in `compose.yaml`, `docker compose pull proxy && bin/up.sh` |
| Storefront links/redirects point to `localhost` or wrong host | sales-channel domain not set | `bin/provision.sh <instance>` |
| Traefik 404 for `swXX-$DOMAIN_BASE` | hostname doesn't resolve, or label/`DOMAIN_BASE` mismatch | check laptop `/etc/hosts`; `docker compose config \| grep Host`; dashboard at `:8080` (see [operations.md](operations.md)) |
| Traefik 502 | instance still booting or Apache down | `bin/logs.sh <instance>`; wait for dockware to finish init |
| `bind: address already in use` on `:80` | something else owns port 80 on the host | stop it, or change the proxy's published port |
| Storefront redirect loop on the 6.5 instance | known bug in dockware/Shopware `6.5.8.0` (fixed in `6.5.8.17`) | confirm `SW65_IMAGE=dockware/dev:6.5.8.17` in `.env`; if it's already pinned to `6.5.8.17` and the instance still loops, it was likely seeded from an older image (see the volume-seed row below) |
| `composer require`/`composer update` fails auth (403/404) for the plugin or for `twint-ag/sdk` | bad/expired `GITLAB_TOKEN`, wrong `GIT_REMOTE`/`SDK_REMOTE`, or the token lacks access to **one** of the two repos | fix `.env`; `deploy.sh` sets http-basic auth (`GITLAB_USERNAME` + `GITLAB_TOKEN`) for both the plugin's host and `SDK_REMOTE`'s host — the token needs `read_repository` on **both** repos, not just the plugin |
| plugin not visible after deploy | `plugin:refresh` didn't detect the composer-managed plugin, or the build step failed | `bin/shell.sh <inst>` then `php bin/console plugin:list`; check `devbox/logs/deploy-*.log` for the failing step; re-run `bin/deploy.sh` |
| Bumped `SWXX_IMAGE` but Shopware version unchanged | `swXX_html`/`swXX_db` are named volumes — they seed from the image only on first creation, then persist regardless of image changes | remove that instance's volumes and re-seed: `bin/down.sh swXX`, `docker volume rm devbox_swXX_html devbox_swXX_db`, `bin/up.sh swXX && bin/provision.sh swXX && bin/deploy.sh` (see [operations.md](operations.md) → Upgrading) |
| Client IP shows as the proxy's container IP | no `TRUSTED_PROXIES` set for Shopware behind Traefik | set trusted proxies if IP-based logic matters (not needed for basic HTTP use) |
| DB/media empty after recreate | volume was removed (`docker volume rm` / `down -v`) — note `down.sh` never passes `-v` | expected; re-provision + re-deploy |
| `deploy.sh` errors "clone is in detached HEAD" | you passed a tag or commit hash, or the host clone was left detached | `deploy.sh` only deploys branches; push the commit to a branch and pass the branch name |
