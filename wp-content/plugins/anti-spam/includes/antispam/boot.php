<?php
/**
 * Anti-Spam Module Bootstrap
 *
 * Loads the anti-spam module classes and dependencies.
 *
 * @package    Titan\Antispam
 * @since      6.5.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the main Antispam module class (handles mode toggling and statistics).
require_once 'classes/class-antispam.php';

// Load the Protector class (handles frontend spam detection and filtering).
require_once 'classes/class-protector.php';

// Load the Advanced Spam Filter class (async ML-based spam detection).
require_once 'classes/class-advanced-spam-filter.php';
