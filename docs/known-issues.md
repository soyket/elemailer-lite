# Known issues (verified in code, NOT fixed)

Found by reading the source on 2026-11-11. Nothing here has been changed. Fix each one in its own small pull request and test it. Severity is a judgment, not a measured result.

## Security

1. **Library delete handler can delete any post.** `Library::my_template_delete_request()` (integrations/elementor/library.php) checks the `elemailer-lite-check` nonce and `edit_posts`, then calls `wp_delete_post( $template_id, true )` with an ID from `$_POST`. It does not check the post type or `delete_post` for that ID. Any user with `edit_posts` (for example Contributor) who has the nonce can permanently delete any post. Severity: high.
2. **Server-side request from user-controlled URL.** `remote_request()` and `elementor_batch_process()` call `wp_remote_get()` with a URL taken from `$_POST['url']` (users with `edit_posts`). No host allowlist, not `wp_safe_remote_get()`. Severity: medium.
3. **Template meta readable for any post ID.** `my_template_remote_request()` returns `_elementor_data` for any post ID given, with only `edit_posts`. Severity: medium.
4. **Public REST read endpoint.** `Core\Api` uses `permission_callback` `__return_true`, and `Action::get_template_setting()` has no capability or post-type check. `GET /wp-json/elemailer_lite/v1/form-templates/get/{id}` returns the post title of any post ID (including non-template and non-public posts) to anyone. Severity: medium. Caution when fixing: the admin script reads the nonce for this GET from `data-nonce` on the clicked link, while the nonce is printed on the modal form. Verify which nonce is sent and update the script before adding a capability check, or the Edit modal will stop loading.
5. **Public AJAX endpoints.** `elemailer_get_taxonomies`, `elemailer_get_terms`, `elemailer_get_posts` (core/global.php) are registered with `wp_ajax_nopriv_` and only verify a `wp_rest` nonce. `elemailer_get_posts` returns IDs and titles for the post type given. The only callers found are in `integrations/elementor/assets/js/editor.js`, which runs in the Elementor editor for logged-in users, so the `nopriv` registrations appear unnecessary. Severity: low to medium.
6. **State change on GET without nonce or capability.** `Elementor\Base::display_admin_notice_logic()` (hooked to `admin_init`) updates options `elemailer_lite_notice_not_now_time` and `elemailer_lite_notice_never_show` from `$_GET` for any user who loads an admin page. Severity: low.
7. **Notice dismissal writes arbitrary data.** `Helpers\Notice::dismiss()` (AJAX `elemailer-notices`, any logged-in user, no nonce, no capability) takes `id`, `time`, `meta` from `$_POST`. With `meta` = `user` it runs `update_user_meta( current user, $id, true )`, which sets any user-meta key to `true` on the caller's own account. Otherwise it runs `set_transient( $id, true, $time )` with an arbitrary name and an expiry from the request (empty means no expiry). Severity: medium to low.
8. **Template settings are not sanitized.** `Action::sanitize()` copies values whose keys are in the allowed list but does not sanitize the values. Stored values (subject, emailTo, emailFrom, emailReplyTo, title) go into post meta as received. Admin-only write. Severity: low.
9. **Update does not check the post type.** `Action::update()` runs `wp_update_post()` and `update_post_meta()` for whatever ID is given (admin only). Severity: low.
10. **Unescaped notice output.** `Notice::markup()` prints `message` and the `data` attribute string without escaping. Current callers pass plugin-controlled strings. Severity: low.

## Robustness
- `Plugin::init()` calls `Util::minify_css()` on every request, writing `app/form-template/view/default-elementor-style.php` each time. This breaks on read-only file systems, costs I/O on every page load, and dirties a Git-tracked file during local development.
- `Library::get_default_page_builder()` makes a synchronous remote request with no caching every time the editor footer loads for an Elemailer post, and when the sync AJAX action runs.
- `Elementor\Base` notices say Elementor 3.4 is needed but the check is `ELEMENTOR_VERSION >= 3.0.11`. The plugin header says 'Elementor tested up to: 3.4'.
- Some strings use the text domain `elemailer` instead of `elemailer-lite` (`Actions\Hooks::register_document_config()` and the Terms description in the Latest Posts widget). `register_document_config()` also returns nothing for other post types.
- `Elementor\Base::init()` and `Plugin::init()` rely on `get_current_screen()` in places without checking it is set.
- Latest Posts widget: when the term 'current' is chosen and the current post has no terms in that taxonomy, `get_the_terms()` returns false and the code loops over it without checking, which raises a PHP warning.
- `Core\Api::action()` returns nothing when no method matches the HTTP method and action.
- The create/edit modal contains only the template name field, so subject, emailTo, emailFrom and emailReplyTo are in the allowed settings list but never sent by the UI.

## Release tooling
- `Gruntfile.js` rtlcss, cssmin and uglify read `assets/css` and `assets/js` at the repo root, which do not exist (assets live under `public/assets`).
- `Gruntfile.js` `version-bump` only rewrites a three-part version (x.y.z) in the plugin header and takes the number from `package.json` (1.1.0). The plugin version is 2.8, so it does not match.
- `.distignore` lists `.Gruntfile.js` (leading dot), so Gruntfile.js, package.json and package-lock.json are not excluded from the WordPress.org deploy.
- `.gitattributes` has duplicate entries and mentions folders that do not exist (`/Build/`, `/Tests/`); harmless but untidy.
