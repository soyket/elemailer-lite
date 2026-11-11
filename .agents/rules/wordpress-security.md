# WordPress security rules

- Start every PHP file with `defined( 'ABSPATH' ) || exit;`.
- Sanitize all input (`sanitize_text_field`, `absint`, `wp_kses_post`, etc.) and escape all output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- Verify nonces on every state-changing form/AJAX/REST request. REST routes need a `permission_callback` that checks capabilities.
- Check capabilities (e.g. `manage_options`) before privileged actions.
- Use `$wpdb->prepare()` for all dynamic SQL.
- Email content: never trust template/shortcode input; do not log recipient data, tokens, or full message bodies.
- Remote requests must set timeouts and handle `WP_Error`.
