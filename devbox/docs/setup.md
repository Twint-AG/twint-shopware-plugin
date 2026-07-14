# Setup (fresh EC2, Ubuntu)

## 0. Get the repo onto the box
A fresh Ubuntu image may lack `git`. Either `sudo apt-get install -y git` first,
or run `bootstrap.sh` (it installs git) after copying it over. Then clone this
repo and `cd` into `devbox/`.

## 1. Bootstrap the host
```bash
bin/bootstrap.sh
```
Installs Docker Engine + Compose plugin + git from Docker's official apt repo and
adds you to the `docker` group. **Log out and back in** (or `newgrp docker`) so
you can run `docker` without sudo.

## 2. Configure
```bash
cp .env.example .env
```
Set:
- `DOMAIN_BASE` — keep `twint-dev` for local `/etc/hosts` testing.
- `GIT_REMOTE` — GitLab host+path of the plugin repo (no scheme).
- `SDK_REMOTE` — GitLab host+path of the private `twint-ag/sdk` dependency (no scheme). Required — deploy fails fast without it.
- `GITLAB_TOKEN` — a deploy token / PAT with `read_repository` + `read_api` (Composer needs `read_api` for GitLab VCS). Must have access to both the plugin repo and the SDK repo.
- `SW65_IMAGE` / `SW66_IMAGE` / `SW67_IMAGE` — pinned dockware tags (defaults provided).

## 3. Start + provision + deploy
```bash
bin/up.sh                # traefik + sw65 + sw66 + sw67
bin/provision.sh all     # sales-channel domains -> swXX-$DOMAIN_BASE
bin/deploy.sh master     # composer require + install/activate + build on all instances
```

## 4. Laptop DNS (local testing)
Add to your laptop's `/etc/hosts` (get `<ec2-ip>` from AWS):
```
<ec2-ip> sw65-twint-dev sw66-twint-dev sw67-twint-dev
```
Open `http://sw66-twint-dev`, etc. For the real-domain path see
[dns-tls.md](dns-tls.md).
