# Architecture (Elemailer Lite 2.8)

Written from reading the source. The Coverage section at the end lists what has and has not been reviewed.

## Boot sequence
1. `elemailer-lite.php` (plugin header: Version 2.8, text domain `elemailer-lite`) requires `autoloader.php` and `plugin.php`.
2. It registers activation and deactivation hooks on `Elemailer_Lite\Plugin::instance()`.
3. On `plugins_loaded` priority 150: if the action `elemailer/after_load` has NOT already fired, it runs `do_action('elemailer_lite/before_load')`, requires `core/global.php`, calls `Plugin::instance()->init()`, then `do_action('elemailer_lite/after_load')`. If that action has already fired, Lite does not initialize at all.
4. On `wp_loaded`, under the same guard: `Plugin::after_wp_loaded_hooks()` calls `remove_editor_styles()`.

`Plugin::__construct()` defines constants, runs `Autoloader::run()`, and hooks `load_textdomain` on `init`.

## Constants (plugin.php)
`ELE_MAILER_LITE_VERSION` ('2.8'), `ELE_MAILER_LITE_PACKAGE` ('free'), `ELE_MAILER_LITE_PLUGIN_URL`, `ELE_MAILER_LITE_PLUGIN_DIR`, `ELE_MAILER_LITE_PLUGIN_PUBLIC`, `ELE_MAILER_LITE_PLUGIN_PUBLIC_DIR`.

## Autoloader
`Autoloader::autoload()` ignores classes outside the `Elemailer_Lite` namespace. It strips the namespace, inserts a hyphen between a lowercase and uppercase letter, turns `_` into `-`, turns `\` into a directory separator, and lowercases. Example: `Elemailer_Lite\Integrations\Elementor\Widgets\Base` loads `integrations/elementor/widgets/base.php`. Widget classes are loaded with `require_once`, not the autoloader.

## Plugin::init()
- Admin menu `Elemailer` (slug `elemailer-menu`, capability `manage_options`, position 25) and submenu Settings (`em-form-template-settings`, renders `public/views/em-form-template-settings.php`). Registers setting `elemailer_hide_mail_branding` in group `em-form-template-settings-group`.
- Admin assets only on screen `edit-em-form-template`: `admin-style.css`, `form-template-functions.js`, localized as `elemailer_lite` with `restUrl` (`rest_url('elemailer_lite/v1/')`) and a `wp_rest` nonce.
- `wp_enqueue_scripts` priority 999: dequeues theme assets and non-allowlisted plugin assets on post type `em-form-template` (allowlist: this plugin, Elementor, Elementor Pro for scripts, Query Monitor, Debug Bar; Elementor Pro styles are dequeued).
- Shows a non-dismissible error notice on the list screen: 'You are able to create only 3 templates with elemailer lite'.
- Starts `Helpers\Notice`, `Integrations\Shortcode\Base`, `Integrations\Elementor\Base`, `Integrations\Elementor\Library`.
- Calls `Util::minify_css()` on every request to rewrite `public/assets/css/elemailer-mail.css` into `app/form-template/view/default-elementor-style.php` (file write on each page load).
- `App\Form_Template\Base` is initialized on `after_setup_theme`.
- Activation: flushes rewrite rules and stores option `elemailer_lite_info` (`activation_version`, `activation_time`, `old_user`). `old_user` is 1 if a previous info record had version 1.0.0 or old_user set.

## Templates (app/form-template)
- `Cpt`: post type `em-form-template`, rewrite slug `em-form-template`, public, `show_in_rest` false, capability type `page`, supports title, editor, elementor, permalink, shown under menu `elemailer-menu`. Allowed settings fields: title, subject, emailTo, emailFrom, emailReplyTo.
- `Base::init()`: creates `Cpt` and `Api`, prints the create modal (`public/views/template-create-modal.php`) on the list screen, adds a Shortcode column showing `[elemailer:id="ID"]`, and hooks `publish_em-form-template`.
- `Action`: `store()` requires `manage_options`; id `0` means insert. `insert()` allows creation only while the count of all `em-form-template` posts (all statuses, via `wp_count_posts`) is below 3, creates the post as `publish`, saves settings in post meta `elemailer__template_setting`, and sets `_wp_page_template` to `elementor_canvas`. `update()` updates the title and meta. `get_template_setting($id)` returns the meta plus post title. `get_all_template()` returns published templates as id => title for the Elementor form selector.
- Free limit enforcement is therefore in two places: `Action::insert()` (refuses creation at 3 or more posts of any status) and `Base::our_elemailer_lite_post_number_max()` (on publish, if more than 3 are published and the old status was not publish, sets the post to draft).
- `Api` (prefix `form-templates`, param `/(?P<id>\w+)`): `post_update()` calls `Action::store()`, `get_get()` calls `Action::get_template_setting()`.
- `Core\Api`: registers `elemailer_lite/v1/{prefix}` with route `/(?P<action>\w+)/{param}`, all HTTP methods, `permission_callback` `__return_true`. The callback runs the method named `{lowercase http method}_{action}`. Full URL shape: `/wp-json/elemailer_lite/v1/form-templates/update/{id}` (POST) and `/get/{id}` (GET).

## Email rendering (helpers/util.php)
- Requires `helpers/vendor/autoload.php` (CssToInlineStyles).
- `render_elementor_content()` uses Elementor `get_builder_content_for_display()`.
- `get_template_styles()` splits the CSS block from the output, rewrites `.elementor-element.` to `.`, removes media queries, returns a `<style>` block.
- `get_template_content()` returns the body without the style block.
- `get_email_html_template()` replaces `<section>` with `<div>`, wraps in an HTML document including `default-elementor-style.php` and template styles, adds footer branding when `elemailer_lite_info.old_user` is set and falsy and `elemailer_hide_mail_branding` is not 'yes', then inlines CSS.
- `kses()` helper with a custom allowlist.

## Elementor integration (integrations/elementor)
- `Base::init()`: if Elementor is not loaded, shows an admin notice and stops. If Elementor Pro files exist but the plugin is inactive, shows a notice. Requires `ELEMENTOR_VERSION` >= 3.0.11 (the notices say 3.4). Then starts `Widgets\Base`, `Actions\Base`, enqueues editor/frontend CSS and JS for post types `em-form-template` and `em-emails-template`, shows a review-request notice, and prints footer branding on the Elementor canvas template for those post types.
- `Widgets\Base`: 13 free widgets: heading, image, image-box, video, button, divider, spacer, text-editor, social, shortcode, latest-posts, selected-posts, footer. Filter `elemailer_lite/onload/include_widgets` can change the list. Category id `elemailer-template-builder-fields`. On the two template post types it unregisters all other Elementor widgets except `common`.
- `Actions\Hooks`: adds a 'Use Elemailer Lite' switcher and template select to the Elementor Pro Form widget Email section (`show_elemailer_email_template_selector`, `select_elemailer_email_template`) and Email 2 section (`..._2`); adds section background controls; sets the panel title; disables the Elementor container experiment and experiments list on Elemailer post types; registers `Void_Email` and `Void_Email2` as replacements for the Elementor Pro email actions (new registrar API if `Form_Actions_Registrar` exists, otherwise the old `elementor_pro/forms/form_submitted` hook).
- `Actions\Void_Email` extends `ElementorPro\Modules\Forms\Actions\Email`. When the switcher is 'yes' and a template is chosen, it uses the rendered template as the email body, forces HTML content type, wraps with `get_email_html_template()`, and sends with `wp_mail()` (BCC recipients are sent as separate mails).
- `Library`: template library inside the Elementor editor. Loads remote templates from `https://lib.elemailer.com/wp-json/wp/v2/elemailerlib/`, lists local saved templates (meta `_elementor_template_type` = `elemailer_library`), imports through `Import` (extends Elementor `Source_Local`). AJAX actions: `elemailer-lite-remote-request`, `-my-template-remote-request`, `-my-template-delete-request`, `-elementor-batch-process`, `-elementor-batch-my-template-process`, `elemailer-sync-page-builder`, `elemailer-saved-my-templates`. Localized data is `elemailerElementorData` with `isPro` false and upgrade links to elemailer.com.

## Shortcode integration (integrations/shortcode)
- `Base::init()` starts `Actions\Base`. `Actions\Hooks` hooks Contact Form 7 `wpcf7_before_send_mail`. In `mail` and `mail_2` bodies it finds `[elemailer:id="N"]`; if the post is published the whole body is replaced by the rendered email HTML (other text in the body is discarded); otherwise the body is left unchanged.

## Settings and options
`elemailer_lite_info`, `elemailer_hide_mail_branding`, `elemailer_lite_notice_never_show`, `elemailer_lite_notice_not_now_time`.

## Lite and Pro
The owner states Pro does not require Lite. The only Pro-related code seen in Lite is the guard on action `elemailer/after_load` in `elemailer-lite.php` and upgrade links and the `isPro` false flag in the library.

## Coverage
Read in full: elemailer-lite.php, plugin.php, autoloader.php, package.json, Gruntfile.js, readme.txt, .distignore, both workflows, app/form-template/{base,cpt,api,action}.php, core/{api,global}.php, helpers/util.php, integrations/elementor/{base,library,import}.php, integrations/elementor/actions/{hooks,void-email}.php, integrations/elementor/widgets/base.php, integrations/shortcode/{base,actions/hooks}.php.

Not yet reviewed (read before editing): core/cpt.php, core/taxonomy.php, helpers/notice.php, helpers/src, helpers/vendor, traits/singleton.php, integrations/elementor/actions/{base,container,void-email2}.php, integrations/shortcode/actions/base.php, each widget folder, public/views, public/assets, integrations/elementor/assets, app/form-template/view, lib, languages, wpml-config.xml, .gitattributes, .gitignore, .wordpress-org.
