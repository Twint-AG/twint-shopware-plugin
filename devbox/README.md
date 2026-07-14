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
