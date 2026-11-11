# Architecture (Elemailer Lite)

> Draft generated from reading plugin.php, package.json and the directory layout. Verify and extend when working in each area.

## Bootstrap
`elemailer-lite.php` loads `plugin.php`. `Elemailer_Lite\Plugin` is a final singleton: its constructor defines constants, runs `Autoloader::run()` and hooks `load_textdomain` on `init`. `init()` wires admin menu/settings (for `manage_options`), asset hooks, notices, and integrations.

## Constants
`ELE_MAILER_LITE_VERSION`, `_PACKAGE` (`free`), `_PLUGIN_URL`, `_PLUGIN_DIR`, `_PLUGIN_PUBLIC`, `_PLUGIN_PUBLIC_DIR`.

## Modules
- `app/form-template`: CPT `em-form-template` (Base, Cpt) and email template view code (including `view/default-elementor-style.php`).
- `integrations/elementor`: Base and Library integration with Elementor.
- `integrations/shortcode`: shortcode support in mail body.
- `helpers/`: Notice, Util (includes CSS minify into `public/assets/css/elemailer-mail.css` for email clients).
- `traits/`, `public/` (assets, views, settings page), `languages/`, `lib/` (third-party).

## Key behavior
- Free limit: only 3 templates; notice shown on the `edit-em-form-template` screen.
- Admin assets only on that screen; REST namespace `elemailer_lite/v1` with `wp_rest` nonce.
- On template screens, theme and non-allowlisted plugin scripts/styles are dequeued (allowlist: this plugin, Elementor, Elementor Pro, Query Monitor, Debug Bar).
- Activation flushes rewrites and stores `elemailer_lite_info` option (activation version/time, old_user flag).
- Setting: `elemailer_hide_mail_branding`.

## Lite and Pro
Independent plugins. Pro does not require Lite; no shared-file or compatibility contract exists.

## Fragile areas
Asset dequeue logic, CPT registration timing, email-client CSS output.
