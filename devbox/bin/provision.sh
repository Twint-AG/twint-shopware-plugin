#!/usr/bin/env bash
# One-time-per-instance provisioning for HTTPS behind Traefik:
#   - trust the proxy so Shopware honours X-Forwarded-Proto (https via Traefik TLS)
#   - point the sales-channel domain at https://swXX-$DOMAIN_BASE
# Idempotent. Usage: provision.sh [all|sw65|sw66|sw67]
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

resolve_targets "${1:-all}"
for inst in "${RESOLVED_TARGETS[@]}"; do
  host="${inst}-${DOMAIN_BASE}"

  echo "==> [$inst] trusting proxy forwarded headers (for https via Traefik)"
  # Traefik terminates TLS and forwards http + X-Forwarded-Proto=https. Shopware
  # must trust the proxy or it sees http, mismatches the https sales-channel
  # domain, and redirect-loops. Trust the private (docker) ranges.
  dc exec -T "$inst" bash -lc 'cat > /var/www/html/config/packages/z-devbox-proxy.yaml <<YAML
framework:
    trusted_proxies: "172.16.0.0/12,10.0.0.0/8,192.168.0.0/16"
    trusted_headers: ["x-forwarded-for", "x-forwarded-host", "x-forwarded-proto", "x-forwarded-port", "x-forwarded-prefix"]
YAML'

  echo "==> [$inst] set sales-channel domain -> https://$host"
  dc exec -T "$inst" php bin/console sales-channel:update:domain "$host"
  # update:domain keeps the existing scheme; force https for the storefront domain.
  dc exec -T "$inst" bash -lc "mysql -h127.0.0.1 -uroot -proot shopware -e \"UPDATE sales_channel_domain SET url=CONCAT('https://',SUBSTRING_INDEX(url,'://',-1)) WHERE url LIKE 'http://%';\""

  # Force dev mail routing to the Mailpit catcher. A non-empty
  # core.mailerSettings.emailAgent ('local'/'smtp') makes Shopware IGNORE
  # MAILER_DSN and send via sendmail/an external SMTP instead — which happens
  # when an instance's DB is imported from a prod/staging dump (carrying the
  # real SMTP creds). Clearing it makes Shopware fall back to MAILER_DSN
  # (=smtp://mailpit:1025, set in compose.yaml), so all mail is captured.
  echo "==> [$inst] force mailer -> Mailpit (clear core.mailerSettings.emailAgent)"
  dc exec -T "$inst" php bin/console system:config:set core.mailerSettings.emailAgent ""

  echo "==> [$inst] cache:clear"
  dc exec -T "$inst" php bin/console cache:clear -q
done
