#!/usr/bin/env bash

RELEASE_HOST=github.com
RELEASE_REPOSITORY=git@${RELEASE_HOST}:Twint-AG/twint-shopware-plugin.git

echo "Syncing release ${CI_COMMIT_TAG}"
mkdir -p ~/.ssh
chmod 400 "${TWINT_GITHUB_DEPLOY_KEY}"
ssh-keyscan ${RELEASE_HOST} >> ~/.ssh/known_hosts
GIT_SSH_COMMAND="ssh -i ${TWINT_GITHUB_DEPLOY_KEY}" git push --force "${RELEASE_REPOSITORY}" HEAD^:latest "${CI_COMMIT_TAG}:${CI_COMMIT_TAG}"
