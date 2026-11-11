# Backlog

Suggested order. Each item should be its own branch and pull request.

- [ ] Fix known issue 1: restrict `my_template_delete_request` to Elemailer library templates and check `delete_post`
- [ ] Fix known issues 2 and 3: validate URL host and post type in library AJAX handlers
- [ ] Fix known issue 7: add a nonce, capability check and id allowlist to `Notice::dismiss()`
- [ ] Fix known issue 4: add capability and post-type checks to `get_template_setting` / REST read (first fix the nonce source in `form-template-functions.js`)
- [ ] Fix known issue 6: add nonce and capability checks to the notice dismiss handler in `Elementor\Base`
- [ ] Known issue 5: remove the `wp_ajax_nopriv_` registrations in `core/global.php` and test the Latest Posts and Selected Posts widget dropdowns
- [ ] Sanitize template settings values in `Action::sanitize()`
- [ ] Stop writing the CSS file on every request in `Plugin::init()` (and decide whether `default-elementor-style.php` stays tracked)
- [ ] Cache the remote library list
- [ ] Review the files listed as not reviewed in docs/architecture.md (12 widgets, templates.php, admin-page JS, CSS)
- [ ] Add PHPUnit tests for `Helpers\Util` string and CSS functions
- [ ] Fix `.distignore` entry `.Gruntfile.js` and stale Grunt asset paths
- [ ] Fill the exact local environment into docs/local-setup.md
