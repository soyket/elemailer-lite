# Local setup

## Reproducible test environment

Use `wp-env` for the standard test environment. It reads `.wp-env.json` from the repository root.

Prerequisites:
- Node.js and npm
- Docker
- PHP and Composer for unit tests and PHPCS

The environment pins WordPress 6.8.2, PHP 8.3, Elementor 4.4.2, and Contact Form 7 6.2.6. `WP_DEBUG`, `WP_DEBUG_LOG`, and `SCRIPT_DEBUG` are enabled.

Commands:

```bash
npm install
composer install
npm run env:start
composer test:unit
npm run test:smoke
npm run env:stop
```

Use `npm run env:destroy` when you want to delete the wp-env containers and volumes and start again from a clean site.

## Requirements (from code and readme.txt)
- WordPress 5.8 or newer (tested up to 6.8.2 per readme)
- PHP 7.2.5 or newer in practice (readme.txt says 7.2, but the bundled Composer code refuses to run below 7.2.5)
- Elementor active, version 3.0.11 or newer (the plugin shows an error notice and does not load its Elementor features otherwise)
- Optional: Elementor Pro (Form widget email actions) and Contact Form 7 (shortcode mode)
- PHP extensions used by the CSS inliner: dom and libxml

## Manual setup fallback

If Docker is not available, create a local WordPress site with any local tool you use. Enable `WP_DEBUG` and `WP_DEBUG_LOG`, install and activate Elementor (and Elementor Pro / Contact Form 7 if you test those paths), put this repository in `wp-content/plugins/elemailer-lite`, and activate it. Install an SMTP catcher (for example Mailpit or a mail-logging plugin) to inspect emails.

Widgets load icon images and the Vimeo/YouTube thumbnails from outside or from the plugin URL, so test email images with a site URL that is reachable by the mail client.
