<?php
/**
 * PHPUnit bootstrap for unit tests that do not need a WordPress request.
 */

if (!defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/../');
}

require_once __DIR__ . '/../helpers/util.php';
