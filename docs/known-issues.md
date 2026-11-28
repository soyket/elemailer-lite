# Known issues (verified in code, NOT fixed)

Found by reading the source on 2026-11-11. Fixed items are removed from this list. The file-by-file fix plan with ids is in docs/core-changes-needed.md. Severity is a judgment, not a measured result.

## Security

1. **Unescaped output.** `Notice::markup()` prints `message` and the `data` attribute string unescaped (plugin-controlled strings today). `heading.php` prints the link with `esc_attr()` instead of `esc_url()`. `image.php` and `image-box.php` echo `alt`, `title` (and `image-box` the `width` attribute) without a local escape. `text-editor.php` prints the WYSIWYG value as is. `public/views/templates.php` prints `{{{site_title}}}` from the remote library unescaped. Severity: low. (C16, C17, C19)
2. **Remote data parsed with `unserialize`.** The Vimeo branch of `widgets/video/video.php` runs `unserialize( file_get_contents( 'http://vimeo.com/api/v2/video/$vid.php' ) )` at render time, over plain http, with no error handling. Severity: medium. (C9)
3. **Selected Posts shows any post id.** `widgets/selected-posts/selected-posts.php` renders the title, excerpt and link of every post id stored in the widget without checking its status. Severity: low. (C15)

## Robustness
- `Plugin::init()` calls `Util::minify_css()` on every request, writing `app/form-template/view/default-elementor-style.php` each time. This breaks on read-only file systems, costs I/O on every page load, and dirties a Git-tracked file during local development. (C10)
- Effective PHP minimum is 7.2.5: `helpers/vendor/composer/platform_check.php` stops PHP below 7.2.5 with a fatal error, while `readme.txt` says `Requires PHP: 7.2`. (C20)
- `Elementor\Base` notices say Elementor 3.4 is needed but the check is `ELEMENTOR_VERSION >= 3.0.11`. The plugin header says 'Elementor tested up to: 3.4'. (C21)
- Some strings use the text domain `elemailer` instead of `elemailer-lite` (`Actions\Hooks::register_document_config()` and the Terms description in the Latest Posts widget). `register_document_config()` also returns nothing for other post types. (C18)
- `Plugin::load_js_css_admin()`, `Plugin::show_free_version_limitation()` and `Base::modal_view()` use `get_current_screen()` without checking it is set. (C13)
- Latest Posts widget: when the term 'current' is chosen and the current post has no terms in that taxonomy, `get_the_terms()` returns false and the code loops over it without checking, which raises a PHP warning. (C12)
- The create/edit modal contains only the template name field, so subject, emailTo, emailFrom and emailReplyTo are in the allowed settings list but never sent by the UI. (C23)
- `public/assets/css/elemailer-mail.css` targets legacy Elementor markup; behaviour with the current Elementor was not tested. (C22)
- The panel script `elemailer-elementor-admin-page.js` calls `createTemplate()` in an unreachable branch and no such function exists; it also relies on implicit globals (`ElemailerElementorAdmin`, `$elementorscope`). (C33)

## Dead or duplicate code
- `integrations/elementor/actions/container.php` (obsolete `Container` class), `helpers/src/` (unused copy of the CSS inliner, uses a call deprecated in PHP 8.2), `core/taxonomy.php` (no subclass), and two placeholder JS files. (C24 to C27)
- The PHP actions `elemailer-lite-my-template-remote-request` and `elemailer-lite-elementor-batch-my-template-process`: no caller in the Lite panel script. (C3)

## Release tooling
- `Gruntfile.js` rtlcss, cssmin and uglify read `assets/css` and `assets/js` at the repo root, which do not exist (assets live under `public/assets`). `version-bump` only rewrites a three-part version (x.y.z) in the plugin header and takes the number from `package.json` (1.1.0). The plugin version is 2.8, so it does not match. (C29)
- `package.json` scripts `start` and `build` run `wp-scripts` but there is no `src/` directory. (C30)
- `.gitattributes` has duplicate entries and mentions folders that do not exist (`/Build/`, `/Tests/`). (C31)
