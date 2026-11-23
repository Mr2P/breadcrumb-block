<?php
/**
 * PHPUnit bootstrap for pure unit tests (no WordPress boot).
 *
 * @package BreadcrumbBlock\Tests
 */

namespace BreadcrumbBlock\Tests;

// Marker for WordPress code that exits without WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', 'wp/' );
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../../includes/short-title.php';
require_once __DIR__ . '/../../includes/breadcrumbs.php';
