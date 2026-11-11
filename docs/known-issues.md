# Known issues (verified in code, NOT fixed)

Found by reading the source on 2026-11-11. Nothing here has been changed. The file-by-file fix plan with ids is in docs/core-changes-needed.md. Severity is a judgment, not a measured result.

## Security

1. **Library delete handler can delete any post.** `Library::my_template_delete_request()` (integrations/elementor/library.php) checks the `elemailer-lite-check` nonce and `edit_posts`, then calls `wp_delete_post( $template_id, true )` with an ID from `$_POST`. It does not check the post type or `delete_post` for that ID. Any user with `edit_posts` (for example Contributor) who has the nonce can permanently delete any post. The Lite panel JS defines a delete function but its click binding was not found in the part read, so the Lite UI may not use this action. Severity: high. (C1)
2. **Server-side request from user-controlled URL.** `remote_request()` and `elementor_batch_process()` call `wp_remote_get()` with a URL taken from `$_POST['url']` (users with `edit_posts`). No host allowlist, not `wp_safe_remote_get()`. Severity: medium. (C2)
3. **Template meta readable for any post ID.** `my_template_remote_request()` and `my_template_elementor_batch_process()` read `_elementor_data` of any post ID given, with only `edit_posts`. Severity: medium. (C3)
4. **Public REST read endpoint.** `Core\Api` uses `permission_callback` `__return_true`, and `Action::get_template_setting()` has no capability or post-type check. `GET /wp-json/elemailer_lite/v1/form-templates/get/{id}` returns the post title of any post ID (including non-template and non-public posts) to anyone. Severity: medium. Caution when fixing: the admin script reads the nonce for this GET from `data-nonce` on the clicked link, while the nonce is printed on the modal form. Verify which nonce is sent and update the script before adding a capability check, or the Edit modal will stop loading. (C4)
5. **Public AJAX endpoints.** `elemailer_get_taxonomies`, `elemailer_get_terms`, `elemailer_get_posts` (core/global.php) are registered with `wp_ajax_nopriv_` and only verify a `wp_rest` nonce. `elemailer_get_posts` returns IDs and titles for the post type given. The only callers found are in `integrations/elementor/assets/js/editor.js`, which runs in the Elementor editor for logged-in users, so the `nopriv` registrations appear unnecessary. Severity: low to medium. (C7)
6. **State change on GET without nonce or capability.** `Elementor\Base::display_admin_notice_logic()` (hooked to `admin_init`) updates options `elemailer_lite_notice_not_now_time` and `elemailer_lite_notice_never_show` from `$_GET` for any user who loads an admin page. Severity: low. (C6)
7. **Notice dismissal writes arbitrary data.** `Helpers\Notice::dismiss()` (AJAX `elemailer-notices`, any logged-in user, no nonce, no capability) takes `id`, `time`, `meta` from `$_POST`. With `meta` = `user` it runs `update_user_meta( current user, $id, true )`, which sets any user-meta key to `true` on the caller's own account. Otherwise it runs `set_transient( $id, true, $time )` with an arbitrary name and an expiry from the request (empty means no expiry). Severity: medium to low. (C5)
8. **Template settings are not sanitized.** `Action::sanitize()` copies values whose keys are in the allowed list but does not sanitize the values. Admin-only write. Severity: low. (C8)
9. **Update does not check the post type.** `Action::update()` runs `wp_update_post()` and `update_post_meta()` for whatever ID is given (admin only). Severity: low. (C4)
10. **Unescaped output.** `Notice::markup()` prints `message` and the `data` attribute string unescaped (plugin-controlled strings today). `heading.php` prints the link with `esc_attr()` instead of `esc_url()`. `image.php` and `image-box.php` echo `alt`, `title` (and `image-box` the `width` attribute) without a local escape. `text-editor.php` prints the WYSIWYG value as is. `public/views/templates.php` prints `{{{site_title}}}` from the remote library unescaped. Severity: low. (C16, C17, C19)
11. **Remote data parsed with `unserialize`.** The Vimeo branch of `widgets/video/video.php` runs `unserialize( file_get_contents( 'http://vimeo.com/api/v2/video/$vid.php' ) )` at render time, over plain http, with no error handling. Severity: medium. (C9)
12. **Selected Posts shows any post id.** `widgets/selected-posts/selected-posts.php` renders the title, excerpt and link of every post id stored in the widget without checking its status. Severity: low. (C15)

## Robustness
- `Plugin::init()` calls `Util::minify_css()` on every request, writing `app/form-template/view/default-elementor-style.php` each time. This breaks on read-only file systems, costs I/O on every page load, and dirties a Git-tracked file during local development. (C10)
- `Library::get_default_page_builder()` makes a synchronous remote request with no caching every time the editor footer loads for an Elemailer post, and when the sync AJAX action runs. (C11)
- Effective PHP minimum is 7.2.5: `helpers/vendor/composer/platform_check.php` stops PHP below 7.2.5 with a fatal error, while `readme.txt` says `Requires PHP: 7.2`. (C20)
- `Elementor\Base` notices say Elementor 3.4 is needed but the check is `ELEMENTOR_VERSION >= 3.0.11`. The plugin header says 'Elementor tested up to: 3.4'. (C21)
- Some strings use the text domain `elemailer` instead of `elemailer-lite` (`Actions\Hooks::register_document_config()` and the Terms description in the Latest Posts widget). `register_document_config()` also returns nothing for other post types. (C18)
- `Plugin::load_js_css_admin()`, `Plugin::show_free_version_limitation()` and `Base::modal_view()` use `get_current_screen()` without checking it is set. (C13)
- Latest Posts widget: when the term 'current' is chosen and the current post has no terms in that taxonomy, `get_the_terms()` returns false and the code loops over it without checking, which raises a PHP warning. (C12)
- `Core\Api::action()` returns nothing when no method matches the HTTP method and action. (C14)
- The create/edit modal contains only the template name field, so subject, emailTo, emailFrom and emailReplyTo are in the allowed settings list but never sent by the UI. (C23)
- `public/assets/css/elemailer-mail.css` targets legacy Elementor markup; behaviour with the current Elementor was not tested. (C22)

## Dead or duplicate code
- `integrations/elementor/actions/container.php` (obsolete `Container` class), `helpers/src/` (unused copy of the CSS inliner, uses a call deprecated in PHP 8.2), `core/taxonomy.php` (no subclass), and two placeholder JS files. (C24 to C27)

## Release tooling
- `Gruntfile.js` rtlcss, cssmin and uglify read `assets/css` and `assets/js` at the repo root, which do not exist (assets live under `public/assets`). `version-bump` only rewrites a three-part version (x.y.z) in the plugin header and takes the number from `package.json` (1.1.0). The plugin version is 2.8, so it does not match. The `copy` excludes do not list the AI files. (C29)
- `.distignore` lists `.Gruntfile.js` (leading dot), so Gruntfile.js, package.json and package-lock.json are not excluded from the WordPress.org deploy. (C28)
- `package.json` scripts `start` and `build` run `wp-scripts` but there is no `src/` directory. (C30)
- `.gitattributes` has duplicate entries and mentions folders that do not exist (`/Build/`, `/Tests/`). (C31)
