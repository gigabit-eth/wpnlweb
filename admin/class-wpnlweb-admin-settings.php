<?php
/**
 * The admin settings management class.
 *
 * Handles WordPress settings registration, validation, and sanitization
 * for the WPNLWeb plugin admin interface.
 *
 * @link       https://wpnlweb.com
 * @since      1.0.0
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Admin settings management class.
 *
 * Defines settings registration, validation callbacks, and sanitization
 * methods for all WPNLWeb admin settings.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin
 * @author     wpnlweb <hey@wpnlweb.com>
 */
class Wpnlweb_Admin_Settings {


	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Initialize WordPress settings registration.
	 *
	 * @since    1.0.0
	 */
	public function init_settings() {
		// Register settings with proper sanitization callbacks.
		register_setting(
			'wpnlweb_settings',
			'wpnlweb_custom_css',
			array(
				'sanitize_callback' => array( $this, 'sanitize_custom_css' ),
				'default'           => '',
			)
		);

		register_setting(
			'wpnlweb_settings',
			'wpnlweb_theme_mode',
			array(
				'sanitize_callback' => array( $this, 'sanitize_theme_mode' ),
				'default'           => 'auto',
			)
		);

		register_setting(
			'wpnlweb_settings',
			'wpnlweb_primary_color',
			array(
				'sanitize_callback' => array( $this, 'sanitize_primary_color' ),
				'default'           => '#3b82f6',
			)
		);

		// Add hooks to clear caches when settings are saved.
		add_action( 'update_option_wpnlweb_theme_mode', array( $this, 'clear_style_caches' ) );
		add_action( 'update_option_wpnlweb_primary_color', array( $this, 'clear_style_caches' ) );
		add_action( 'update_option_wpnlweb_custom_css', array( $this, 'clear_style_caches' ) );
	}

	/**
	 * Sanitize custom CSS input.
	 *
	 * @since    1.0.0
	 * @param    string $input Raw CSS input.
	 * @return   string Sanitized CSS.
	 */
	public function sanitize_custom_css( $input ) {
		if ( empty( $input ) ) {
			return '';
		}

		// Strip dangerous content.
		$input = wp_strip_all_tags( $input );

		// Remove potentially dangerous CSS patterns.
		$dangerous_patterns = array(
			'/javascript\s*:/i',
			'/vbscript\s*:/i',
			'/expression\s*\(/i',
			'/behavior\s*:/i',
			'/binding\s*:/i',
			'/@import/i',
			'/url\s*\(\s*["\']?\s*javascript/i',
			'/url\s*\(\s*["\']?\s*data:/i',
		);

		$input = preg_replace( $dangerous_patterns, '', $input );

		// Ensure we return clean CSS.
		return sanitize_textarea_field( $input );
	}

	/**
	 * Sanitize theme mode setting.
	 *
	 * @since    1.0.0
	 * @param    string $input Theme mode input.
	 * @return   string Sanitized theme mode.
	 */
	public function sanitize_theme_mode( $input ) {
		$valid_modes = array( 'auto', 'light', 'dark' );

		if ( in_array( $input, $valid_modes, true ) ) {
			return $input;
		}

		// Return default if invalid.
		return 'auto';
	}

	/**
	 * Sanitize primary color setting.
	 *
	 * @since    1.0.0
	 * @param    string $input Color input.
	 * @return   string Sanitized hex color.
	 */
	public function sanitize_primary_color( $input ) {
		// Remove any whitespace.
		$input = trim( $input );

		// Validate hex color format.
		if ( preg_match( '/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $input ) ) {
			return strtolower( $input );
		}

		// Try to add # if missing.
		if ( preg_match( '/^([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $input ) ) {
			return '#' . strtolower( $input );
		}

		// Return default color if invalid.
		return '#3b82f6';
	}

	/**
	 * Clear style caches when settings are updated.
	 *
	 * @since    1.0.0
	 */
	public function clear_style_caches() {
		// Clear any WordPress object cache.
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		// Clear any transients we might use for compiled styles in the future.
		delete_transient( 'wpnlweb_compiled_styles' );
		delete_transient( 'wpnlweb_theme_css_cache' );

		// Allow other plugins/themes to clear their caches.
		do_action( 'wpnlweb_clear_style_caches' );
	}

	/**
	 * Get default settings values.
	 *
	 * @since    1.0.0
	 * @return   array Default settings values.
	 */
	public function get_default_settings() {
		return array(
			'wpnlweb_theme_mode'    => 'auto',
			'wpnlweb_primary_color' => '#3b82f6',
			'wpnlweb_custom_css'    => '',
		);
	}

	/**
	 * Get current settings values with defaults.
	 *
	 * @since    1.0.0
	 * @return   array Current settings values.
	 */
	public function get_current_settings() {
		$defaults = $this->get_default_settings();

		return array(
			'wpnlweb_theme_mode'    => get_option( 'wpnlweb_theme_mode', $defaults['wpnlweb_theme_mode'] ),
			'wpnlweb_primary_color' => get_option( 'wpnlweb_primary_color', $defaults['wpnlweb_primary_color'] ),
			'wpnlweb_custom_css'    => get_option( 'wpnlweb_custom_css', $defaults['wpnlweb_custom_css'] ),
		);
	}

	/**
	 * Validate settings input array.
	 *
	 * @since    1.0.0
	 * @param    array $input Settings input array.
	 * @return   array Validated settings array.
	 */
	public function validate_settings( $input ) {
		$validated = array();

		if ( isset( $input['wpnlweb_theme_mode'] ) ) {
			$validated['wpnlweb_theme_mode'] = $this->sanitize_theme_mode( $input['wpnlweb_theme_mode'] );
		}

		if ( isset( $input['wpnlweb_primary_color'] ) ) {
			$validated['wpnlweb_primary_color'] = $this->sanitize_primary_color( $input['wpnlweb_primary_color'] );
		}

		if ( isset( $input['wpnlweb_custom_css'] ) ) {
			$validated['wpnlweb_custom_css'] = $this->sanitize_custom_css( $input['wpnlweb_custom_css'] );
		}

		return $validated;
	}

	/**
	 * Get preset color options.
	 *
	 * @since    1.0.0
	 * @return   array Preset colors array.
	 */
	public function get_preset_colors() {
		return array(
			'#3b82f6', // Blue (default).
			'#ef4444', // Red.
			'#10b981', // Green.
			'#f59e0b', // Orange.
			'#8b5cf6', // Purple.
			'#06b6d4', // Teal.
			'#f97316', // Orange variant.
			'#84cc16',  // Lime.
		);
	}
}
