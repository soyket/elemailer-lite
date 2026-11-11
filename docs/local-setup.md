# Local setup

## Requirements (from code and readme.txt)
- WordPress 5.8 or newer (tested up to 6.8.2 per readme)
- PHP 7.2.5 or newer in practice (readme.txt says 7.2, but the bundled Composer code refuses to run below 7.2.5)
- Elementor active, version 3.0.11 or newer (the plugin shows an error notice and does not load its Elementor features otherwise)
- Optional: Elementor Pro (Form widget email actions) and Contact Form 7 (shortcode mode)
- PHP extensions used by the CSS inliner: dom and libxml

## Steps
1. Create a local WordPress site with any local tool you use. Enable `WP_DEBUG` and `WP_DEBUG_LOG`.
2. Install and activate Elementor (and Elementor Pro / Contact Form 7 if you test those paths).
3. Put this repository in `wp-content/plugins/elemailer-lite` and activate it.
4. For developer tooling: `composer install` (PHPCS only; it creates a root `vendor/` folder that is Git-ignored).
5. Install an SMTP catcher (for example Mailpit or a mail-logging plugin) to inspect the emails.
6. Widgets load icon images and the Vimeo/YouTube thumbnails from outside or from the plugin URL, so test email images with a site URL that is reachable by the mail client.

The owner should add the exact local tool and PHP version used, because this is not stored in the repository.
