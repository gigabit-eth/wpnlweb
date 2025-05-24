<?php

/**
 * WordPress IDE Helper
 * 
 * This file includes WordPress stubs for better IDE support.
 * It should not be included in production.
 * 
 * @package WPNLWeb
 */

// Only load if we're in development mode
if (defined('WP_DEBUG') && WP_DEBUG) {
    // Include WordPress stubs if available
    if (file_exists(__DIR__ . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php')) {
        require_once __DIR__ . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
    }
}
