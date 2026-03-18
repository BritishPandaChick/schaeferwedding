<?php
/**
 * API Classes Autoloader
 *
 * @package Titan_Security
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load API classes.
require_once __DIR__ . '/class-api-client.php';
require_once __DIR__ . '/class-api-client-factory.php';
