<?php
/**
 * Usually in this file places the code that is responsible for the notification, compatibility with other plugins,
 * minor functions that must be performed on all pages of the admin panel.
 *
 * This file should contain code that applies only to the administration area.
 *
 * @package Titan_Security
 */

use WBCR\Titan\Plugin;
use WBCR\Titan\WBCR_HTTP;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Vulner class.
require_once WTITAN_PLUGIN_DIR . '/includes/vulnerabilities/boot.php';
// Audit class.
require_once WTITAN_PLUGIN_DIR . '/includes/audit/boot.php';
// Anti-spam class.
require_once WTITAN_PLUGIN_DIR . '/includes/antispam/boot.php';
