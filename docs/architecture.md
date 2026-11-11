# Architecture (Elemailer Lite 2.8)

Written from reading the source. The Coverage section at the end lists exactly what has and has not been read. Planned changes to the files described here are in docs/core-changes-needed.md.

## Boot sequence
1. `elemailer-lite.php` (plugin header: Version 2.8, text domain `elemailer-lite`, Elementor tested up to 3.4) requires `autoloader.php` and `plugin.php`.
2. It registers activation and deactivation hooks on `Elemailer_Lite\Plugin::instance()`.
3. On `plugins_loaded` priority 150: if the action `elemailer/after_load` has NOT already fired, it runs `do_action('elemailer_lite/before_load')`, requires `core/global.php`, calls `Plugin::instance()->init()`, then `do_action('elemailer_lite/after_load')`. If that action has already fired, Lite does not initialize at all.
4. On `wp_loaded`, under the same guard: `Plugin::after_wp_loaded_hooks()` calls `remove_editor_styles()`.

`Plugin::__construct()` defines constants, runs `Autoloader::run()`, and hooks `load_textdomain` on `init`.

## Constants (plugin.php)
`ELE_MAILER_LITE_VERSION` ('2.8'), `ELE_MAILER_LITE_PACKAGE` ('free'), `ELE_MAILER_LITE_PLUGIN_URL`, `ELE_MAILER_LITE_PLUGIN_DIR`, `ELE_MAILER_LITE_PLUGIN_PUBLIC`, `ELE_MAILER_LITE_PLUGIN_PUBLIC_DIR`.

## Autoloader
`Autoloader::autoload()` ignores classes outside the `Elemailer_Lite` namespace. It strips the namespace, inserts a hyphen between a lowercase and uppercase letter, turns `_` into `-`, turns `\` into a directory separator, and lowercases. Example: `Elemailer_Lite\Integrations\Elementor\Widgets\Base` loads `integrations/elementor/widgets/base.php`. Widget classes are loaded with `require_once`, not the autoloader.

## Core building blocks (core/, traits/)
- `Traits\Singleton`: `instance()` creates one object per class using a static property.
- `Core\Cpt` (abstract): the constructor calls `post_type()` and `post_args()` and registers the post type on `init`.
- `Core\Taxonomy` (abstract): same idea for taxonomies on `init`. No subclass exists.
- `Core\Api` (abstract): REST route registration and dispatch (see Templates below).
- `core/global.php`: three AJAX handlers (`elemailer_get_taxonomies`, `elemailer_get_terms`, `elemailer_get_posts`), loaded from the main plugin file on `plugins_loaded`.

## Plugin::init()
- Admin menu `Elemailer` (slug `elemailer-menu`, capability `manage_options`, position 25) and submenu Settings (`em-form-template-settings`, renders `public/views/em-form-template-settings.php`: a branding opt-out checkbox that saves `elemailer_hide_mail_branding` through `options.php`, plus Pro and donate links). Registers setting `elemailer_hide_mail_branding` in group `em-form-template-settings-group`.
- Admin assets only on screen `edit-em-form-template`: `admin-style.css`, `form-template-functions.js`, localized as `elemailer_lite` with `restUrl` (`rest_url('elemailer_lite/v1/')`) and a `wp_rest` nonce.
- `wp_enqueue_scripts` priority 999: dequeues theme assets and non-allowlisted plugin assets on post type `em-form-template` (allowlist: this plugin, Elementor, Elementor Pro for scripts, Query Monitor, Debug Bar; Elementor Pro styles are dequeued).
- Shows a non-dismissible error notice on the list screen: 'You are able to create only 3 templates with elemailer lite'.
- Starts `Helpers\Notice`, `Integrations\Shortcode\Base`, `Integrations\Elementor\Base`, `Integrations\Elementor\Library`.
- Calls `Util::minify_css()` on every request to rewrite `public/assets/css/elemailer-mail.css` into `app/form-template/view/default-elementor-style.php` (file write on each page load). That target file is tracked in Git (the `.gitignore` line for it is commented out).
- `App\Form_Template\Base` is initialized on `after_setup_theme`.
- Activation: flushes rewrite rules and stores option `elemailer_lite_info` (`activation_version`, `activation_time`, `old_user`). `old_user` is 1 if a previous info record had version 1.0.0 or old_user set.

## Templates (app/form-template)
- `Cpt`: post type `em-form-template`, rewrite slug `em-form-template`, public, `show_in_rest` false, capability type `page`, supports title, editor, elementor, permalink, shown under menu `elemailer-menu`. Allowed settings fields: title, subject, emailTo, emailFrom, emailReplyTo.
- `Base::init()`: creates `Cpt` and `Api`, prints the create modal (`public/views/template-create-modal.php`) on the list screen, adds a Shortcode column showing `[elemailer:id="ID"]`, and hooks `publish_em-form-template`.
- `Action`: `store()` requires `manage_options`; id `0` means insert. `insert()` allows creation only while the count of all `em-form-template` posts (all statuses, via `wp_count_posts`) is below 3, creates the post as `publish`, saves settings in post meta `elemailer__template_setting`, and sets `_wp_page_template` to `elementor_canvas`. `update()` updates the title and meta. `get_template_setting($id)` returns the meta plus post title. `get_all_template()` returns published templates as id => title for the Elementor form selector.
- Free limit enforcement is therefore in two places: `Action::insert()` (refuses creation at 3 or more posts of any status) and `Base::our_elemailer_lite_post_number_max()` (on publish, if more than 3 are published and the old status was not publish, sets the post to draft).
- `Api` (prefix `form-templates`, param `/(?P<id>\w+)`): `post_update()` calls `Action::store()`, `get_get()` calls `Action::get_template_setting()`.
- `Core\Api`: registers `elemailer_lite/v1/{prefix}` with route `/(?P<action>\w+)/{param}`, all HTTP methods, `permission_callback` `__return_true`. The callback runs the method named `{lowercase http method}_{action}`. Full URL shape: `/wp-json/elemailer_lite/v1/form-templates/update/{id}` (POST) and `/get/{id}` (GET).

## Admin screen flow (public/)
- The list-table links (row Edit, Add New, row title) are intercepted by `public/assets/js/admin/form-template-functions.js`. Add New opens the modal with a default name `Form template # {timestamp}`. Edit calls `GET form-templates/get/{id}` and fills the modal. Save calls `POST form-templates/update/{id}` (id 0 for new) using the nonce on the modal form. The modal form contains only one field, the template name (`title`), so the other settings fields (subject, emailTo, emailFrom, emailReplyTo) are never sent by the UI. 'Edit with Elementor' saves, then redirects to `post.php?post={id}&action=elementor`.
- Settings page: `public/views/em-form-template-settings.php`.

## Email rendering (helpers/util.php)
- Requires `helpers/vendor/autoload.php`, which loads Composer's `platform_check.php`. That check stops PHP below 7.2.5 with a fatal error. The inliner is `tijsverkoyen/css-to-inline-styles` 2.2.4 with `symfony/css-selector` 5.4.3 and `symfony/polyfill-php80` 1.24.0 (Composer PSR-4 map points to `helpers/vendor`, not to `helpers/src`). The inliner's own CSS processor strips `@media` blocks and `@charset` before inlining.
- `render_elementor_content()` uses Elementor `get_builder_content_for_display()`.
- `get_template_styles()` splits the CSS block from the output, rewrites `.elementor-element.` to `.`, removes media queries, removes the opening `<style>` tag, and returns the CSS followed by `</style>`.
- `get_template_content()` returns the body without the style block.
- `get_email_html_template()` replaces `<section>` with `<div>`, wraps in an HTML document, includes `default-elementor-style.php` and then prints `get_template_styles()`, adds footer branding when `elemailer_lite_info.old_user` is set and falsy and `elemailer_hide_mail_branding` is not 'yes', then inlines CSS. **Important:** `default-elementor-style.php` deliberately starts with `<style>` and has no closing tag (the closing `</style>` line in `Util::minify_css()` is commented out). The closing tag comes from `get_template_styles()`, so the default CSS and the template CSS end up in one `<style>` element. Never add a `</style>` to the generated file.
- `kses()` helper with a custom allowlist (used by the Button widget).

## Styles and assets (what each CSS file is for)
- `public/assets/css/elemailer-mail.css`: source of the default email CSS (base typography, 600px max width for sections, column widths `.elementor-col-*`, mobile rule that stacks columns below 600px). Minified into `app/form-template/view/default-elementor-style.php`. It targets legacy Elementor markup (`.elementor-column-wrap`, `.elementor-row`, `.elementor-section-wrap`).
- `app/form-template/view/default-elementor-style.php` (generated, 2,705 bytes, plain `<style>` text with no PHP tag): see the Important note above.
- `public/assets/css/style.css` (handle `elemailer-lite`): loaded by `Plugin::load_elementor_css_public()` on the front end and editor preview for post type `em-form-template`; its first comment says it is not sent in emails. It repeats most of `elemailer-mail.css`, adds backward-compatibility rules, hides the admin bar Elementor links for the template post types, hides `.elementor-location-floating_buttons`, and hides some Elementor panel items (`#elementor-panel-get-pro-elements-sticky`, `[aria-label="Site Settings"]`, `[aria-label="Notes"]`, and two generated `.eui-*-Mui*` class names).
- `public/assets/css/admin-style.css` (handle `elemailer-ui`, whole file read): loaded only on the template list screen. The first part styles the create modal (`.elemailer-form-template-add-modal`, `.elemailer-modal-*`, response messages) and the preloader (`.sk-chase*`). Everything after the comment 'This Style For Elemailer Dashboard' (dashboard header and tabs, filters, tables, create-mail progress bar, template categories, create form and builder area, send/schedule area, settings area, `#signUpConfirm`, data-table list rules) plus global Select2 rules is not used by any Lite view that was read, so it looks shared with Pro.
- `integrations/elementor/assets/css/editor.css` (Elementor editor panel): shows only the Elemailer widget category; hides the Advanced tab, most panel menu items, global settings, the elements navigation, responsive switchers, the footer settings, global colour pickers; fixes the preview width in the iframe; replaces the Elementor 'get pro elements' box text with 'Get More with Elemailer Pro ...' using CSS `content:`; contains a rule that paints `.select2-selection ul:not(.select2-selection__rendered)` red under a comment 'CSS for dark mode'. It depends on Elementor editor ids and classes.
- `integrations/elementor/assets/css/frontend.css` (Elementor preview): hides every add-section button except Elemailer's and the standard add-section button, and limits `#elementor-add-new-section` to 600px.
- `integrations/elementor/assets/css/elemailer-elementor-admin-style.css` (library modal, loaded by `Library::register_widget_styles()`): styles `#elemailer-lite-modal` (grid of template cards, header, toolbar, tooltips, notices, buttons, a `@media (max-width: 768px)` block at the end); uses the icon `public/assets/img/elemailer-icon.svg` through a relative path and sets `font-family: icomoon` for `[class*=elemailer-lite-icon-]` without an `@font-face` (see C38). It contains class names that look inherited from another template-library plugin (`ast-validate` in the JS, `elementor-add-ast-site-button` in the CSS).

## Notices (helpers/notice.php)
`Notice::push()` prints an admin notice (types, optional buttons, dismissible). Dismissal is a JS call to AJAX action `elemailer-notices`, handled by `Notice::dismiss()`, which stores either user meta (default) or a transient under the given id. The notice ids are prefixed `elemailer-sites-notice-id-` when pushed.

## Elementor integration (integrations/elementor)
- `Base::init()`: if Elementor is not loaded, shows an admin notice and stops. If Elementor Pro files exist but the plugin is inactive, shows a notice. Requires `ELEMENTOR_VERSION` >= 3.0.11 (the notices say 3.4). Then starts `Widgets\Base`, `Actions\Base`, enqueues editor/frontend CSS and JS for post types `em-form-template` and `em-emails-template`, shows a review-request notice, and prints footer branding on the Elementor canvas template for those post types.
- `Actions\Base::init()`: always calls `Hooks::trigger_elementor_free_actions()`; calls `Hooks::trigger_elementor_pro_actions()` only if `elementor-pro/elementor-pro.php` is an active plugin.
- `Actions\Hooks`: adds a 'Use Elemailer Lite' switcher and template select to the Elementor Pro Form widget Email section (`show_elemailer_email_template_selector`, `select_elemailer_email_template`) and Email 2 section (`..._2`); adds section background controls; sets the panel title; disables the Elementor container experiment and experiments list on Elemailer post types; registers `Void_Email` and `Void_Email2` as replacements for the Elementor Pro email actions (new registrar API if `Form_Actions_Registrar` exists, otherwise the old `elementor_pro/forms/form_submitted` hook).
- `Actions\Void_Email` extends `ElementorPro\Modules\Forms\Actions\Email`. When the switcher is 'yes' and a template is chosen, it uses the rendered template as the email body, forces HTML content type, wraps with `get_email_html_template()`, and sends with `wp_mail()` (BCC recipients are sent as separate mails). `Void_Email2` extends it for the Email 2 action: control ids get a `_2` suffix and it uses the `..._2` template keys.
- `actions/container.php`: a fake global `Container` class kept from version 1.6, marked obsolete since 1.8; its `require_once` is commented out in `actions/hooks.php`.
- `assets/js/editor.js` (loaded in the Elementor editor): fills the taxonomy, term and post dropdowns for the Latest Posts and Selected Posts widgets by calling the AJAX actions from `core/global.php` with `elemailer_lite.wpRestNonce`; hides unneeded column and section controls; changes the editor exit links to target `_top`. `assets/js/frontend.js` and `public/assets/js/functions.js` are placeholders.

### Widgets (integrations/elementor/widgets)
`Widgets\Base` lists 13 free widgets and filters the list through `elemailer_lite/onload/include_widgets`. On the two template post types it unregisters all other Elementor widgets except `common`, then registers these. Every widget has an 'Advanced Style' section (margin, padding, background color or image) and builds the same inline style string in `render()`.
- heading: an `h2` with optional link, size, line height, colour, alignment.
- image: media control, width %, alignment, optional custom URL (passed through `do_shortcode`); alt and title come from `Control_Media`.
- image-box: image plus title and description, image position left, top or right.
- video: a thumbnail linking to the video. Sources YouTube (thumbnail from `img.youtube.com`), Vimeo (thumbnail fetched at render time from `http://vimeo.com/api/v2/video/{id}.php` with `file_get_contents` and `unserialize`), Dailymotion (thumbnail by rewriting the URL) and self-hosted (chosen image). Play icons are `assets/img/*-play-icon.png` inside the widget folder.
- button: anchor only when the URL contains `http://` or `https://`, otherwise a `span`; markup is passed through `Util::kses()`.
- divider and spacer: simple styled blocks.
- text-editor: WYSIWYG content printed as is.
- social: repeater of 22 networks; icon images are `assets/social-icons/{style}{network}-48.png` inside the widget folder, referenced by the site's plugin URL.
- shortcode: label plus `do_shortcode()` of the text (the help text mentions Elementor Pro `[field id=...]` and Contact Form 7 tags).
- footer: address text; the Subscription section only shows an upsell note (unsubscribe is Pro).
- latest-posts: `WP_Query` by post type with optional taxonomy and terms (relation OR), posts per page, posts per row (1, 2, 3), thumbnail, title, excerpt, read more.
- selected-posts: renders specific post ids chosen in the widget (the taxonomy control is hidden and marked for future use); same layout options.

### Template library (library.php, import.php, public/views/templates.php, assets/js/elemailer-elementor-admin-page.js)
- `Library` loads the remote list from `https://lib.elemailer.com/wp-json/wp/v2/elemailerlib/` and local saved templates (meta `_elementor_template_type` = `elemailer_library`). Localized data is `elemailerElementorData` with `isPro` false, `license_status` false and upgrade links to elemailer.com. `Import` (extends Elementor `Source_Local`) processes the imported Elementor data and writes it into the target post.
- `templates.php` prints the Underscore/WP templates for the modal: list, search, preview and the Import button. Templates whose type is not `free` show a Premium ribbon and a 'Get Access' link instead of Import.
- Panel script (`ElemailerElementorAdmin`, implicit global; `$elementorscope` is also an implicit global): adds a folder button to the Elementor add-section area, opens the modal, lists and previews templates from `elemailerElementorData.default_page_builder`, handles search and the Free/Premium filter, and replaces Elementor's 'Save as template' dialog with an upsell message (`_restrictSaveTemplate`). The script is started by calling `ElemailerElementorAdmin.init()` at the end of the file.
- **Import flow (verified from the full script):** (1) the user previews a template (step 2) and `_requiredPluginsMarkup()` enables the Import button; for non-free templates `license_status` is false so the button is replaced by a 'Get Access' link in the template. (2) Import calls `_insert()` then `_enableImport()`, which POSTs AJAX action `elemailer-lite-remote-request` with `url` = the template's `elemailer-page-api-url` (built in PHP from `https://lib.elemailer.com/wp-json/wp/v2/elemailerlib/{id}`) and the nonce `_ajax_nonce`. (3) On success `insertDemo()` POSTs `elemailer-lite-elementor-batch-process` with `id` = `elementor.config.document.id` (the post being edited), `url` = the same api url, and the nonce. (4) The response data (Elementor elements) gets random ids and is inserted into the editor preview at the stored index with `elementor.getPreviewView().addChildModel()`, then Elementor's `template:after:insert` is triggered and the modal closes.
- Sync button: POSTs `elemailer-sync-page-builder` and `elemailer-saved-my-templates`.
- Not used by the Lite panel script: `_myTemplateDelete()` is defined but never bound to a click; the PHP actions `elemailer-lite-my-template-remote-request`, `elemailer-lite-elementor-batch-my-template-process` and `elemailer-lite-my-template-delete-request` are not called from this script. `_enableImport()` calls `createTemplate()` in a branch that cannot run (the action is always set to `insert` first), and no `createTemplate` function exists in the file.

## Shortcode integration (integrations/shortcode)
- `Base::init()` starts `Actions\Base`, which starts `Actions\Hooks`. `Actions\Hooks` hooks Contact Form 7 `wpcf7_before_send_mail`. In `mail` and `mail_2` bodies it finds `[elemailer:id="N"]`; if the post is published the whole body is replaced by the rendered email HTML (other text in the body is discarded); otherwise the body is left unchanged.

## Translations and WPML
`wpml-config.xml` lists translatable fields for `elemailer-*` widgets. It also contains entries for `elemailer-wc-*` widgets (billing address, description, footer, header, new account, order notes, shipping address) that are not part of the 13 Lite widgets. `languages/elemailer-lite.pot` is generated (header: Project-Id-Version Elemailer Lite 2.8, created 2025-08-05 by WP-CLI 2.12.0, text domain `elemailer-lite`) and holds source file and line references, so it changes with every code edit; a GitHub Action rebuilds it on pushes to `master`.

## Settings and options
`elemailer_lite_info`, `elemailer_hide_mail_branding`, `elemailer_lite_notice_never_show`, `elemailer_lite_notice_not_now_time`.

## Lite and Pro
The owner states Pro does not require Lite. The only Pro-related code seen in Lite is the guard on action `elemailer/after_load` in `elemailer-lite.php`, upgrade links, the `isPro` false flag and Premium ribbons in the library, the Pro upsell text injected by `editor.css`, WC widget entries in `wpml-config.xml`, and Pro-style dashboard rules in `admin-style.css`.

## Build and release files
`package.json` (scripts `start`, `build`, `i18n`), `Gruntfile.js`, `.distignore`, `.gitattributes`, `.gitignore`, `.github/workflows/main.yml` and `wporg.yml`, `.wordpress-org/` (WordPress.org banners, icons and 9 screenshots). Details in docs/release-process.md.

## Coverage
Read in full: elemailer-lite.php, plugin.php, autoloader.php, package.json, Gruntfile.js, readme.txt, .distignore, .gitignore, .gitattributes, wpml-config.xml, both existing workflows, every PHP file in app/, core/, traits/, helpers/ (util.php, notice.php) and integrations/ (all of integrations/elementor and integrations/shortcode, including all 13 widgets and widgets/base.php), public/views/{em-form-template-settings,template-create-modal,templates}.php, public/assets/js/admin/form-template-functions.js, public/assets/js/functions.js, integrations/elementor/assets/js/{editor,frontend}.js, integrations/elementor/assets/js/elemailer-elementor-admin-page.js (owner copy), public/assets/css/{elemailer-mail,style,admin-style}.css (admin-style from the owner copy), integrations/elementor/assets/css/{editor,frontend}.css, app/form-template/view/default-elementor-style.php, the root index.php stub, helpers/src/CssToInlineStyles.php and helpers/src/Css/{Processor.php, Property/Processor.php, Rule/Processor.php, Rule/Rule.php}, helpers/vendor/autoload.php and helpers/vendor/composer/{autoload_real,autoload_psr4,platform_check,installed.json}.

Read from the owner's copy through a text view and search passages: integrations/elementor/assets/css/elemailer-elementor-admin-style.css (33,462 characters). The passages cover the start, the middle and the end (final media query); short gaps between passages are marked in the tool output and may hold rules that were not seen.

Read only in part: languages/elemailer-lite.pot (about 25,000 of 40,533 characters; generated).

Not opened: helpers/src/Css/Property/Property.php (1,587 bytes); the `.wordpress-org/` images (binary files, only names and sizes listed); the symfony and tijsverkoyen source under helpers/vendor; widget asset folders (video and social icons; folder existence only); public/assets/{images,img}; and the 53-byte `index.php` stubs other than the root one. The root stub contains an ABSPATH guard and a comment; its Git blob id equals that of public/assets/css/index.php, so those two are identical. The others were only checked by size (all 53 bytes).

Tool limit to remember: any file larger than about 25,000 characters is cut off by the GitHub connector. Check the end of the output for a truncation marker, and ask the owner for the file when it appears.
