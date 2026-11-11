# Testing rules

There is no automated test suite in this repository yet (no `tests/` directory, no PHPUnit config). Until one exists, testing is syntax lint plus manual checks.

- Run `composer lint:syntax` before every commit.
- Run `composer lint:phpcs` and fix issues on lines you changed. Do not reformat unrelated code.
- Manual smoke test for any change (WP_DEBUG and WP_DEBUG_LOG on, Elementor active):
  1. Elemailer > Form Templates: create a template through the modal, edit it in Elementor, confirm only Elemailer widgets appear.
  2. Confirm creating a 4th template is refused.
  3. Copy the shortcode `[elemailer:id="ID"]` from the list table.
  4. With Elementor Pro: add a Form widget, choose the template under Email and Email 2, submit, check the received email.
  5. With Contact Form 7: paste the shortcode as the mail body, submit, check the received email.
  6. Check `wp-content/debug.log` for new notices.
- Email rendering changes must be checked in at least Gmail web and one other client. Preview in the editor is not proof.
- Logic that can run without WordPress (string and CSS processing in `Helpers\Util`) is a good first target for PHPUnit tests.
- State exactly what you ran and the result in the pull request.
