# Testing rules

There is no automated test suite in this repository yet (no `tests/` directory, no PHPUnit config). Until one exists, testing is syntax lint plus manual checks.

- Run `composer lint:syntax` before every commit.
- Run `composer lint:phpcs` and fix issues on lines you changed. Do not reformat unrelated code.
- Manual smoke test for any change (WP_DEBUG and WP_DEBUG_LOG on, Elementor active):
  1. Elemailer > Form Templates: create a template through the modal (the modal has a single Template Name field), edit it in Elementor, confirm only Elemailer widgets appear.
  2. Reopen the template from the list (Edit link) and confirm the modal loads its name.
  3. Confirm creating a 4th template is refused.
  4. Copy the shortcode `[elemailer:id="ID"]` from the list table.
  5. With Elementor Pro: add a Form widget, choose the template under Email and Email 2, submit, check the received email.
  6. With Contact Form 7: paste the shortcode as the mail body, submit, check the received email.
  7. In the editor, add Latest Posts and Selected Posts widgets and confirm the taxonomy and term dropdowns fill in.
  8. Check `wp-content/debug.log` for new notices.
- The plugin rewrites `app/form-template/view/default-elementor-style.php` on page loads. If Git shows it modified and you did not change `public/assets/css/elemailer-mail.css`, do not commit it.
- Email rendering changes must be checked in at least Gmail web and one other client. Preview in the editor is not proof.
- Logic that can run without WordPress (string and CSS processing in `Helpers\Util`) is a good first target for PHPUnit tests.
- State exactly what you ran and the result in the pull request.
