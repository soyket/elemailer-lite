<?php
/**
 * Smoke test for the wp-env WordPress environment.
 *
 * Run with: npm run test:smoke
 */

$failures = 0;

function elemailer_smoke_check($label, $condition) {
	global $failures;

	if ($condition) {
		echo "PASS: {$label}\n";
		return;
	}

	$failures++;
	echo "FAIL: {$label}\n";
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

elemailer_smoke_check('Elemailer Lite active', is_plugin_active('elemailer-lite/elemailer-lite.php'));
elemailer_smoke_check('Elementor active', is_plugin_active('elementor/elementor.php'));
elemailer_smoke_check('Contact Form 7 active', is_plugin_active('contact-form-7/wp-contact-form-7.php'));
elemailer_smoke_check('Template post type registered', post_type_exists('em-form-template'));
elemailer_smoke_check('CF7 mail hook registered', has_action('wpcf7_before_send_mail') !== false);

foreach (get_posts(['post_type' => 'em-form-template', 'post_status' => 'any', 'numberposts' => -1]) as $old) {
	wp_delete_post($old->ID, true);
}

$elementor_data = [
	[
		'id' => 'sec0001',
		'elType' => 'section',
		'settings' => [],
		'elements' => [
			[
				'id' => 'col0001',
				'elType' => 'column',
				'settings' => ['_column_size' => 100],
				'elements' => [
					['id' => 'wid0001', 'elType' => 'widget', 'widgetType' => 'elemailer-heading', 'settings' => ['title' => 'Hello [your-name]'], 'elements' => []],
					['id' => 'wid0002', 'elType' => 'widget', 'widgetType' => 'elemailer-text-editor', 'settings' => ['text_editor' => '<p>Email: [your-email]</p><p>Message: [your-message]</p>'], 'elements' => []],
				],
			],
		],
	],
];

$template_id = wp_insert_post([
	'post_type' => 'em-form-template',
	'post_status' => 'publish',
	'post_title' => 'Smoke Test Template',
]);

elemailer_smoke_check('Template created', !is_wp_error($template_id) && $template_id > 0);

if (!is_wp_error($template_id) && $template_id > 0) {
	update_post_meta($template_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
	update_post_meta($template_id, '_elementor_edit_mode', 'builder');
	update_post_meta($template_id, '_elementor_template_type', 'wp-page');

	$content = \Elemailer_Lite\Helpers\Util::get_template_content($template_id);
	elemailer_smoke_check('Template content renders heading text', strpos($content, 'Hello [your-name]') !== false);

	$email_html = \Elemailer_Lite\Helpers\Util::get_email_html_template($template_id, $content);
	elemailer_smoke_check('Email HTML has doctype', stripos($email_html, '<!doctype html>') !== false);
	elemailer_smoke_check('Email HTML converts sections to divs', strpos($email_html, '<section') === false);

	$hooks = \Elemailer_Lite\Integrations\Shortcode\Actions\Hooks::instance();
	$expanded = $hooks->process_elemailer_shortcode('[elemailer:id="' . $template_id . '"]');
	elemailer_smoke_check('Elemailer shortcode expands to email HTML', stripos($expanded, '<!doctype html>') !== false);
}

$captured_mail = null;
add_filter('pre_wp_mail', function ($null, $atts) use (&$captured_mail) {
	$captured_mail = $atts;
	return true;
}, 10, 2);

wp_mail('smoke@example.com', 'Elemailer smoke test', 'Smoke test body');
elemailer_smoke_check('Mail capture intercepts wp_mail', is_array($captured_mail) && 'Elemailer smoke test' === $captured_mail['subject']);

if ($failures > 0) {
	echo "Smoke test failed: {$failures} check(s) failed.\n";
	exit(1);
}

echo "Smoke test passed.\n";
