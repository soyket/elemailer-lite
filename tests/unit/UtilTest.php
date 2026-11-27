<?php

namespace Elemailer_Lite\Tests\Unit;

use Elemailer_Lite\Helpers\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase {
	public function test_add_param_url_adds_query_string() {
		$this->assertSame(
			'https://example.com/page?foo=bar',
			Util::add_param_url('https://example.com/page', 'foo', 'bar')
		);
	}

	public function test_add_param_url_appends_to_existing_query_string() {
		$this->assertSame(
			'https://example.com/page?foo=bar&baz=qux',
			Util::add_param_url('https://example.com/page?foo=bar', 'baz', 'qux')
		);
	}

	public function test_minify_css_removes_comments_and_writes_style_tag() {
		$source = tempnam(sys_get_temp_dir(), 'elemailer-css-source-');
		$destination = tempnam(sys_get_temp_dir(), 'elemailer-css-output-');

		file_put_contents($source, "/* comment */\n.a { color: red; }\n.b { margin: 0; }\n");

		Util::minify_css($source, $destination);
		$output = file_get_contents($destination);

		$this->assertStringStartsWith('<style>', $output);
		$this->assertStringNotContainsString('comment', $output);
		$this->assertStringContainsString('.a', $output);
		$this->assertStringContainsString('color: red;', $output);
		$this->assertStringNotContainsString("\n", $output);

		unlink($source);
		unlink($destination);
	}
}
