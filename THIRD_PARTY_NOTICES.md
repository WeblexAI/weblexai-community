# Third-Party Notices

WeblexAI Community Edition is licensed under Apache-2.0. It also includes and depends on third-party software under their own licenses.

## PHP Dependencies

PHP dependency names, versions, and licenses are locked in `composer.lock`. Generate the current machine-readable report with:

```bash
composer licenses --format=json > composer-licenses.json
```

The current dependency set is primarily MIT, Apache-2.0, BSD-2-Clause, BSD-3-Clause, and LGPL-2.1-or-later.

## JavaScript Dependencies

JavaScript dependency names, versions, and licenses are locked in `package-lock.json`. Generate an inventory with:

```bash
npm ls --all --json > npm-dependencies.json
```

The browser SDK and frontend assets are built from `resources/` and emitted into `public/wlai` and `public/build`.

## Simple Icons

The installer footer includes the GitHub icon from Simple Icons:

- `public/images/brand/github.svg`

Simple Icons is licensed under CC0-1.0. The GitHub logo remains a GitHub trademark.

## Container Images

The default deployment uses the images declared in `Dockerfile` and `docker-compose.yml`, including FrankenPHP, PostgreSQL, Redis, and Python for the optional update-agent and E2E mock provider. Review image licenses and security advisories during each release.

## Release Gate

Before publishing a release, run:

```bash
composer audit --locked
npm audit --audit-level=high
```

Record any accepted vulnerability or license exception in the release notes.
