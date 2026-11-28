# Current task

## Goal
Add a reproducible WordPress test environment and first automated tests for Elemailer Lite: wp-env setup, PHPUnit unit tests for `Helpers\Util`, and a WordPress smoke test. Test tooling and docs only; no plugin runtime behavior changes and no changes to `master`.

## Scope / out of scope
In scope: `.wp-env.json`, `phpunit.xml.dist`, `tests/`, npm and Composer test scripts/dependencies, local setup and testing docs, packaging exclusions for test tooling.
Out of scope: C1-C38 code fixes, GitHub workflow changes, version bumps, merges to `master`.

## Acceptance criteria
- [x] `.wp-env.json`, `package.json`, `composer.json`, and `phpunit.xml.dist` parse
- [x] `composer test:unit` passes where PHP/Composer are available
- [x] `composer lint:syntax` passes
- [x] `composer lint:phpcs:changed` passes or reports only pre-existing issues outside changed lines
- [ ] `npm run test:smoke` passes where Docker is available; if Docker is unavailable, the limitation is stated in the handoff
- [ ] Owner reviews the branch diff

## Manual test steps
Run `npm install`, `composer install`, `npm run env:start`, `composer test:unit`, `npm run test:smoke`, and `npm run env:stop`. The smoke test activates Elemailer Lite, Elementor, and Contact Form 7 in wp-env, renders a minimal Elemailer template, expands the Elemailer shortcode, captures `wp_mail()` instead of sending it, and fails on assertion failures.

## Status / handoff
Done: implementation and local verification (config parsing, unit tests, syntax lint, PHPCS changed files). The smoke test also passes on a local non-Docker WordPress test site (WordPress 7.1.5, SQLite, Elemailer Lite 2.8, Elementor 4.4.2, Contact Form 7 6.2.6), and its fixture now runs as an administrator and writes `_elementor_version` meta like an editor-built document.
Remaining: Docker-backed wp-env smoke test run (WordPress 6.8.2) and owner review.
Blockers: Docker may not be available in every agent environment; unit tests and config checks still run without Docker.
Next step: owner review of the C1-C8 security branch; the C1 removal branch removes the unused library delete handler and JS caller instead of keeping the hardened handler.
