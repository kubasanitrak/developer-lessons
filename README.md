# developer-lessons

WordPress plugin for pay-per-post lesson content (Comgate, bank transfer, invoices).

Repository: https://github.com/kubasanitrak/developer-lessons

## Releasing an update

1. Bump `Version` and `DL_VERSION` in `developer-lessons.php`.
2. Add a `## [x.y.z]` section to `CHANGELOG.md`.
3. Commit and push to `main`.
4. Create and push a matching tag (header `1.1.6` → tag `v1.1.6`):

```bash
git tag v1.1.6
git push origin v1.1.6
```

GitHub Actions builds `developer-lessons.zip` and publishes a GitHub Release. Installed sites check for updates via [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (vendored under `lib/plugin-update-checker/`).

On a WordPress site: **Dashboard → Updates** or **Plugins → Check for updates** (when available).

## First release note

Sites already on `1.1.5` will not see an update until a GitHub Release exists with a version **newer** than the installed copy (e.g. tag `v1.1.6`).
