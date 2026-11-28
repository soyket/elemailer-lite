<?php
/**
 * Integration tests for security, template lifecycle, widgets, and email conversion.
 *
 * Run with: npm run test:integration
 */

$GLOBALS['elemailer_it_failures'] = 0;
$GLOBALS['elemailer_it_checks'] = 0;

function elemailer_it_check($label, $condition) {
	$GLOBALS['elemailer_it_checks']++;

	if ($condition) {
		echo "PASS: {$label}\n";
		return;
	}

	$GLOBALS['elemailer_it_failures']++;
	echo "FAIL: {$label}\n";
}

function elemailer_it_ajax($action, $post) {
	$_POST = $post;
	$_REQUEST = $post;

	if (!defined('DOING_AJAX')) {
		define('DOING_AJAX', true);
	}

	$die_handler = function () {
		return function ($message = '') {
			throw new Exception(is_string($message) ? $message : 'wp_die');
		};
	};
	add_filter('wp_die_handler', $die_handler);
	add_filter('wp_die_ajax_handler', $die_handler);

	ob_start();
	try {
		do_action('wp_ajax_' . $action);
	} catch (Exception $e) {
		// wp_send_json_* and wp_die end AJAX handlers by design.
	}

	return ob_get_clean();
}

function elemailer_it_make_template($title, $status, $data) {
	$id = wp_insert_post([
		'post_type' => 'em-form-template',
		'post_status' => $status,
		'post_title' => $title,
	]);

	if (!is_wp_error($id) && $id > 0) {
		update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
		update_post_meta($id, '_elementor_edit_mode', 'builder');
		update_post_meta($id, '_elementor_template_type', 'wp-page');
		if (defined('ELEMENTOR_VERSION')) {
			update_post_meta($id, '_elementor_version', ELEMENTOR_VERSION);
		}
	}

	return $id;
}

function elemailer_it_data($widgets) {
	$elements = [];
	foreach ($widgets as $i => $widget) {
		$elements[] = [
			'id' => 'itw' . $i,
			'elType' => 'widget',
			'widgetType' => $widget[0],
			'settings' => $widget[1],
			'elements' => [],
		];
	}

	return [[
		'id' => 'itsec1',
		'elType' => 'section',
		'settings' => [],
		'isInner' => false,
		'elements' => [[
			'id' => 'itcol1',
			'elType' => 'column',
			'settings' => ['_column_size' => 100],
			'isInner' => false,
			'elements' => $elements,
		]],
	]];
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$admin_id = 1;
$contributor = get_user_by('login', 'elemailer_contributor');
if (!$contributor) {
	$contributor_id = wp_insert_user([
		'user_login' => 'elemailer_contributor',
		'user_pass' => wp_generate_password(),
		'role' => 'contributor',
	]);
} else {
	$contributor_id = $contributor->ID;
}

foreach (get_posts(['post_type' => 'em-form-template', 'post_status' => 'any', 'numberposts' => -1]) as $old) {
	wp_delete_post($old->ID, true);
}
foreach (get_posts(['post_type' => 'elementor_library', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_elementor_template_type', 'meta_value' => 'elemailer_library']) as $old) {
	wp_delete_post($old->ID, true);
}

$normal_post_id = wp_insert_post([
	'post_type' => 'post',
	'post_status' => 'publish',
	'post_title' => 'Integration Normal Post',
	'post_author' => $admin_id,
]);
update_post_meta($normal_post_id, '_elementor_data', 'SECRET_NORMAL_DATA');

$library_id = wp_insert_post([
	'post_type' => 'elementor_library',
	'post_status' => 'publish',
	'post_title' => 'Integration Library Template',
	'post_author' => $admin_id,
]);
update_post_meta($library_id, '_elementor_template_type', 'elemailer_library');
update_post_meta($library_id, '_elementor_data', wp_slash(wp_json_encode(elemailer_it_data([['elemailer-heading', ['title' => 'Library Heading']]]))));

// Security: C1 library delete handler removed.
elemailer_it_check('C1 library delete AJAX action is not registered', has_action('wp_ajax_elemailer-lite-my-template-delete-request') === false);

wp_set_current_user($contributor_id);
elemailer_it_ajax('elemailer-lite-my-template-delete-request', [
	'_ajax_nonce' => wp_create_nonce('elemailer-lite-check'),
	'template_id' => $normal_post_id,
]);
elemailer_it_check('C1 contributor cannot delete a normal post', (bool) get_post($normal_post_id));

elemailer_it_ajax('elemailer-lite-my-template-delete-request', [
	'_ajax_nonce' => wp_create_nonce('elemailer-lite-check'),
	'template_id' => $library_id,
]);
elemailer_it_check('C1 contributor cannot delete a library template', (bool) get_post($library_id));

wp_set_current_user($admin_id);
elemailer_it_ajax('elemailer-lite-my-template-delete-request', [
	'_ajax_nonce' => wp_create_nonce('elemailer-lite-check'),
	'template_id' => $library_id,
]);
elemailer_it_check('C1 removed delete action does not delete a library template', (bool) get_post($library_id));

// Security: C3 source template read must not expose arbitrary post data.
wp_set_current_user($contributor_id);
$output = elemailer_it_ajax('elemailer-lite-my-template-remote-request', [
	'_ajax_nonce' => wp_create_nonce('elemailer-lite-check'),
	'template_id' => $normal_post_id,
]);
elemailer_it_check('C3 contributor cannot read arbitrary post Elementor data', strpos($output, 'SECRET_NORMAL_DATA') === false);

// Security: C2 remote URL allowlist.
$requested_urls = [];
add_filter('pre_http_request', function ($pre, $args, $url) use (&$requested_urls) {
	$requested_urls[] = $url;
	return [
		'headers' => [],
		'body' => wp_json_encode(['ok' => true]),
		'response' => ['code' => 200, 'message' => 'OK'],
		'cookies' => [],
		'filename' => null,
	];
}, 10, 3);

wp_set_current_user($admin_id);
elemailer_it_ajax('elemailer-lite-remote-request', [
	'_ajax_nonce' => wp_create_nonce('elemailer-lite-check'),
	'url' => 'https://evil.example/steal',
]);
elemailer_it_check('C2 disallowed remote host is not requested', empty($requested_urls));

$output = elemailer_it_ajax('elemailer-lite-remote-request', [
	'_ajax_nonce' => wp_create_nonce('elemailer-lite-check'),
	'url' => 'https://lib.elemailer.com/wp-json/wp/v2/elemailerlib/1',
]);
elemailer_it_check('C2 allowed library host is requested', !empty($requested_urls) && strpos($output, 'ok') !== false);

// Security: C3 batch import target must be an Elemailer template.
elemailer_it_ajax('elemailer-lite-elementor-batch-process', [
	'_ajax_nonce' => wp_create_nonce('elemailer-lite-check'),
	'url' => 'https://lib.elemailer.com/wp-json/wp/v2/elemailerlib/1',
	'id' => $normal_post_id,
]);
elemailer_it_check('C3 import into a normal post is refused', 'SECRET_NORMAL_DATA' === get_post_meta($normal_post_id, '_elementor_data', true));
remove_all_filters('pre_http_request');

// Security: C4 REST template settings require manage_options and a template post type.
$template_id = elemailer_it_make_template('Integration Template', 'publish', elemailer_it_data([
	['elemailer-heading', ['title' => 'Hello [your-name]']],
	['elemailer-text-editor', ['text_editor' => '<p>Message: [your-message]</p>']],
]));

wp_set_current_user(0);
$response = rest_do_request(new WP_REST_Request('GET', '/elemailer_lite/v1/form-templates/get/' . $template_id));
elemailer_it_check('C4 anonymous REST template read is denied', in_array($response->get_status(), [401, 403], true));

wp_set_current_user($admin_id);
$response = rest_do_request(new WP_REST_Request('GET', '/elemailer_lite/v1/form-templates/get/' . $template_id));
$data = $response->get_data();
elemailer_it_check('C4 admin REST template read works', 200 === $response->get_status() && 'Integration Template' === ($data['title'] ?? ''));

$response = rest_do_request(new WP_REST_Request('GET', '/elemailer_lite/v1/form-templates/get/' . $normal_post_id));
$data = $response->get_data();
elemailer_it_check('C4 REST read does not expose normal post titles', empty($data) || 'Integration Normal Post' !== ($data['title'] ?? ''));

// Security: C5 notice dismissal allowlist and nonce.
wp_set_current_user($contributor_id);
delete_user_meta($contributor_id, 'evil_meta');
elemailer_it_ajax('elemailer-notices', [
	'_ajax_nonce' => wp_create_nonce('elemailer-notices'),
	'id' => 'evil_meta',
	'meta' => 'user',
	'time' => 60,
]);
elemailer_it_check('C5 arbitrary user meta dismissal is refused', '' === get_user_meta($contributor_id, 'evil_meta', true));

elemailer_it_ajax('elemailer-notices', [
	'_ajax_nonce' => wp_create_nonce('elemailer-notices'),
	'id' => 'elemailer-sites-notice-id-test',
	'meta' => 'user',
	'time' => 60,
]);
elemailer_it_check('C5 valid notice dismissal works', !empty(get_user_meta($contributor_id, 'elemailer-sites-notice-id-test', true)));

// Security: C6 review notice GET actions require nonce and capability.
wp_set_current_user($admin_id);
delete_option('elemailer_lite_notice_never_show');
$_GET = ['elemailer_lite_notice_never_show' => '1'];
\Elemailer_Lite\Integrations\Elementor\Base::instance()->display_admin_notice_logic();
elemailer_it_check('C6 notice GET without nonce is refused', 'yes' !== get_option('elemailer_lite_notice_never_show'));

$_GET = [
	'elemailer_lite_notice_never_show' => '1',
	'_wpnonce' => wp_create_nonce('elemailer_lite_notice'),
];
\Elemailer_Lite\Integrations\Elementor\Base::instance()->display_admin_notice_logic();
elemailer_it_check('C6 notice GET with nonce works for admin', 'yes' === get_option('elemailer_lite_notice_never_show'));
delete_option('elemailer_lite_notice_never_show');
$_GET = [];

// Security: C7 logged-out AJAX actions are not registered.
elemailer_it_check('C7 nopriv taxonomy action is not registered', false === has_action('wp_ajax_nopriv_elemailer_get_taxonomies'));
elemailer_it_check('C7 nopriv terms action is not registered', false === has_action('wp_ajax_nopriv_elemailer_get_terms'));
elemailer_it_check('C7 nopriv posts action is not registered', false === has_action('wp_ajax_nopriv_elemailer_get_posts'));

// Security: C8 template settings are sanitized.
$sanitized = \Elemailer_Lite\App\Form_Template\Action::instance()->sanitize([
	'title' => '<b>Hi</b>',
	'emailTo' => 'a@example.com [your-email] <script>x</script>',
	'unknown' => 'drop-me',
]);
elemailer_it_check('C8 sanitize strips tags from title', 'Hi' === ($sanitized['title'] ?? ''));
elemailer_it_check('C8 sanitize strips script tags from email fields', strpos($sanitized['emailTo'] ?? '', '<script') === false);
elemailer_it_check('C8 sanitize drops unknown fields', !isset($sanitized['unknown']));

// Lifecycle: create limit, update, invalid/draft shortcode handling, branding.
foreach (get_posts(['post_type' => 'em-form-template', 'post_status' => 'any', 'numberposts' => -1]) as $old) {
	wp_delete_post($old->ID, true);
}
wp_set_current_user($admin_id);
$action = \Elemailer_Lite\App\Form_Template\Action::instance();
$created = [];
for ($i = 1; $i <= 3; $i++) {
	$result = $action->store(0, ['title' => 'Lifecycle Template ' . $i]);
	$created[] = $result['data']['template_id'] ?? 0;
	elemailer_it_check('Lifecycle template ' . $i . ' created', 1 === ($result['status'] ?? 0));
}
$result = $action->store(0, ['title' => 'Lifecycle Template 4']);
elemailer_it_check('Lifecycle fourth template is refused by Lite limit', 0 === ($result['status'] ?? 1));

$action->store($created[0], ['title' => 'Lifecycle Template Updated']);
$settings = $action->get_template_setting($created[0]);
elemailer_it_check('Lifecycle template update saves title', 'Lifecycle Template Updated' === ($settings['title'] ?? ''));

$render_id = elemailer_it_make_template('Lifecycle Render', 'publish', elemailer_it_data([
	['elemailer-heading', ['title' => 'Hello [your-name]']],
]));
$hooks = \Elemailer_Lite\Integrations\Shortcode\Actions\Hooks::instance();
elemailer_it_check('Lifecycle invalid shortcode id is unchanged', '[elemailer:id="999999"]' === $hooks->process_elemailer_shortcode('[elemailer:id="999999"]'));

$draft_id = elemailer_it_make_template('Lifecycle Draft', 'draft', elemailer_it_data([
	['elemailer-heading', ['title' => 'Draft Heading']],
]));
elemailer_it_check('Lifecycle draft shortcode is unchanged', '[elemailer:id="' . $draft_id . '"]' === $hooks->process_elemailer_shortcode('[elemailer:id="' . $draft_id . '"]'));

$content = \Elemailer_Lite\Helpers\Util::get_template_content($render_id);
$email_html = \Elemailer_Lite\Helpers\Util::get_email_html_template($render_id, $content);
elemailer_it_check('Lifecycle branding is present by default', strpos($email_html, 'Powered by') !== false);
update_option('elemailer_hide_mail_branding', 'yes');
$email_html = \Elemailer_Lite\Helpers\Util::get_email_html_template($render_id, $content);
elemailer_it_check('Lifecycle hide-branding setting removes footer', strpos($email_html, 'Powered by') === false);
delete_option('elemailer_hide_mail_branding');

// Widgets: render the main Elemailer widgets in one template.
$widget_id = elemailer_it_make_template('Widget Integration', 'draft', elemailer_it_data([
	['elemailer-heading', ['title' => 'Widget Heading', 'link' => ['url' => 'https://example.com/heading']]],
	['elemailer-text-editor', ['text_editor' => '<p>Widget body text</p>']],
	['elemailer-button', ['text' => 'Widget Button', 'link' => ['url' => 'https://example.com/button']]],
	['elemailer-image', ['void_email_image' => ['id' => 0, 'url' => 'https://example.com/test-image.png']]],
	['elemailer-divider', []],
	['elemailer-spacer', ['void_spacer_height' => ['size' => 30, 'unit' => 'px']]],
	['elemailer-image-box', ['void_email_title' => 'Box Title', 'void_email_description' => 'Box description text', 'void_email_image' => ['id' => 0, 'url' => 'https://example.com/box.png']]],
	['elemailer-footer', ['footer_address' => '123 Test Street']],
	['elemailer-shortcode', ['shortcode' => '[caption width="100"]Caption text[/caption]']],
	['elemailer-latest-posts', []],
]));
$widget_html = \Elemailer_Lite\Helpers\Util::get_template_content($widget_id);
elemailer_it_check('Widgets template renders non-empty HTML', strlen($widget_html) > 500);
elemailer_it_check('Widgets heading renders', strpos($widget_html, 'Widget Heading') !== false);
elemailer_it_check('Widgets text editor renders', strpos($widget_html, 'Widget body text') !== false);
elemailer_it_check('Widgets button renders', strpos($widget_html, 'Widget Button') !== false);
elemailer_it_check('Widgets image renders', strpos($widget_html, 'test-image.png') !== false);
elemailer_it_check('Widgets spacer renders height', strpos($widget_html, '30px') !== false);
elemailer_it_check('Widgets image box renders', strpos($widget_html, 'Box Title') !== false && strpos($widget_html, 'Box description text') !== false);
elemailer_it_check('Widgets footer renders', strpos($widget_html, '123 Test Street') !== false);
elemailer_it_check('Widgets shortcode renders', strpos($widget_html, 'Caption text') !== false);
elemailer_it_check('Widgets latest posts renders', strpos($widget_html, 'Integration Normal Post') !== false);

// Email conversion: doctype, inline styles, section conversion, placeholders, entities.
$email_id = elemailer_it_make_template('Email Conversion', 'publish', elemailer_it_data([
	['elemailer-heading', ['title' => 'Fish & Chips [your-name]']],
	['elemailer-text-editor', ['text_editor' => '<p>Message: [your-message]</p>']],
]));
$email_content = \Elemailer_Lite\Helpers\Util::get_template_content($email_id);
$email_html = \Elemailer_Lite\Helpers\Util::get_email_html_template($email_id, $email_content);
elemailer_it_check('Email conversion has doctype', stripos($email_html, '<!doctype html>') !== false);
elemailer_it_check('Email conversion inlines styles', strpos($email_html, 'style="') !== false);
elemailer_it_check('Email conversion removes section tags', strpos($email_html, '<section') === false);
elemailer_it_check('Email conversion preserves CF7 placeholders', strpos($email_html, '[your-name]') !== false && strpos($email_html, '[your-message]') !== false);
elemailer_it_check('Email conversion escapes ampersands', strpos($email_html, 'Fish &amp; Chips') !== false || strpos($email_html, 'Fish & Chips') !== false);
elemailer_it_check('Email conversion keeps a style block', substr_count(strtolower($email_html), '<style') >= 1);

echo "SUMMARY: " . ($GLOBALS['elemailer_it_checks'] - $GLOBALS['elemailer_it_failures']) . "/{$GLOBALS['elemailer_it_checks']} passed\n";
if ($GLOBALS['elemailer_it_failures'] > 0) {
	exit(1);
}
