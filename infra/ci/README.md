# Local CI (`infra/ci/`)

Run the GitLab `test` job on your machine, in Docker, using the **same image and
`spc` as CI** (`shivammathur/node:jammy`). Verify a branch before pushing — so a
red pipeline means a real problem, not a flaky shared runner.

## Usage

```bash
infra/ci/local-ci.sh tests            # all PHP 8.1–8.5 in parallel
infra/ci/local-ci.sh tests 8.3        # just one (or a few) versions
```

Per-version logs land in `infra/ci/logs/` (git-ignored). The command prints a
`PASS`/`FAIL` line per version.

## What it reproduces (from `.gitlab-ci.yml` → `test`)

Matrix PHP 8.1–8.5: `spc -U` → `spc --php-version X --extensions "…"` →
`composer install --optimize-autoloader` → `vendor/bin/ecs` →
`vendor/bin/rector process src --dry-run`. (No phpunit / no release — CI's `test`
job doesn't run them.)

## Notes

- Each job copies the repo into a container-local workdir (excluding
  `.git/vendor/node_modules/dist/build`), so parallel jobs never clobber each
  other and your working tree is untouched.
- Private-SDK auth (dev branches): `GITLAB_TOKEN` from `infra/local/.env` maps to
  CI's `gitlab-token.git.nfq.asia`. Blank on stable branches.
- If `.gitlab-ci.yml` changes, update `local-ci.sh` to match (extension list +
  step order live in both).
