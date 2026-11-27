# Elemailer Lite: instructions for AI agents

Elemailer Lite is the free WordPress plugin that lets users design form emails with Elementor. Elemailer (Pro) is a separate premium plugin. The owner states that Pro does not require Lite to be installed or active, so there is no file or compatibility dependency between them. Do not add code that assumes either plugin needs the other.

## Read first (tiered, to save tokens)
Always read: this file and docs/tasks/current-task.md.
Then read only what the task needs:
- The relevant item in docs/core-changes-needed.md (ids C1, C2, ...), not the whole file.
- The relevant section of docs/architecture.md (how the code is wired, with file paths).
- The relevant rule file in .agents/rules/ (architecture, testing, or wordpress-security).
- docs/known-issues.md only for the area you are changing (verified problems, do not repeat them in new code).

## Token budget
- Search first (grep for the function, hook, or string), then read the matching function or line range. Do not read whole large files when a range answers the question.
- Do not read `helpers/vendor/`, `helpers/src/`, root `vendor/` or `node_modules/` if present, `.wordpress-org/`, `languages/elemailer-lite.pot`, lockfiles (`composer.lock`, `package-lock.json`), images, or `app/form-template/view/default-elementor-style.php` unless the task is specifically about them.
- Keep command output small: run PHPCS on changed files only (`composer lint:phpcs:changed`), and summarize findings in docs/tasks/current-task.md instead of pasting long logs.

## Facts you must know
- Plugin version is 2.8. WordPress 5.8+. readme.txt says PHP 7.2, but the bundled Composer code (`helpers/vendor`) refuses to run below **PHP 7.2.5**, so treat 7.2.5 as the real minimum. Write code that works on 7.2.5: no typed properties, arrow functions, `match`, named arguments, nullsafe `?->`, `str_contains()`, `??=`.
- Text domain `elemailer-lite`. Namespace `Elemailer_Lite`. Constants prefix `ELE_MAILER_LITE_`.
- Classes are autoloaded from a lowercase, hyphenated path (see docs/architecture.md, Autoloader). Name new classes and files to match.
- Do not edit `helpers/vendor/` (Composer output: symfony/css-selector 5.4.3, symfony/polyfill-php80 1.24.0, tijsverkoyen/css-to-inline-styles 2.2.4). `helpers/src/` is an unused copy of the inliner, and `lib/` only holds an `index.php` stub.
- **Generated file:** `app/form-template/view/default-elementor-style.php` is rewritten by `Plugin::init()` from `public/assets/css/elemailer-mail.css` on every request, and it is tracked in Git. Edit the CSS source, never this file. If Git shows it modified and you did not change the CSS source, do not commit it.
- Do not edit `languages/elemailer-lite.pot` by hand. A GitHub Action regenerates it on every push to `master`.
- Front-end and editor CSS/JS are plain files enqueued directly from `public/assets/` and `integrations/elementor/assets/`. No PHP code references a build step. There is no `src/` directory, so `npm run build` is not a real build for this repo.
- Every folder has an `index.php` stub (53 bytes). Keep adding one to new folders.
- Each Elementor widget repeats the same inline-style code for margin, padding and background. Email clients ignore most external CSS, so widgets write inline styles on purpose.

## Hard rules
- Never commit to `master`. Work on a branch and open a pull request.
- Never create a Git tag or a GitHub release. Pushing any tag triggers an automatic deploy to WordPress.org (see docs/release-process.md).
- Keep stable: CPT slugs `em-form-template` (and the reserved `em-emails-template`), REST namespace `elemailer_lite/v1`, option keys (`elemailer_lite_info`, `elemailer_hide_mail_branding`, `elemailer_lite_notice_never_show`, `elemailer_lite_notice_not_now_time`), post meta key `elemailer__template_setting`, shortcode format `[elemailer:id="ID"]`, widget names (`elemailer-*`, used by wpml-config.xml and saved Elementor data), action/filter names beginning `elemailer_lite/`. Changing any of these requires an entry in docs/decisions.md and a migration plan for existing sites.
- Keep the free limit of 3 templates unless the task explicitly changes it. It is enforced in two places (see docs/architecture.md).
- All user-facing strings use the `elemailer-lite` text domain.
- Follow .agents/rules/wordpress-security.md. Do not copy the weak patterns listed in docs/known-issues.md.
- When you fix an item from docs/core-changes-needed.md, tick it there and in docs/tasks/backlog.md, and remove it from docs/known-issues.md in the same pull request.
- No unrelated refactors. Keep diffs small.

## Commands
- Syntax lint: `composer install` then `composer lint:syntax`
- Unit tests: `composer test:unit`
- WordPress smoke test: `npm install`, `npm run env:start`, `npm run test:smoke`, `npm run env:stop` (needs Docker)
- Coding standards on changed files (advisory, legacy code has violations): `composer lint:phpcs:changed` (full run: `composer lint:phpcs`)
- Translations: `npm run i18n` (needs WP-CLI)

## Definition of done
- `composer lint:syntax` passes
- `composer test:unit` passes
- `npm run test:smoke` passes when the change touches WordPress runtime behavior and Docker is available
- No new PHPCS errors in lines you changed (`composer lint:phpcs:changed`)
- Manually tested in WordPress with Elementor active and WP_DEBUG on, no new notices in debug.log (see docs/local-setup.md)
- Docs updated if behavior changed
- docs/tasks/current-task.md updated

## Version bump (manual, 4 places)
The version appears in `elemailer-lite.php` header, `ELE_MAILER_LITE_VERSION` in `plugin.php`, `Stable tag` plus changelog in `readme.txt`. `package.json` is stale (1.1.0). Only bump versions when the owner asks.

## Handoff protocol (model or account switch)
Before stopping: commit and push your branch, then update docs/tasks/current-task.md with done, remaining, blockers, and the exact next command. The next agent must resume from the repository, not from chat history.
