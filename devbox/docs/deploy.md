# Deploying the plugin

```
bin/deploy.sh [branch|commit]
```
`ref` defaults to `master`. The plugin is installed on **all three instances**
(same ref everywhere) — the point is to validate one plugin version across
6.5/6.6/6.7 at once.

## Ref → Composer constraint
`deploy.sh` maps the ref you pass to a Composer version constraint:

| You pass | Composer constraint | Meaning |
|----------|---------------------|---------|
| `master` (default) | `dev-master` | tip of master |
| `feature/x` | `dev-feature/x` | tip of that branch |
| `e8737e40` (7–40 hex) | `dev-master#e8737e40` | that commit (must be reachable from master) |

To deploy an unmerged commit, push it to a branch and pass the branch name.

## What it does, for each instance
1. **Configure Composer**: register the GitLab repo as a VCS repository and set
   `gitlab-token` (from `GITLAB_TOKEN`) in the instance's `auth.json`.
2. **`composer require twint-ag/twint-shopware-plugin:<constraint>`** — Composer
   pulls the plugin **and its dependencies** (`twint-ag/sdk`, `chillerlan/php-qrcode`)
   into that instance's own `vendor/`, resolved for that instance's PHP version.
   `shopware/*` requirements are satisfied by the running platform.
3. **Shopware**: `plugin:refresh` → `plugin:install --activate TwintPayment`
   (falls back to `plugin:update` if already installed) → build administration +
   storefront → `cache:clear`.

Each instance has its own isolated copy (own `vendor/`, own build), persisted in
its `swXX_html` volume. No bind-mount, no host checkout.

## Examples
```bash
bin/deploy.sh master               # all three on master
bin/deploy.sh feature/express-x    # all three on a feature branch
bin/deploy.sh e8737e40             # all three on a specific commit
```

## Idempotency
Re-running `deploy.sh` on already-deployed instances updates rather than errors
(Composer updates the constraint; `plugin:update` runs if already installed).
