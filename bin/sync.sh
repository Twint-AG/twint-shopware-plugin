#!/usr/bin/env bash

set -euo pipefail

RELEASE_HOST=github.com
RELEASE_REPOSITORY=git@${RELEASE_HOST}:Twint-AG/twint-shopware-plugin.git

RELEASE_BOT_NAME="TWINT Release Bot"
RELEASE_BOT_EMAIL="plugin@twint.ch"

# Internal deployment / infra tooling that must never reach the public GitHub
# mirror. `git push` ignores .gitattributes export-ignore, so these paths are
# stripped from the pushed tree with a scrub commit before pushing.
EXCLUDE_PATHS=(devbox infra)

echo "Syncing release ${CI_COMMIT_TAG}"
mkdir -p ~/.ssh
chmod 400 "${TWINT_GITHUB_DEPLOY_KEY}"
ssh-keyscan "${RELEASE_HOST}" >> ~/.ssh/known_hosts

# Remove the excluded paths from the index. --ignore-unmatch keeps this a no-op
# for paths that don't exist at this commit, so the script never fails on them.
git -c user.name="${RELEASE_BOT_NAME}" -c user.email="${RELEASE_BOT_EMAIL}" \
    rm -r --cached --quiet --ignore-unmatch "${EXCLUDE_PATHS[@]}"

# Record the removal as a scrub commit on top of the release commit. Skip the
# commit when nothing was staged (excluded paths absent), leaving HEAD as-is.
if ! git diff --cached --quiet; then
  git -c user.name="${RELEASE_BOT_NAME}" -c user.email="${RELEASE_BOT_EMAIL}" \
      commit --no-gpg-sign --quiet \
      -m "chore(sync): strip internal deployment tooling from public mirror"
fi

# Point the release tag at the scrubbed commit so the published tag matches the
# 'latest' branch. This only affects the GitHub mirror; the origin tag is untouched.
git tag -f -a "${CI_COMMIT_TAG}" -m "release ${CI_COMMIT_TAG}" --no-sign

GIT_SSH_COMMAND="ssh -i ${TWINT_GITHUB_DEPLOY_KEY}" \
  git push --force "${RELEASE_REPOSITORY}" \
    HEAD:refs/heads/latest "${CI_COMMIT_TAG}:${CI_COMMIT_TAG}"
