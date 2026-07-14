#!/usr/bin/env bash
# One-time host setup for the devbox (Ubuntu/Debian). Idempotent.
set -euo pipefail

if ! command -v apt-get >/dev/null 2>&1; then
  echo "ERROR: bootstrap.sh targets Ubuntu/Debian (apt-based). Aborting." >&2
  exit 1
fi

echo "==> Installing prerequisites (ca-certificates, curl, git)"
sudo apt-get update -y
sudo apt-get install -y ca-certificates curl git

echo "==> Adding Docker's official apt repository"
sudo install -m 0755 -d /etc/apt/keyrings
if [ ! -f /etc/apt/keyrings/docker.asc ]; then
  sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  sudo chmod a+r /etc/apt/keyrings/docker.asc
fi
# shellcheck disable=SC1091
. /etc/os-release
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

echo "==> Installing Docker Engine + Compose plugin"
sudo apt-get update -y
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

echo "==> Enabling Docker service"
sudo systemctl enable --now docker

echo "==> Adding $USER to the docker group"
sudo usermod -aG docker "$USER"

echo
echo "Docker:  $(docker --version)"
echo "Compose: $(docker compose version)"
echo
echo "NOTE: log out and back in (or run 'newgrp docker') for docker-group"
echo "      membership to take effect before running devbox/bin/up.sh."
