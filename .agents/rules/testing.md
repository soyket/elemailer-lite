# Testing rules

- Run `composer lint:syntax` before every commit.
- Run `composer lint:phpcs` and fix issues in files you changed (the legacy codebase has existing violations; do not mass-reformat).
- Manual smoke test for any change: activate plugin, create/edit an email template in Elementor, preview, save, confirm the 3-template limit notice, check debug.log for notices.
- When adding logic that can be isolated (helpers, parsers, shortcode handling), add PHPUnit tests under `tests/`.
- Report what you ran and the result in the PR description.
