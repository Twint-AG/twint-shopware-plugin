#!/usr/bin/env bash
# Deploy a git ref of the TWINT plugin to ALL instances via Composer.
# Usage: deploy.sh [branch|commit]   (ref defaults to master)
set -euo pipefail
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Self-update: pull the latest devbox tooling from git before deploying, so the
# host always runs the newest scripts. Runs once, then re-execs the fresh copy.
# Skips gracefully if not in a git repo; opt out with DEVBOX_NO_SELF_UPDATE=1.
if [ -z "${DEVBOX_SELF_UPDATED:-}" ] && [ -z "${DEVBOX_NO_SELF_UPDATE:-}" ] \
   && git -C "$SELF_DIR" rev-parse --git-dir >/dev/null 2>&1; then
  echo "==> updating devbox tooling (git pull --ff-only)"
  GIT_TERMINAL_PROMPT=0 git -C "$SELF_DIR" pull --ff-only \
    || echo "WARNING: git pull failed; continuing with current scripts" >&2
  exec env DEVBOX_SELF_UPDATED=1 "$0" "$@"
fi

. "$SELF_DIR/_lib.sh"
load_env

REF="${1:-master}"
PLUGIN="TwintPayment"
PLUGIN_PACKAGE="twint-ag/twint-shopware-plugin"
GITLAB_USERNAME="${GITLAB_USERNAME:?GITLAB_USERNAME not set in .env}"
SDK_REMOTE="${SDK_REMOTE:?SDK_REMOTE not set in .env (host+path of the private twint-ag/sdk repo)}"

# Normalize a remote to scheme-less host/path. Accepts any of:
#   git@host:group/repo.git (SSH)   host/group/repo.git   https://host/group/repo.git
norm_remote() {
  local r="$1"
  r="${r#https://}"; r="${r#http://}"; r="${r#git@}"; r="${r/://}"
  printf '%s' "$r"
}

GIT_NP="$(norm_remote "$GIT_REMOTE")"
GITLAB_HOST="${GIT_NP%%/*}"            # e.g. git.nfq.asia
VCS_URL="https://${GIT_NP}"            # e.g. https://git.nfq.asia/twint-ag/twint-shopware-plugin.git
SDK_NP="$(norm_remote "$SDK_REMOTE")"
SDK_HOST="${SDK_NP%%/*}"               # e.g. git.nfq.asia
SDK_URL="https://${SDK_NP}"            # e.g. https://git.nfq.asia/twint-ag/sdk.git

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

  echo "==> [$inst] configuring Composer VCS repos + http-basic auth"
  # Self-hosted GitLab: plain VCS repo + http-basic (username + token) so Composer
  # resolves via git over HTTPS (read_repository), no API scope needed. --auth
  # writes to the project auth.json (in the html volume); the token is passed as
  # an argument, never echoed by this script.
  dc exec -T "$inst" composer config repositories.twint vcs "$VCS_URL"
  dc exec -T "$inst" composer config --auth "http-basic.${GITLAB_HOST}" "$GITLAB_USERNAME" "$GITLAB_TOKEN"
  dc exec -T "$inst" composer config repositories.sdk vcs "$SDK_URL"
  dc exec -T "$inst" composer config --auth "http-basic.${SDK_HOST}" "$GITLAB_USERNAME" "$GITLAB_TOKEN"

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
