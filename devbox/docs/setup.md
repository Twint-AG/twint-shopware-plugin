# Setup (fresh EC2, Ubuntu)

## 0. Get the repo onto the box
A fresh Ubuntu image may lack `git`. Either `sudo apt-get install -y git` first,
or run `bootstrap.sh` (it installs git) after copying it over. Then clone this
repo and `cd` into `devbox/`.

## 1. Bootstrap the host
```bash
bin/bootstrap.sh
```
Installs Docker Engine + the Compose plugin + git from Docker's official apt
repo and adds you to the `docker` group. Works on current Ubuntu releases
(including 26.04) with Docker Engine 29. **Log out and back in** (or `newgrp
docker`) so you can run `docker` without sudo.

## 2. Configure
```bash
cp .env.example .env
```
Set:
- `DOMAIN_BASE` — keep `twint-dev` for local `/etc/hosts` testing, or a real
  domain (see [dns-tls.md](dns-tls.md)). Instances are served at
  `swXX-$DOMAIN_BASE` (hyphen-joined).
- `GIT_REMOTE` — the plugin repo, SSH (`git@host:group/repo.git`) or
  host+path (`host/group/repo.git`); `deploy.sh` normalizes either form.
- `SDK_REMOTE` — same forms, for the plugin's private `twint-ag/sdk` Composer
  dependency. Required — deploy fails fast without it.
- `GITLAB_USERNAME` + `GITLAB_TOKEN` — a GitLab user and a token (deploy token
  or PAT) with `read_repository` scope, with access to **both** the plugin
  repo and the SDK repo. `deploy.sh` uses these as Composer http-basic auth
  (username + token), not `gitlab-token`/API auth — no `read_api` scope
  needed.
- `BASIC_AUTH_USER` / `BASIC_AUTH_PASSWORD` — HTTP basic auth credentials
  Traefik enforces in front of all three instances (`/api` and `/store-api`
  are exempt — see [architecture.md](architecture.md)).
- `SW65_IMAGE` / `SW66_IMAGE` / `SW67_IMAGE` — pinned dockware tags (defaults
  provided).

## 3. Start + provision + deploy
```bash
bin/up.sh                # generates Traefik htpasswd, then starts proxy + sw65 + sw66 + sw67
bin/provision.sh all     # sales-channel domains -> swXX-$DOMAIN_BASE
bin/deploy.sh master     # checkout master on the host clone, then composer-install + build on all instances
```

## 4. Laptop DNS (local testing)
Add to your laptop's `/etc/hosts` (get `<ec2-ip>` from AWS):
```
<ec2-ip> sw65-twint-dev sw66-twint-dev sw67-twint-dev
```
Open `http://sw65-twint-dev` (you'll be prompted for the basic-auth
credentials from step 2). For the real-domain path see
[dns-tls.md](dns-tls.md).

## Next
- Day-to-day commands: [operations.md](operations.md)
- Deploying other branches: [deploy.md](deploy.md)
- Something not working: [troubleshooting.md](troubleshooting.md)
