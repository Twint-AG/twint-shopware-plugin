#!/usr/bin/env bash
# Deploy a git ref of the TWINT plugin to ALL instances via Composer.
# Usage: deploy.sh [branch|commit]   (ref defaults to master)
set -euo pipefail
. "$(dirname "${BASH_SOURCE[0]}")/_lib.sh"
load_env

REF="${1:-master}"
PLUGIN="TwintPayment"
PLUGIN_PACKAGE="twint-ag/twint-shopware-plugin"
GITLAB_HOST="${GIT_REMOTE%%/*}"        # e.g. git.nfq.asia
VCS_URL="https://${GIT_REMOTE}"        # e.g. https://git.nfq.asia/twint-ag/twint-shopware-plugin.git

# Private VCS dependency of the plugin (twint-ag/sdk). Same GitLab access/token.
SDK_REMOTE="${SDK_REMOTE:?SDK_REMOTE not set in .env (host+path of the private twint-ag/sdk repo)}"
SDK_HOST="${SDK_REMOTE%%/*}"           # e.g. git.nfq.asia
SDK_URL="https://${SDK_REMOTE}"        # e.g. https://git.nfq.asia/twint-ag/sdk.git

# Map a friendly ref to a Composer constraint:
#   7-40 hex chars -> treated as a commit: dev-master#<sha>
#   anything else  -> treated as a branch: dev-<branch>
composer_constraint() {
  local ref="$1"
  if printf '%s' "$ref" | grep -Eq '^[0-9a-f]{7,40}$'; then
    echo "dev-master#${ref}"
  else
    echo "dev-${ref}"
  fi
}

deploy_to() {
  local inst="$1" constraint="$2"

  echo "==> [$inst] configuring Composer GitLab repo + token"
  dc exec -T "$inst" composer config repositories.twint vcs "$VCS_URL"
  # --auth writes to the project auth.json (persisted in the html volume); the
  # token value is passed as an argument, not echoed by this script.
  dc exec -T "$inst" composer config --auth "gitlab-token.${GITLAB_HOST}" "$GITLAB_TOKEN"

  # Register the plugin's private VCS dependency so Composer can resolve it.
  dc exec -T "$inst" composer config repositories.sdk vcs "$SDK_URL"
  dc exec -T "$inst" composer config --auth "gitlab-token.${SDK_HOST}" "$GITLAB_TOKEN"

  echo "==> [$inst] composer require ${PLUGIN_PACKAGE}:${constraint}"
  dc exec -T "$inst" composer require "${PLUGIN_PACKAGE}:${constraint}" \
    --no-interaction --no-progress --with-all-dependencies

  echo "==> [$inst] plugin refresh"
  dc exec -T "$inst" php bin/console plugin:refresh

  echo "==> [$inst] install/activate (or update if already installed)"
  if ! dc exec -T "$inst" php bin/console plugin:install --activate "$PLUGIN"; then
    dc exec -T "$inst" php bin/console plugin:update "$PLUGIN"
  fi

  echo "==> [$inst] build administration + storefront"
  dc exec -T "$inst" bash -lc 'bin/build-administration.sh && bin/build-storefront.sh'

  echo "==> [$inst] cache:clear"
  dc exec -T "$inst" php bin/console cache:clear
}

CONSTRAINT="$(composer_constraint "$REF")"
echo "==> deploying ${PLUGIN_PACKAGE}:${CONSTRAINT} to all instances"
for inst in "${INSTANCES[@]}"; do
  deploy_to "$inst" "$CONSTRAINT"
  echo "==> [$inst] done"
done
echo "==> all instances on ${CONSTRAINT}"
