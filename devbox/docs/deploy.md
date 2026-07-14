# Deploying the plugin

```bash
bin/deploy.sh              # deploy the branch the host clone is currently on
bin/deploy.sh <branch>     # checkout <branch> on the host clone, then deploy it
```

The plugin is always installed on **all three instances**, at the same
branch — there's no per-instance target. The point is to validate one plugin
version across 6.5/6.6/6.7 at once.

Branches only — a tag or commit hash left the host clone in detached HEAD, and
`deploy.sh` refuses to run from detached HEAD (there'd be no branch name to
derive the Composer constraint from). To deploy an unmerged commit, push it to
a branch and pass the branch name.

The ref you deploy must itself contain `devbox/` (this tooling lives in the
plugin repo) — deploying a branch without it would remove these scripts on
checkout.

## Self-update, then deploy
Before anything else, `deploy.sh` brings the **host clone** to the ref you
asked for, then re-execs itself so the freshly-checked-out branch's own
`devbox/` scripts run:
- No argument: `git pull --ff-only` on whatever branch is currently checked
  out (a failed pull is logged as a warning and the run continues with the
  current checkout).
- With a `<branch>` argument: `git fetch --all --prune`, then `git checkout -B
  <branch> origin/<branch>` + `git reset --hard origin/<branch>`.

Either way, the branch the host clone ends up on **is** what gets deployed —
checkout and installed ref can never diverge. Set `DEVBOX_NO_SELF_UPDATE=1` to
skip this step entirely (deploy exactly what's already checked out, with no
git operations). Set `DEVBOX_READY=1` if you need to bypass the re-exec guard
directly (used internally).

The Composer constraint is just `dev-<branch>` — the branch name the host
clone is on after the step above, no other mapping.

## What it does, for each instance
1. **Configure Composer**: register the plugin's GitLab repo and its private
   `twint-ag/sdk` dependency (`SDK_REMOTE`) as VCS repositories
   (`composer config repositories.* vcs <url>`), and set **http-basic** auth
   (`GITLAB_USERNAME` + `GITLAB_TOKEN`) for **both** hosts via
   `composer config --auth http-basic.<host> <user> <token>`. This only needs
   `read_repository` — no GitLab API scope. Also allows the SDK's
   `php-http/discovery` Composer plugin (blocked by default since Composer
   2.2).
2. **`composer require twint-ag/twint-shopware-plugin:dev-<branch> --no-update`**,
   then wipe any prior `vendor/twint-ag/twint-shopware-plugin`, then
   **`composer update twint-ag/twint-shopware-plugin --prefer-dist
   --with-all-dependencies`** — pulls the plugin and its deps (`twint-ag/sdk`,
   `chillerlan/php-qrcode`) into that instance's own `vendor/`, resolved for
   that instance's PHP version. `shopware/*` requirements are satisfied by the
   running platform. `--prefer-dist` avoids a `.git` in vendor (so later
   builds writing compiled assets into the package dir never trip Composer's
   "uncommitted changes" check on the next deploy).
3. **Trim the installed copy**: with a `read_repository`-only token there's no
   GitLab API dist archive, so Composer clones the whole repo into vendor —
   ignoring `.gitattributes export-ignore`. `deploy.sh` removes `.git` and
   deletes every path the plugin's `.gitattributes` marks `export-ignore`, so
   the vendored plugin doesn't recursively carry its own `devbox/`, `infra/`,
   `tests/`, etc.
4. **Shopware**: `plugin:refresh` → `plugin:install --activate TwintPayment`
   (falls back to `plugin:update TwintPayment` if already installed) → build
   administration + storefront (`bin/build-administration.sh` &&
   `bin/build-storefront.sh`) → `cache:clear`.

## Logs
Every run writes (and tees to the console) a timestamped log at
`devbox/logs/deploy-<timestamp>-<branch>.log` (gitignored).

## Examples
```bash
bin/deploy.sh                       # deploy whatever the host clone is on
bin/deploy.sh master                # checkout + deploy master
bin/deploy.sh feature/express-x     # checkout + deploy a feature branch
```

## Idempotency
Re-running `deploy.sh` on already-deployed instances updates rather than
errors (`composer update` refreshes the branch's tip; `plugin:update` runs if
the plugin is already installed).
