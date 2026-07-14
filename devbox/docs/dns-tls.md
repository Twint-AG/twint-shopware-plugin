# DNS & TLS

## Now — local /etc/hosts (HTTP only)
`DOMAIN_BASE=twint-dev`. Hostnames are hyphen-joined (`sw65-twint-dev`). On each
developer's laptop, add to `/etc/hosts`:
```
<ec2-ip> sw65-twint-dev sw66-twint-dev sw67-twint-dev
```
No TLS; access over `http://`.

## Later — Route53 + Let's Encrypt
1. **DNS:** create a wildcard record for the parent of the instance label →
   EC2 IP in Route53. With `DOMAIN_BASE=twint.dev.nfq-asia.com` the hosts are
   `sw65-twint.dev.nfq-asia.com`, so the wildcard is `*.dev.nfq-asia.com`.
2. **Config:** set `DOMAIN_BASE=<domain>` in `.env`; re-run
   `bin/provision.sh all` so sales-channel domains follow.
3. **TLS in `compose.yaml`** (proxy service): add a `websecure` entrypoint on
   `:443`, publish `443:443`, add a Let's Encrypt resolver, e.g.:
   ```yaml
   command:
     - "--entrypoints.websecure.address=:443"
     - "--certificatesresolvers.le.acme.tlschallenge=true"
     - "--certificatesresolvers.le.acme.email=ops@<domain>"
     - "--certificatesresolvers.le.acme.storage=/letsencrypt/acme.json"
   ```
   and per instance:
   ```yaml
   - "traefik.http.routers.swXX.entrypoints=websecure"
   - "traefik.http.routers.swXX.tls.certresolver=le"
   ```
   Persist `/letsencrypt` on the proxy with a named volume.

No change to shop services, volumes, or the bin/ scripts is required.
