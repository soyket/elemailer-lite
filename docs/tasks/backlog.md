# Backlog

The detailed, file-by-file change list is docs/core-changes-needed.md (ids C1 to C38). This backlog is the suggested order. Each item should be its own branch and pull request.

## Suggested order
- [ ] C1 library delete handler (high), with the owner decision on removing it
- [ ] C2, C3 library URL and post checks (the import flow was verified, so these are safe to implement)
- [ ] C5 notice dismiss handler
- [ ] C4 REST read check, together with the nonce fix in `form-template-functions.js`
- [ ] C6 notice option updates from `$_GET`
- [ ] C7 remove `nopriv` AJAX registrations
- [ ] C9 Vimeo thumbnail (https, no unserialize, cache)
- [ ] C8 sanitize template settings
- [ ] C10 stop rewriting the CSS file on every request (keep the generated file without a closing style tag)
- [ ] C11 cache the remote library list
- [ ] C20 decide PHP minimum (7.2.5)
- [ ] C12 to C19 small correctness and escaping fixes
- [ ] C21 to C23 compatibility and modal fields
- [ ] C24 to C27 remove dead code after a reference search
- [ ] C29 to C31 release tooling clean-up
- [ ] C33 library panel script clean-up
- [ ] C34 to C36, C38 stylesheet and icon clean-up

## Done
- [x] C32 read the full library panel script (2026-11-11)
- [x] C28 .distignore typo fixed; Gruntfile.js, package.json, package-lock.json and README.md excluded from the WordPress.org package (2026-11-27)

## Review gaps
- [ ] C37 remaining unread: the tail of languages/elemailer-lite.pot (generated), helpers/src/Css/Property/Property.php, the .wordpress-org images

## Other
- [x] Add PHPUnit tests for `Helpers\Util` string and CSS functions (2026-11-28)
- [x] Fill the exact local environment into docs/local-setup.md (2026-11-28)
