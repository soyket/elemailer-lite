# Elemailer Lite: AI Agent Rules

Elemailer Lite is the free WordPress plugin (Elementor email template builder). Elemailer (Pro) is a separate, standalone premium plugin.
**Pro does NOT require Lite to be installed or active.** There is no file or compatibility dependency between them. Do not add cross-plugin requirements, shared-file assumptions, or compatibility shims.

## Read first
1. docs/architecture.md
2. docs/tasks/current-task.md
3. .agents/rules/*.md

## Stack
- PHP (namespace `Elemailer_Lite`, custom autoloader in autoloader.php), WordPress, Elementor
- Build/i18n: npm + Grunt + @wordpress/scripts (see package.json)
- Text domain: `elemailer-lite`; constants prefix: `ELE_MAILER_LITE_`

## Commands
- Install JS deps: `npm install`
- Build: `npm run build`
- Regenerate translations: `npm run i18n`
- Install PHP dev tools: `composer install`
- Syntax lint: `composer lint:syntax`
- Coding standards (advisory): `composer lint:phpcs`

## Working rules
- Work on a feature branch; never commit to master directly. Open a PR.
- Make a short plan and list files before changing more than 3 files.
- Keep changes small and scoped to the current task. No unrelated refactors.
- Do not edit `lib/` (third-party) or generated files (`languages/*.pot`, built assets) by hand.
- Preserve existing option keys, CPT slug (`em-form-template`), REST namespace (`elemailer_lite/v1`), hooks, and shortcodes. Changing them needs a note in docs/decisions.md and a migration plan.
- Keep free-version limits (3 templates) intact unless the task says otherwise.
- All user-facing strings use `__()`/`esc_html__()` with text domain `elemailer-lite`.
- Never commit secrets or API keys.

## Definition of done
- Syntax lint passes; no new PHPCS errors in changed files
- No PHP notices/warnings with WP_DEBUG on
- Manually verified in WordPress + Elementor (see docs/local-setup.md)
- Security rules followed (.agents/rules/wordpress-security.md)
- docs/ updated if behavior or architecture changed
- docs/tasks/current-task.md updated with status and next steps (handoff)

## Handoff protocol (model/account switching)
Before stopping: commit, push, and update docs/tasks/current-task.md (done / remaining / blockers / next command to run). The next agent resumes from the repo, not chat history.
