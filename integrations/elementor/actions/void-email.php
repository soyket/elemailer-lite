<?php

namespace Elemailer_Lite\Integrations\Elementor\Actions;

defined('ABSPATH') || exit;

use \ElementorPro\Core\Utils;
use \ElementorPro\Modules\Forms\Classes\Ajax_Handler;
use \ElementorPro\Modules\Forms\Classes\Form_Record;
use \ElementorPro\Modules\Forms\Actions\Email;

/**
 * custom email class for sending email
 * this class will override elementor default email class
 * fired on sending form when used our templete
 *
 * @author elEmailer 
 * @since 1.0.0
 */
class Void_Email extends Email
{

	// template selector status check key
	public $key_template_select_status;
	public $key_selected_template;

	public function __construct()
	{
		// assign for email
		$this->key_template_select_status = 'show_elemailer_email_template_selector';
		$this->key_selected_template = 'select_elemailer_email_template';
	}

	/**
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run($record, $ajax_handler)
	{
		// declare empty email template body
		$elemailer_lite_template_body = '';
		$settings = $record->get('form_settings');
		$send_html = 'plain' !== $settings[$this->get_control_id('email_content_type')];

		// get status of user for using our email template
		$template_active = isset($settings[$this->key_template_select_status]) ? $settings[$this->key_template_select_status] : '';
		// get selected email template id from settings
		$template_id = isset($settings[$this->key_selected_template]) ? $settings[$this->key_selected_template] : '';
		// assign dynamic email template body to empty declared email template body
		if ($template_active == 'yes' && $template_id != '') {
			// get void email template
			$elemailer_lite_template_body = \Elemailer_Lite\Helpers\Util::get_template_content($template_id);
			// send always content type html during use our templete
			$send_html = true;
		}
		$line_break = $send_html ? '<br>' : "\n";
		$fields = [
			'email_to' => get_option('admin_email'),
			/* translators: %s: Site title. */
			'email_subject' => sprintf(__('New message from "%s"', 'elemailer-lite'), get_bloginfo('name')),
			'email_content' => '[all-fields]',
			'email_from_name' => get_bloginfo('name'),
			'email_from' => get_bloginfo('admin_email'),
			'email_reply_to' => 'noreplay@' . Utils::get_site_domain(),
			'email_to_cc' => '',
			'email_to_bcc' => '',
		];
		foreach ($fields as $key => $default) {
			$setting = trim($settings[$this->get_control_id($key)]);
			// assign void email template content as body of template
			$setting = ((($key == 'email_content') && ($template_active == 'yes' && $elemailer_lite_template_body != '')) ? $elemailer_lite_template_body : $setting);
			$setting = $record->replace_setting_shortcodes($setting);
			if (!empty($setting)) {
				$fields[$key] = $setting;
			}
		}

		$email_reply_to = $this->get_reply_to($record, $fields);
		$fields['email_content'] = $this->replace_content_shortcodes($fields['email_content'], $record, $line_break);
		$email_meta = '';
		$form_metadata_settings = $settings[$this->get_control_id('form_metadata')];
		foreach ($record->get('meta') as $id => $field) {
			if (in_array($id, $form_metadata_settings)) {
				$email_meta .= $this->field_formatted($field) . $line_break;
			}
		}
		if (!empty($email_meta)) {
			$fields['email_content'] .= $line_break . '---' . $line_break . $line_break . $email_meta;
		}
		$headers = sprintf('From: %s <%s>' . "\r\n", $fields['email_from_name'], $fields['email_from']);
		$headers .= sprintf('Reply-To: %s' . "\r\n", $email_reply_to);
		if ($send_html) {
			$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
		}
		$cc_header = '';
		if (!empty($fields['email_to_cc'])) {
			$cc_header = 'Cc: ' . $fields['email_to_cc'] . "\r\n";
		}
		/**
		 * Email headers.
		 *
		 * Filters the additional headers sent when the form send an email.
		 *
		 * @since 1.0.0
		 *
		 * @param string|array $headers Additional headers.
		 */
		$headers = apply_filters('elementor_pro/forms/wp_mail_headers', $headers);
		/**
		 * Email content.
		 *
		 * Filters the content of the email sent by the form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $email_content Email content.
		 */
		$fields['email_content'] = apply_filters('elementor_pro/forms/wp_mail_message', $fields['email_content']);
		// retrieve full email template html after rendering form shortcode
		if ($template_active == 'yes' && $template_id != '') {
			$fields['email_content'] = \Elemailer_Lite\Helpers\Util::get_email_html_template($template_id, $fields['email_content']);
		}

		$attachments = $this->get_mail_attachments($record, $settings, $fields);

		$email_sent = false;
		$email_sent = wp_mail($fields['email_to'], $fields['email_subject'], $fields['email_content'], $headers . $cc_header, $attachments);
		if (!empty($fields['email_to_bcc'])) {
			$bcc_emails = explode(',', $fields['email_to_bcc']);
			foreach ($bcc_emails as $bcc_email) {
				wp_mail(trim($bcc_email), $fields['email_subject'], $fields['email_content'], $headers, $attachments);
			}
		}
		/**
		 * Elementor form mail sent.
		 * Fires when an email was sent successfully.
		 *
		 * @since 1.0.0
		 *
		 * @param array       $settings Form settings.
		 * @param Form_Record $record   An instance of the form record.
		 */
		do_action('elementor_pro/forms/mail_sent', $settings, $record);
		if (!$email_sent) {
			$ajax_handler->add_error_message(Ajax_Handler::get_default_message(Ajax_Handler::SERVER_ERROR, $settings));
		}
	}
	/**
	 * override parent function, can't use private method from child
	 * formatted field with submitted value
	 *
	 * @param array $field
	 *
	 * @return array $formatted
	 * @since 1.0.0
	 */
	private function field_formatted($field)
	{
		$formatted = '';
		if (!empty($field['title'])) {
			$formatted = sprintf('%s: %s', $field['title'], $field['value']);
		} elseif (!empty($field['value'])) {
			$formatted = sprintf('%s', $field['value']);
		}
		return $formatted;
	}
	/**
	 * override parent function. can't use private method from child
	 * shortcode replace function from email content function
	 *
	 * @param string $email_content
	 * @param object $record
	 * @param string $line_break
	 *
	 * @return string $email_content
	 * @since 1.0.0
	 */
	private function replace_content_shortcodes($email_content, $record, $line_break)
	{
		$email_content = do_shortcode($email_content);
		$all_fields_shortcode = '[all-fields]';
		if (false !== strpos($email_content, $all_fields_shortcode)) {
			$text = '';
			foreach ($record->get('fields') as $field) {
				$formatted = $this->field_formatted($field);
				if (('textarea' === $field['type']) && ('<br>' === $line_break)) {
					$formatted = str_replace(["\r\n", "\n", "\r"], '<br />', $formatted);
				}
				$text .= $formatted . $line_break;
			}
			$email_content = str_replace($all_fields_shortcode, $text, $email_content);
		}
		return $email_content;
	}

	/**
	 * Retrieve attachments for the outgoing email.
	 *
	 * Collects attachments from supported Elementor settings and upload fields,
	 * then allows 3rd parties to filter the final list.
	 *
	 * @param Form_Record $record
	 * @param array       $settings
	 * @param array       $fields
	 *
	 * @return array
	 * @since 1.9.2
	 */
	private function get_mail_attachments($record, $settings, $fields)
	{
		$attachments = [];

		$setting_attachment_keys = [
			$this->get_control_id('email_attachments'),
			$this->get_control_id('attachments'),
		];

		foreach ($setting_attachment_keys as $attachment_key) {
			if (!empty($settings[$attachment_key])) {
				$attachments = array_merge($attachments, $this->normalize_attachment_value($settings[$attachment_key]));
			}
		}

		foreach ($record->get('fields') as $field) {
			if (empty($field['value'])) {
				continue;
			}

			$field_type = isset($field['type']) ? $field['type'] : '';
			if (!in_array($field_type, ['upload', 'file'], true)) {
				continue;
			}

			$attachments = array_merge($attachments, $this->normalize_attachment_value($field['value']));
		}

		$attachments = array_values(array_unique(array_filter($attachments)));

		/**
		 * Filters attachments sent via Elemailer custom email action.
		 *
		 * @since 1.9.2
		 *
		 * @param array       $attachments Attachment paths passed to wp_mail().
		 * @param Form_Record $record      Current Elementor form record.
		 * @param array       $settings    Current form settings.
		 * @param array       $fields      Final computed mail fields.
		 */
		return apply_filters('elemailer/forms/wp_mail_attachments', $attachments, $record, $settings, $fields);
	}

	/**
	 * Normalize a setting or field value into filesystem attachment paths.
	 *
	 * @param mixed $value
	 *
	 * @return array
	 * @since 1.9.2
	 */
	private function normalize_attachment_value($value)
	{
		if (empty($value)) {
			return [];
		}

		if (is_string($value)) {
			$decoded = json_decode($value, true);
			if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
				$value = $decoded;
			}
		}

		if (!is_array($value)) {
			$value = preg_split('/[\r\n,]+/', (string) $value);
		}

		$attachments = [];
		foreach ($value as $item) {
			if (is_array($item)) {
				$item = isset($item['path']) ? $item['path'] : (isset($item['url']) ? $item['url'] : '');
			}

			$path = $this->normalize_attachment_path($item);
			if (!empty($path)) {
				$attachments[] = $path;
			}
		}

		return $attachments;
	}

	/**
	 * Convert a candidate attachment into a local readable path.
	 *
	 * @param string $attachment
	 *
	 * @return string
	 * @since 1.9.2
	 */
	private function normalize_attachment_path($attachment)
	{
		$attachment = trim((string) $attachment);
		if ('' === $attachment) {
			return '';
		}

		if (file_exists($attachment) && is_readable($attachment)) {
			return $attachment;
		}

		if (filter_var($attachment, FILTER_VALIDATE_URL)) {
			$attachment_id = attachment_url_to_postid($attachment);
			if ($attachment_id) {
				$attachment_path = get_attached_file($attachment_id);
				if ($attachment_path && file_exists($attachment_path) && is_readable($attachment_path)) {
					return $attachment_path;
				}
			}

			$upload_dir = wp_get_upload_dir();
			if (!empty($upload_dir['baseurl']) && 0 === strpos($attachment, $upload_dir['baseurl'])) {
				$relative_path = ltrim(substr($attachment, strlen($upload_dir['baseurl'])), '/');
				$attachment_path = trailingslashit($upload_dir['basedir']) . $relative_path;
				if (file_exists($attachment_path) && is_readable($attachment_path)) {
					return $attachment_path;
				}
			}
		}

		return '';
	}
}
