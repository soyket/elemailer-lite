# Elemailer Lite: instructions for AI agents

Elemailer Lite is the free WordPress plugin that lets users design form emails with Elementor. Elemailer (Pro) is a separate premium plugin. The owner states that Pro does not require Lite to be installed or active, so there is no file or compatibility dependency between them. Do not add code that assumes either plugin needs the other.

## Read first
1. docs/architecture.md (how the code is wired, with file paths)
2. docs/known-issues.md (verified problems, do not repeat them in new code)
3. .agents/rules/*.md
4. docs/tasks/current-task.md (the task you are doing now)

## Facts you must know
- Plugin version is 2.8. WordPress 5.8+, **PHP 7.2+** (readme.txt `Requires PHP: 7.2`). Write PHP 7.2 compatible code: no typed properties, arrow functions, `match`, named arguments, nullsafe `?->`, `str_contains()`, `??=`.
- Text domain `elemailer-lite`. Namespace `Elemailer_Lite`. Constants prefix `ELE_MAILER_LITE_`.
- Classes are autoloaded from a lowercase, hyphenated path (see docs/architecture.md, Autoloader). Name new classes and files to match.
- Do not edit `helpers/vendor/` or `helpers/src/`. They hold third-party CSS-inlining code used by `Helpers\Util` and were not reviewed file by file. `lib/` only contains an `index.php` stub.
- **Generated file:** `app/form-template/view/default-elementor-style.php` is rewritten by `Plugin::init()` from `public/assets/css/elemailer-mail.css` on every request, and it is tracked in Git. Edit the CSS source, never this file. If Git shows it modified and you did not change the CSS source, do not commit it.
- Do not edit `languages/elemailer-lite.pot` by hand. A GitHub Action regenerates it on every push to `master`.
- Front-end and editor CSS/JS are plain files enqueued directly from `public/assets/` and `integrations/elementor/assets/`. No PHP code references a build step. There is no `src/` directory, so `npm run build` is not a real build for this repo.
- Every folder has an `index.php` stub (53 bytes). Keep adding one to new folders.

## Hard rules
- Never commit to `master`. Work on a branch and open a pull request.
- Never create a Git tag or a GitHub release. Pushing any tag triggers an automatic deploy to WordPress.org (see docs/release-process.md).
- Keep stable: CPT slugs `em-form-template` (and the reserved `em-emails-template`), REST namespace `elemailer_lite/v1`, option keys (`elemailer_lite_info`, `elemailer_hide_mail_branding`, `elemailer_lite_notice_never_show`, `elemailer_lite_notice_not_now_time`), post meta key `elemailer__template_setting`, shortcode format `[elemailer:id="ID"]`, widget names (`elemailer-*`, used by wpml-config.xml and saved Elementor data), action/filter names beginning `elemailer_lite/`. Changing any of these requires an entry in docs/decisions.md and a migration plan for existing sites.
- Keep the free limit of 3 templates unless the task explicitly changes it. It is enforced in two places (see docs/architecture.md).
- All user-facing strings use the `elemailer-lite` text domain.
- Follow .agents/rules/wordpress-security.md. Do not copy the weak patterns listed in docs/known-issues.md.
- No unrelated refactors. Keep diffs small.

## Commands
- Syntax lint: `composer install` then `composer lint:syntax`
- Coding standards (advisory, legacy code has violations): `composer lint:phpcs`
- Translations: `npm run i18n` (needs WP-CLI)

## Definition of done
- `composer lint:syntax` passes
- No new PHPCS errors in lines you changed
- Manually tested in WordPress with Elementor active and WP_DEBUG on, no new notices in debug.log (see docs/local-setup.md)
- Docs updated if behavior changed
- docs/tasks/current-task.md updated

## Version bump (manual, 4 places)
The version appears in `elemailer-lite.php` header, `ELE_MAILER_LITE_VERSION` in `plugin.php`, `Stable tag` plus changelog in `readme.txt`. `package.json` is stale (1.1.0). Only bump versions when the owner asks.

## Handoff protocol (model or account switch)
Before stopping: commit and push your branch, then update docs/tasks/current-task.md with done, remaining, blockers, and the exact next command. The next agent must resume from the repository, not from chat history.
