#!/usr/bin/env bash

set -euo pipefail

RELEASE_BOT_NAME="TWINT Release Bot"
RELEASE_BOT_EMAIL="plugin@twint.ch"

[ -z "${1+x}" ] && echo "Usage: $0 <version>" && exit 1

base_dir=$(dirname "$0")/../

version="$1"

git diff --exit-code
git diff --exit-code --cached
sed -e "s:9\.9\.9-dev:$version:" -i ${base_dir}/composer.json
GIT_COMMITTER_NAME="${RELEASE_BOT_NAME}" GIT_COMMITTER_EMAIL="${RELEASE_BOT_EMAIL}" GIT_AUTHOR_NAME="${RELEASE_BOT_NAME}" GIT_AUTHOR_EMAIL="${RELEASE_BOT_EMAIL}" git commit -m "chore(release-management): create release ${version}" composer.json
GIT_COMMITTER_NAME="${RELEASE_BOT_NAME}" GIT_COMMITTER_EMAIL="${RELEASE_BOT_EMAIL}" git tag -a "${version}" -m "chore(release-management): tag ${version}" --no-sign
git reset --hard HEAD^
git push origin "${version}"
