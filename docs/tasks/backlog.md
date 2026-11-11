# Backlog

Suggested order. Each item should be its own branch and pull request.

- [ ] Fix known issue 1: restrict `my_template_delete_request` to Elemailer library templates and check `delete_post`
- [ ] Fix known issues 2 and 3: validate URL host and post type in library AJAX handlers
- [ ] Fix known issue 4: add capability and post-type checks to `get_template_setting` / REST read
- [ ] Fix known issue 6: add nonce and capability checks to the notice dismiss handler
- [ ] Sanitize template settings values in `Action::sanitize()`
- [ ] Stop writing the CSS file on every request in `Plugin::init()`
- [ ] Cache the remote library list
- [ ] Review the files listed as not yet reviewed in docs/architecture.md
- [ ] Add PHPUnit tests for `Helpers\Util` string and CSS functions
- [ ] Fix `.distignore` entry `.Gruntfile.js` and stale Grunt asset paths
- [ ] Fill the exact local environment into docs/local-setup.md
