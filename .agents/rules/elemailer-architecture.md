# Elemailer Lite architecture rules

- Bootstrap: elemailer-lite.php -> plugin.php (`Elemailer_Lite\Plugin`, singleton) -> autoloader.php.
- Code areas: `app/form-template` (CPT and template logic), `integrations/elementor` and `integrations/shortcode`, `helpers/`, `traits/`, `public/` (assets, views), `lib/` (third-party, do not edit).
- The CPT `em-form-template` is registered on `after_setup_theme` (not earlier) to avoid textdomain-too-early notices. Textdomain loads on `init`. Do not move these without testing on the latest WordPress.
- `Plugin::enqueue_dequeue_js_css_public()` dequeues theme/plugin assets on template screens. Treat as fragile; test with multiple themes before changing.
- Lite and Pro are independent plugins. Never reference Pro files/classes, and never assume Lite is present from Pro-oriented code.
- Follow the existing namespace/autoloader conventions when adding classes.
