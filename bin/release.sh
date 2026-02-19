#!/usr/bin/env bash

set -euo pipefail

RELEASE_BOT_NAME="TWINT Release Bot"
RELEASE_BOT_EMAIL="plugin@twint.ch"

export GIT_COMMITTER_NAME="${RELEASE_BOT_NAME}"
export GIT_COMMITTER_EMAIL="${RELEASE_BOT_EMAIL}"
export GIT_AUTHOR_NAME="${RELEASE_BOT_NAME}"
export GIT_AUTHOR_EMAIL="${RELEASE_BOT_EMAIL}"

[ -z "${1+x}" ] && echo "Usage: $0 <version>" && exit 1

base_dir=$(dirname "$0")/../

version="$1"
store_version="${version}-store"

if [ -z "${TWINT_DRY_RUN:=}" ]; then
  git diff --exit-code
  git diff --exit-code --cached
fi

FILES=("${base_dir}/composer.json" "${base_dir}/src/Core/Setting/Settings.php")

sed -i -e "s@dev-master@${version}@g" "${FILES[@]}"
git commit --no-gpg-sign -m "chore(release-management): create release ${version} (direct installation)" "${FILES[@]}"
git tag -a "${version}" -m "chore(release-management): tag ${version} (direct installation)" --no-sign

sed -i -e "s@public const INSTALL_SOURCE = .*;@public const INSTALL_SOURCE = InstallSource::STORE;@g" "${FILES[@]}"
git commit --no-gpg-sign -m "chore(release-management): create release ${version} (store installation)" "${FILES[@]}"
git tag -a "${store_version}" -m "chore(release-management): tag ${version} (store installation)" --no-sign

git archive --format=zip -o "${base_dir}/twint-shopware-plugin-${store_version}.zip" --prefix="TwintPayment/" "${store_version}"

if [ -z "$TWINT_DRY_RUN" ]; then
    git reset --hard HEAD^^
    git push origin "${version}" "${store_version}"
fi
