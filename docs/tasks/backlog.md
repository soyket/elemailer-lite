# Backlog

The detailed, file-by-file change list is docs/core-changes-needed.md (ids C1 to C31). This backlog is the suggested order. Each item should be its own branch and pull request.

## Suggested order
- [ ] C1 library delete handler (high)
- [ ] C2, C3 library URL and post checks
- [ ] C5 notice dismiss handler
- [ ] C4 REST read check, together with the nonce fix in `form-template-functions.js`
- [ ] C6 notice option updates from `$_GET`
- [ ] C7 remove `nopriv` AJAX registrations
- [ ] C9 Vimeo thumbnail (https, no unserialize, cache)
- [ ] C8 sanitize template settings
- [ ] C10 stop rewriting the CSS file on every request
- [ ] C11 cache the remote library list
- [ ] C20 decide PHP minimum (7.2.5)
- [ ] C12 to C19 small correctness and escaping fixes
- [ ] C21 to C23 compatibility and modal fields
- [ ] C24 to C27 remove dead code after a reference search
- [ ] C28 to C31 release tooling clean-up

## Other
- [ ] Add PHPUnit tests for `Helpers\Util` string and CSS functions
- [ ] Read the rest of `elemailer-elementor-admin-page.js` and the CSS files that docs/architecture.md lists as not opened
- [ ] Fill the exact local environment into docs/local-setup.md
