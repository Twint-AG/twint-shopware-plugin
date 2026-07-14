# devbox — multi-version Shopware dev box

Runs Shopware **6.5, 6.6, and 6.7 side by side** on the `twint-dev` EC2 box,
one Docker Compose project: a Traefik reverse proxy in front of three
`dockware/dev` instances (`sw65`/`sw66`/`sw67`), so the TWINT plugin can be
validated against every supported version at once. `deploy.sh` installs the
plugin — via Composer, from a GitLab branch — onto all three instances at
once.

> This folder is internal tooling. It is excluded from the public GitHub mirror
> (`.gitattributes` + `bin/sync.sh`). Do not commit `.env`.

## Quickstart

```bash
# on twint-dev (fresh host), once:
bin/bootstrap.sh                 # install Docker + compose + git, then re-login
cp .env.example .env             # set DOMAIN_BASE, GIT_REMOTE, SDK_REMOTE,
                                  # GITLAB_USERNAME/GITLAB_TOKEN, BASIC_AUTH_USER/PASSWORD
bin/up.sh                        # generate basic-auth htpasswd, start traefik + all three instances
bin/provision.sh all             # set sales-channel domains
bin/deploy.sh master             # checkout + composer-install the plugin on all three

# on your laptop, once — /etc/hosts:
#   <ec2-ip> sw65-twint-dev sw66-twint-dev sw67-twint-dev

# iterate — deploy any branch to all instances:
bin/deploy.sh my-feature-branch
bin/deploy.sh                    # redeploy whatever branch the host clone is currently on
```

Then open `http://sw65-twint-dev`, `http://sw66-twint-dev`,
`http://sw67-twint-dev` (you'll be prompted for the `BASIC_AUTH_USER` /
`BASIC_AUTH_PASSWORD` credentials from `.env`; `/api` and `/store-api` skip
that prompt).

## Docs

| Doc | What's inside |
|-----|---------------|
| [docs/setup.md](docs/setup.md) | Fresh-host bootstrap and first run |
| [docs/deploy.md](docs/deploy.md) | `deploy.sh` in depth: branch → Composer install, what runs |
| [docs/operations.md](docs/operations.md) | Day-to-day up/down/logs/shell + data safety |
| [docs/dns-tls.md](docs/dns-tls.md) | `/etc/hosts` now → real domain + TLS later |
| [docs/troubleshooting.md](docs/troubleshooting.md) | Common failures and fixes |
| [docs/architecture.md](docs/architecture.md) | How it fits together and why |
