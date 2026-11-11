# Security rules for this repository

These rules come from patterns actually present in the code (see docs/known-issues.md). Follow them in all new and changed code.

## REST API
- `Core\Api::init()` registers every route with `'permission_callback' => '__return_true'`, so routes are public by default. Authorization must be done inside each action method with `current_user_can()`.
- Every action method, including read-only `get_*` methods, must check a capability that matches the data it returns.
- If you add or change route registration, prefer a real `permission_callback` and keep the existing `elemailer_lite/v1/form-templates/{action}/{id}` URL working.
- Validate that an ID refers to the expected post type (`em-form-template`) before reading or writing it.

## AJAX
- The three handlers in `core/global.php` are registered with `wp_ajax_nopriv_` and only check a `wp_rest` nonce. Do not add more public handlers. If a handler is not meant for logged-out users, register only `wp_ajax_`.
- Library handlers in `integrations/elementor/library.php` use `check_ajax_referer( 'elemailer-lite-check', '_ajax_nonce' )` plus `current_user_can( 'edit_posts' )`. For any action that deletes, reads post meta, or fetches a URL, `edit_posts` is not enough: check the specific post (`current_user_can( 'delete_post', $id )` or `edit_post`) and its post type.
- A nonce proves intent, not permission. Always add a capability check.

## Input and output
- `Action::sanitize()` only filters which keys are accepted. It does not sanitize values. Sanitize each value (`sanitize_text_field`, `sanitize_email`, `wp_kses_post`) before saving, and escape on output.
- Never read `$_GET`, `$_POST`, or `$_REQUEST` without `wp_unslash()` and a sanitizer.
- Do not change state from a GET request without a nonce and a capability check.
- Use `$wpdb->prepare()` for dynamic SQL (the current code does not run custom SQL).

## Outbound requests
- The library code calls `wp_remote_get()` with URLs that come from the request. For any URL from user input use `wp_safe_remote_get()`, restrict hosts to `lib.elemailer.com` or `elemailer.com`, set a `timeout`, and handle `WP_Error`.

## Email content
- Do not log recipient addresses, form values, or rendered email bodies.
- Rendered templates pass through `Util::get_email_html_template()` and a CSS inliner. Keep user-controlled text out of the HTML unescaped.
