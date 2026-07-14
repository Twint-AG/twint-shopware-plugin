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
