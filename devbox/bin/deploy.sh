#!/usr/bin/env bash
# Deploy the TWINT plugin to ALL instances via Composer, at the ref that the
# host clone is CURRENTLY checked out on. No ref argument — check out the branch
# you want first, then run this. This makes it impossible to install a ref other
# than what is checked out.
#
# Usage:
#   git checkout <branch> && git pull      # pick what to deploy
#   devbox/bin/deploy.sh
set -euo pipefail
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Self-update: pull the latest of the current branch, then re-exec the fresh copy
# (so the host always runs the newest tooling for this branch). Runs once.
# Skips gracefully if not in a git repo; opt out with DEVBOX_NO_SELF_UPDATE=1.
if [ -z "${DEVBOX_SELF_UPDATED:-}" ] && [ -z "${DEVBOX_NO_SELF_UPDATE:-}" ] \
   && git -C "$SELF_DIR" rev-parse --git-dir >/dev/null 2>&1; then
  echo "==> updating devbox tooling (git pull --ff-only)"
  GIT_TERMINAL_PROMPT=0 git -C "$SELF_DIR" pull --ff-only \
    || echo "WARNING: git pull failed; continuing with current checkout" >&2
  exec env DEVBOX_SELF_UPDATED=1 "$0" "$@"
fi

. "$SELF_DIR/_lib.sh"
load_env

# The branch the host clone is on IS what gets deployed. Composer installs the
# matching dev-<branch> from GitLab, so checkout and install can never diverge.
BRANCH="$(git -C "$SELF_DIR" rev-parse --abbrev-ref HEAD)"
if [ "$BRANCH" = "HEAD" ]; then
  REPO_ROOT="$(git -C "$SELF_DIR" rev-parse --show-toplevel)"
  echo "ERROR: clone is in detached HEAD (no branch to deploy)." >&2
  echo "       Check out a branch first:  git -C $REPO_ROOT checkout <branch>" >&2
  exit 1
fi
CONSTRAINT="dev-${BRANCH}"

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
VCS_URL="https://${GIT_NP}"
SDK_NP="$(norm_remote "$SDK_REMOTE")"
SDK_HOST="${SDK_NP%%/*}"
SDK_URL="https://${SDK_NP}"

# Per-deploy log: tee everything below to a timestamped file + the console, so
# each deploy's full process is recorded. Logs live in devbox/logs/ (gitignored).
LOG_DIR="$DEVBOX_DIR/logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/deploy-$(date +%Y%m%d-%H%M%S)-${BRANCH//\//-}.log"
exec > >(tee -a "$LOG_FILE") 2>&1
echo "==> deploy started $(date -Is) | branch=$BRANCH | constraint=$CONSTRAINT | log=$LOG_FILE"

deploy_to() {
  local inst="$1"

  echo "==> [$inst] configuring Composer VCS repos + http-basic auth"
  # Self-hosted GitLab: plain VCS repo + http-basic (username + token) so Composer
  # resolves via git over HTTPS (read_repository), no API scope needed. --auth
  # writes to the project auth.json (in the html volume); the token is passed as
  # an argument, never echoed by this script.
  dc exec -T "$inst" composer config repositories.twint vcs "$VCS_URL"
  dc exec -T "$inst" composer config --auth "http-basic.${GITLAB_HOST}" "$GITLAB_USERNAME" "$GITLAB_TOKEN"
  dc exec -T "$inst" composer config repositories.sdk vcs "$SDK_URL"
  dc exec -T "$inst" composer config --auth "http-basic.${SDK_HOST}" "$GITLAB_USERNAME" "$GITLAB_TOKEN"

  echo "==> [$inst] composer require ${PLUGIN_PACKAGE}:${CONSTRAINT}"
  dc exec -T "$inst" composer require "${PLUGIN_PACKAGE}:${CONSTRAINT}" \
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

echo "==> deploying ${PLUGIN_PACKAGE}:${CONSTRAINT} to all instances"
for inst in "${INSTANCES[@]}"; do
  deploy_to "$inst"
  echo "==> [$inst] done"
done
echo "==> all instances on ${CONSTRAINT}"
echo "==> deploy finished $(date -Is) | log=$LOG_FILE"
