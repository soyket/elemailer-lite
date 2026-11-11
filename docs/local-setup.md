# Local setup

## Requirements (from code and readme.txt)
- WordPress 5.8 or newer (tested up to 6.8.2 per readme)
- PHP 7.2 or newer
- Elementor active, version 3.0.11 or newer (the plugin shows an error notice and does not load its Elementor features otherwise)
- Optional: Elementor Pro (Form widget email actions) and Contact Form 7 (shortcode mode)

## Steps
1. Create a local WordPress site with any local tool you use. Enable `WP_DEBUG` and `WP_DEBUG_LOG`.
2. Install and activate Elementor (and Elementor Pro / Contact Form 7 if you test those paths).
3. Put this repository in `wp-content/plugins/elemailer-lite` and activate it.
4. For developer tooling: `composer install` (PHPCS only).
5. Install an SMTP catcher (for example Mailpit or a mail-logging plugin) to inspect the emails.

The owner should add the exact local tool and PHP version used, because this is not stored in the repository.
