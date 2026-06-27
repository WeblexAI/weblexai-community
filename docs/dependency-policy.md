# Dependency Policy

WeblexAI Community Edition should prefer stable, tagged upstream packages.

Temporary compatibility forks are allowed only when all of these are true:

- the dependency is required for a supported feature
- there is no compatible tagged upstream release
- the resolved package commit is pinned in `composer.lock`
- the fork purpose is documented before release

Current temporary forks: none.

Before a stable public release, either move these forks under WeblexAI-controlled repositories with tags or replace them with compatible upstream releases.
