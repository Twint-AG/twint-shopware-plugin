#!/usr/bin/env bash
# Deploy the TWINT plugin to ALL instances via Composer, always at the ref the
# host clone is checked out on — so the checkout and the installed ref can never
# diverge.
#
# Usage:
#   devbox/bin/deploy.sh              # deploy the currently checked-out branch
#   devbox/bin/deploy.sh <branch>     # checkout <branch> on the host, then deploy it
#
# NOTE: the ref you deploy must itself contain devbox/ (this tooling lives in the
# plugin repo). Deploying a ref without devbox/ would remove this script on
# checkout. Deploy your feature branch, or master once devbox/ is merged.
set -euo pipefail
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# First pass: bring the host clone to the ref to deploy, then re-exec once.
#   - with a ref arg: fetch + checkout that ref (reset to origin for a branch)
#   - without:        pull --ff-only the current branch
# Opt out of any git work with DEVBOX_NO_SELF_UPDATE=1.
if [ -z "${DEVBOX_READY:-}" ] && [ -z "${DEVBOX_NO_SELF_UPDATE:-}" ] \
   && git -C "$SELF_DIR" rev-parse --git-dir >/dev/null 2>&1; then
  export GIT_TERMINAL_PROMPT=0
  if [ -n "${1:-}" ]; then
    echo "==> checking out '$1' on the host clone"
    git -C "$SELF_DIR" fetch --all --prune
    if git -C "$SELF_DIR" rev-parse --verify --quiet "origin/$1" >/dev/null; then
      git -C "$SELF_DIR" checkout -B "$1" "origin/$1"
      git -C "$SELF_DIR" reset --hard "origin/$1"
    else
      git -C "$SELF_DIR" checkout "$1"   # tag or commit (detached)
    fi
  else
    echo "==> updating current branch (git pull --ff-only)"
    git -C "$SELF_DIR" pull --ff-only \
      || echo "WARNING: git pull failed; continuing with current checkout" >&2
  fi
  exec env DEVBOX_READY=1 "$0"           # re-exec the (possibly updated) script
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

  echo "==> [$inst] installing ${PLUGIN_PACKAGE}:${CONSTRAINT} (dist, clean extract)"
  # Allow the SDK's HTTP-client discovery plugin (blocked by default in composer 2.2+).
  dc exec -T "$inst" composer config --no-plugins allow-plugins.php-http/discovery true
  # Point composer.json at the target ref (no install yet), wipe any prior copy,
  # then install as a DIST archive. --prefer-dist means no .git in vendor, so the
  # Shopware build writing node_modules/compiled assets into the package dir never
  # trips composer's "uncommitted changes" check on the next deploy; and
  # .gitattributes export-ignore trims the archive to the plugin runtime (no
  # devbox/infra/tests copied recursively into vendor).
  dc exec -T "$inst" composer require --no-update "${PLUGIN_PACKAGE}:${CONSTRAINT}"
  dc exec -T "$inst" rm -rf "/var/www/html/vendor/${PLUGIN_PACKAGE}"
  dc exec -T "$inst" composer update "${PLUGIN_PACKAGE}" \
    --prefer-dist --with-all-dependencies --no-interaction --no-progress

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
