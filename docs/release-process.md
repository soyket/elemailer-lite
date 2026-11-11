# Release process (as implemented)

## Automation in .github/workflows
- `main.yml` (Elemailer-Lite POT file Generator): runs on every push to `master`. Generates `languages/elemailer-lite.pot` using `iamdharmesh/action-wordpress-pot-generator`.
- `wporg.yml` (Deploy to WordPress.org): runs when **any tag** is pushed. Uses `10up/action-wordpress-plugin-deploy@stable` with secrets `VSVN_USERNAME` and `VSVN_PASSWORD`, slug `elemailer-lite`. The package contents are filtered by `.distignore`.
- `ai-quality-checks.yml` (added on branch ai-native-dev-setup): PHP syntax lint on PHP 7.2 and 8.3 for pull requests and non-master pushes; PHPCS is advisory.

## Therefore
- Do not push tags unless you intend to publish to WordPress.org.
- Merging to `master` triggers the POT generator.

## Manual release checklist
1. Update the version in `elemailer-lite.php` (header), `plugin.php` (`ELE_MAILER_LITE_VERSION`), and `readme.txt` (`Stable tag` and changelog entry). Update `Tested up to` if needed.
2. Run the manual smoke test in .agents/rules/testing.md.
3. Merge to `master`.
4. Tag the release (this deploys to WordPress.org).

## Package contents
The `.distignore` on this branch excludes AGENTS.md, CLAUDE.md, .agents, docs, tests, root vendor, composer files, and phpcs.xml.dist. `helpers/vendor` is NOT excluded because the plugin needs it at runtime. `.gitattributes` also marks these AI and tooling files `export-ignore`, so GitHub source archives leave them out.

## PHP requirement
`readme.txt` says `Requires PHP: 7.2`, but `helpers/vendor/composer/platform_check.php` requires 7.2.5 or newer and ends the request with a fatal error below that. Decide in core-changes item C20 whether to change the stated minimum or rebuild the vendor folder.

## Grunt
`Gruntfile.js` has a `release` task (clean, copy, compress) that builds `elemailer-lite-{package.json version}.zip`. Its copy step has its own exclude list and does not know about the AI files (the `docs/`, `AGENTS.md` and `CLAUDE.md` paths would be copied into a Grunt-built zip; dot-folders such as `.agents` are skipped by default). Prefer the `.distignore`-based WordPress.org deploy, or add the paths to the Grunt exclude list. See docs/known-issues.md for problems in the other Grunt tasks.
