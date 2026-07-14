# DNS & TLS

## Now — local `/etc/hosts` (HTTP only)
The stack is HTTP-only today; there is no TLS in `compose.yaml`. With
`DOMAIN_BASE=twint-dev`, hostnames are hyphen-joined (`sw65-twint-dev`,
`sw66-twint-dev`, `sw67-twint-dev`). On each developer's laptop, add to
`/etc/hosts`:
```
<ec2-ip> sw65-twint-dev sw66-twint-dev sw67-twint-dev
```
Access over `http://`; you'll also be prompted for the Traefik basic-auth
credentials from `.env` (see [architecture.md](architecture.md)).

## Later — a real domain
1. **DNS:** create a wildcard record for the parent of the instance label →
   EC2 IP (Route53 or any DNS provider). With
   `DOMAIN_BASE=twint.dev.nfq-asia.com` the hosts are
   `sw65-twint.dev.nfq-asia.com` etc., so the wildcard is
   `*.dev.nfq-asia.com`.
2. **Config:** set `DOMAIN_BASE=<domain>` in `.env`; re-run `bin/up.sh` (new
   `APP_URL`/Host rules) and `bin/provision.sh all` so sales-channel domains
   follow.
3. **TLS** — none of these require changes to the shop services, volumes, or
   `bin/` scripts; only the `proxy` service in `compose.yaml` changes. Pick
   one:

   - **Let's Encrypt, DNS-01 (Route53)** — works even without exposing `:80`/
     `:443` for the challenge, and supports wildcard certs. Needs Traefik
     given AWS credentials with Route53 permissions:
     ```yaml
     environment:
       - AWS_ACCESS_KEY_ID=...
       - AWS_SECRET_ACCESS_KEY=...
       - AWS_HOSTED_ZONE_ID=...
     command:
       - "--entrypoints.websecure.address=:443"
       - "--certificatesresolvers.le.acme.dnschallenge=true"
       - "--certificatesresolvers.le.acme.dnschallenge.provider=route53"
       - "--certificatesresolvers.le.acme.email=ops@<domain>"
       - "--certificatesresolvers.le.acme.storage=/letsencrypt/acme.json"
     ```
   - **Let's Encrypt, public HTTP-01 or TLS-ALPN-01** — simplest if the box is
     reachable on `:80`/`:443` from the internet (no DNS API needed):
     ```yaml
     command:
       - "--entrypoints.websecure.address=:443"
       - "--certificatesresolvers.le.acme.httpchallenge=true"           # or acme.tlschallenge=true
       - "--certificatesresolvers.le.acme.httpchallenge.entrypoint=web"
       - "--certificatesresolvers.le.acme.email=ops@<domain>"
       - "--certificatesresolvers.le.acme.storage=/letsencrypt/acme.json"
     ```
     Publish `443:443` and persist `/letsencrypt` with a named volume.
   - **Self-signed** — fastest for a purely internal box with no public DNS;
     mount a cert/key pair and point Traefik at them with a `tls` file
     provider, accepting the browser trust warning (or installing the CA on
     dev laptops).

   Whichever resolver, add per instance:
   ```yaml
   - "traefik.http.routers.swXX.entrypoints=websecure"
   - "traefik.http.routers.swXX.tls.certresolver=le"
   ```
