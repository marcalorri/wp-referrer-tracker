---
trigger: model_decision
---
# Project release documentation

When preparing a ReferrerTracker WordPress plugin release:

- Update the plugin version in `referrertracker.php` in both the plugin header and `REFERRERTRACKER_VERSION`.
- Update `Stable tag` in `readme.txt` to the same version.
- Add a new top entry in the `readme.txt` changelog with the release notes.
- Validate changed PHP files with `php -l` before committing.
- Check `git status --short --branch` before committing.
- Create a commit with a concise release-oriented message.
- Create a matching Git tag using the format `vX.Y.Z`.
- Push both `main` and the tag to GitHub.
- After pushing, verify that the GitHub release/update workflow has the expected tag available.
