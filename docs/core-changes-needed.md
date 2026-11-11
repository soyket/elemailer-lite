# Core file changes needed (main plugin files)

This is the working list of changes the plugin's own source files need. Each item names the file and function, what is wrong now, the change to make, and how to test it. Descriptions of the underlying problems are in docs/known-issues.md. Use item ids (C1, C2, ...) in branch names, commits and pull requests.

Rules for working from this list:
- One item (or a few tightly related items) per branch and pull request. Never bundle with the AI setup files.
- Items marked **owner decision** need an answer from the plugin owner before coding.
- After merging an item, tick it here and in docs/tasks/backlog.md, and remove it from docs/known-issues.md.
- Nothing in this list has been changed yet. Status of every item: open.

## Security (do first)

- [ ] **C1** `integrations/elementor/library.php` `my_template_delete_request()`: calls `wp_delete_post( $template_id, true )` for any id with only `edit_posts`. Change: `absint()` the id; require post type `elementor_library` and meta `_elementor_template_type` = `elemailer_library`; require `current_user_can( 'delete_post', $id )`. Test: a Contributor cannot delete a normal post through the AJAX action; an admin can still delete a saved library template. Note: the Lite panel JS defines `_myTemplateDelete` but its click binding was not found in the part of the file that was read, so the Lite UI may not call this action at all.
- [ ] **C2** `integrations/elementor/library.php` `remote_request()` and `elementor_batch_process()`: `wp_remote_get()` on a URL from `$_POST['url']`. Change: only allow `https` and the host `lib.elemailer.com`; use `wp_safe_remote_get()` with a timeout; check `is_wp_error()` and the response code. Test: template library still lists, previews and imports; a URL to another host is refused.
- [ ] **C3** `integrations/elementor/library.php` `my_template_remote_request()` and `my_template_elementor_batch_process()`: read `_elementor_data` of any post id. Change: require post type `elementor_library` with the Elemailer type meta and `current_user_can( 'edit_post', $id )`.
- [ ] **C4** `app/form-template/action.php` `get_template_setting()` (and the REST route in `core/api.php`): readable by anyone and returns the title of any post id. Change: require `manage_options` and `get_post_type( $id ) === 'em-form-template'`. Also in `update()`: require the same post type. **Must be done together with** `public/assets/js/admin/form-template-functions.js`: the GET sends `X-WP-Nonce` from `data-nonce` on the clicked link, but the nonce is printed on the form `#elemailer-modal-settings`; make the script send the form nonce for both calls. Test: Edit modal still loads for an admin; an anonymous request returns 401/403 or an empty result.
- [ ] **C5** `helpers/notice.php` `dismiss()` and `enqueue_scripts()`: any logged-in user can set any user-meta key to `true` or create any transient with a chosen expiry. Change: `check_ajax_referer()` with a nonce printed by `enqueue_scripts()`; accept only ids starting `elemailer-sites-notice-id-`; `meta` only `user` or `transient`; `absint( $time )` with a cap. Test: dismissing the review notice still works and stays dismissed.
- [ ] **C6** `integrations/elementor/base.php` `display_admin_notice_logic()`: updates options from `$_GET` without nonce or capability. Change: require `manage_options` and a nonce; build the Not Now and Never Show links in `display_admin_notice()` with `wp_nonce_url()`.
- [ ] **C7** `core/global.php`: three AJAX handlers registered with `wp_ajax_nopriv_`. Change: remove the `nopriv` registrations; use `check_ajax_referer()` and `current_user_can( 'edit_posts' )`; return JSON with `wp_send_json_success()`; keep the `postTypeNonce` field name or update `integrations/elementor/assets/js/editor.js` in the same change. Test: Latest Posts and Selected Posts widget dropdowns still fill in the Elementor editor.
- [ ] **C8** `app/form-template/action.php` `sanitize()`: values are copied unsanitized. Change: sanitize per field (`title`, `subject` with `sanitize_text_field`; `emailTo`, `emailFrom`, `emailReplyTo` with `sanitize_text_field` or `sanitize_email` as the field allows shortcodes, which needs an owner decision on allowed syntax).

## Correctness and robustness

- [ ] **C9** `integrations/elementor/widgets/video/video.php` Vimeo branch: `unserialize( file_get_contents( 'http://vimeo.com/api/v2/video/$vid.php' ) )` at render time. Problems: plain http, `unserialize` on remote data, blocking request on every render, no error handling (`$hash[0]` on failure), and the id is taken with `substr( $url, 18, 9 )`. Change: parse the id with a regex, fetch over https with `wp_remote_get()` and a timeout, decode JSON (or oEmbed), cache with a transient, and fall back to a default thumbnail. **Owner decision:** whether the old Vimeo v2 endpoint is still wanted; oEmbed is the usual replacement.
- [ ] **C10** `plugin.php` `init()`: `Util::minify_css()` writes `app/form-template/view/default-elementor-style.php` on every request. Change: run it on activation and when the source CSS file changes (compare `filemtime`), or build it at release time. **Owner decision:** keep the generated file tracked in Git or ignore it (the `.gitignore` line is currently commented out).
- [ ] **C11** `integrations/elementor/library.php` `get_default_page_builder()`: uncached remote request each time the editor footer loads. Change: cache the decoded list in a transient (for example 12 hours) and clear it in the sync AJAX action; set a timeout.
- [ ] **C12** `integrations/elementor/widgets/latest-posts/latest-posts.php` `render()`: loops over `get_the_terms()` without checking for false or `WP_Error` when 'current' is chosen. Change: guard with `is_array()`.
- [ ] **C13** `plugin.php` `load_js_css_admin()` and `show_free_version_limitation()`; `app/form-template/base.php` `modal_view()`: use `get_current_screen()` without a null check. Change: guard the result.
- [ ] **C14** `core/api.php` `action()`: returns nothing when no method matches. Change: return `new WP_Error( 'rest_no_route', ..., array( 'status' => 404 ) )`.
- [ ] **C15** `integrations/elementor/widgets/selected-posts/selected-posts.php` `render()`: renders any post id stored in the widget, with no status check. **Owner decision:** limit to published posts (`get_post_status( $post ) === 'publish'`).

## Escaping and strings

- [ ] **C16** `widgets/heading/heading.php`: the link is printed with `esc_attr()`; use `esc_url()`. `widgets/image/image.php` and `widgets/image-box/image-box.php`: `alt` and `title` from `Control_Media` and the `width` attribute are echoed without a local escape; wrap in `esc_attr()` (verify the output is unchanged for normal values).
- [ ] **C17** `public/views/templates.php`: `{{{site_title}}}` prints a title from the remote library unescaped inside the Elementor editor. Change to `{{site_title}}` and check that titles with entities still look right (the panel JS has its own `_unescape` helpers).
- [ ] **C18** `integrations/elementor/actions/hooks.php` `register_document_config()` and the Terms description in `widgets/latest-posts/latest-posts.php` use the text domain `elemailer`; change to `elemailer-lite`. `register_document_config()` also returns nothing for other post types; return `$obj`.
- [ ] **C19** `integrations/elementor/widgets/text-editor/text-editor.php`: WYSIWYG content is printed raw. **Owner decision:** keep (Elementor-authored content) or pass through `Util::kses()` / `wp_kses_post()`; test that email layouts do not change.

## Compatibility and metadata

- [ ] **C20** PHP minimum. `readme.txt` says `Requires PHP: 7.2`, but `helpers/vendor/composer/platform_check.php` requires PHP 7.2.5 or newer and raises a fatal error below that (symfony/css-selector 5.4.3 needs `>=7.2.5`). **Owner decision:** raise the stated minimum to 7.2.5 in `readme.txt` (and the plugin header if added), or rebuild `helpers/vendor` without the platform check.
- [ ] **C21** Elementor version strings. `integrations/elementor/base.php` checks `ELEMENTOR_VERSION >= 3.0.11` but its three notices say 3.4; `elemailer-lite.php` header says `Elementor tested up to: 3.4`. Make the notices use one constant that matches the check, and update the header after testing against the current Elementor.
- [ ] **C22** `public/assets/css/elemailer-mail.css` contains selectors for the legacy Elementor markup (`.elementor-column-wrap`, `.elementor-row`, `.elementor-section-wrap`). Test email output with the current Elementor in both default and Optimized DOM modes before changing layout CSS. No change proposed yet.
- [ ] **C23** `app/form-template/cpt.php` allowed settings fields (`subject`, `emailTo`, `emailFrom`, `emailReplyTo`) are never sent by the modal, which only has the name field. **Owner decision:** add the fields to `public/views/template-create-modal.php` or remove them from the allowed list.

## Dead code (remove only after a repository-wide search finds no references)

- [ ] **C24** `integrations/elementor/actions/container.php`: fake `Container` class marked obsolete since 1.8; its `require_once` is commented out in `actions/hooks.php`.
- [ ] **C25** `helpers/src/`: a copy of `CssToInlineStyles` that is not autoloaded (the Composer PSR-4 map points to `helpers/vendor/tijsverkoyen/css-to-inline-styles/src`). Note: this copy uses `mb_convert_encoding( ..., 'HTML-ENTITIES', ... )`, which is deprecated since PHP 8.2; whether the vendored copy does the same was not checked.
- [ ] **C26** `core/taxonomy.php`: abstract class with no subclass found.
- [ ] **C27** `public/assets/js/functions.js` and `integrations/elementor/assets/js/frontend.js`: placeholder files that only contain a comment.

## Release tooling

- [ ] **C28** `.distignore`: `.Gruntfile.js` should be `Gruntfile.js`; also exclude `package.json` and `package-lock.json` if they should not ship.
- [ ] **C29** `Gruntfile.js`: rtlcss, cssmin and uglify read `assets/css` and `assets/js` at the repo root, which do not exist; `version-bump` rewrites a three-part version from `package.json` (1.1.0) while the plugin is 2.8; the `copy` excludes do not list `docs/`, `AGENTS.md` or `CLAUDE.md`.
- [ ] **C30** `package.json`: `start` and `build` run `wp-scripts` but there is no `src/` directory; version is stale; `@wordpress/scripts` is `^19.2.2`. **Owner decision:** keep, update or remove.
- [ ] **C31** `.gitattributes`: duplicate entries and rules for `/Build/` and `/Tests/` that do not exist (cosmetic).
