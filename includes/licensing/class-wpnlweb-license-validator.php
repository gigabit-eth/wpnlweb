<?php
/**
 * License Validator - Server-Side Authority Model
 *
 * Implements zero-trust client validation with all license authority on server.
 * Local caching is minimal and read-only for performance only.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/includes/licensing
 * @since      1.1.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License Validator Class - Server Authority Model
 *
 * All license validation happens server-side. Local storage is read-only cache only.
 * Implements fail-closed security model with real-time server validation.
 *
 * @since 1.1.0
 */
class Wpnlweb_License_Validator {

	/**
	 * Server validation URL.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    string
	 */
	private $server_url = 'https://api.wpnlweb.com/v1';

	/**
	 * Validation timeout in seconds.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    int
	 */
	private $timeout = 10;

	/**
	 * Read-only cache for performance (max 5 minutes).
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $cache = array();

	/**
	 * Rate limiting for server calls.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $rate_limits = array();

	/**
	 * Initialize License Validator.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		// Clear cache on license changes
		add_action( 'wpnlweb_license_key_changed', array( $this, 'clear_cache' ) );
		
		// Background validation cron
		add_action( 'wpnlweb_background_license_check', array( $this, 'background_validation' ) );
		add_action( 'init', array( $this, 'schedule_background_validation' ) );
	}

	/**
	 * Validate feature access with server authority.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature identifier.
	 * @param  array  $context Additional context for validation.
	 * @return array  Validation result with access decision.
	 */
	public function validate_feature_access( $feature, $context = array() ) {
		$license_key = $this->get_stored_license_key();
		
		if ( empty( $license_key ) ) {
			return $this->create_denial_response( 'no_license', 'No license key provided' );
		}

		// Check cache first (performance optimization only)
		$cache_key = $this->get_cache_key( $feature, $context );
		if ( isset( $this->cache[ $cache_key ] ) ) {
			$cached = $this->cache[ $cache_key ];
			if ( time() - $cached['timestamp'] < 300 ) { // 5 minutes max
				return $cached['response'];
			}
		}

		// Rate limiting check
		if ( $this->is_rate_limited() ) {
			return $this->create_denial_response( 'rate_limited', 'Too many validation requests' );
		}

		// Server validation (authority)
		$validation_result = $this->validate_with_server( $license_key, $feature, $context );
		
		// Cache successful responses only (and briefly)
		if ( $validation_result['access_granted'] ) {
			$this->cache[ $cache_key ] = array(
				'timestamp' => time(),
				'response'  => $validation_result,
			);
		}

		return $validation_result;
	}

	/**
	 * Validate with central server (source of truth).
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $license_key License key to validate.
	 * @param  string $feature     Feature being accessed.
	 * @param  array  $context     Additional context.
	 * @return array  Server validation response.
	 */
	private function validate_with_server( $license_key, $feature, $context ) {
		$request_data = array(
			'action'      => 'validate_feature',
			'license_key' => $license_key,
			'domain'      => $this->get_current_domain(),
			'feature'     => $feature,
			'context'     => $context,
			'plugin_version' => WPNLWEB_VERSION,
			'wp_version'  => get_bloginfo( 'version' ),
			'php_version' => PHP_VERSION,
			'timestamp'   => time(),
			'nonce'       => wp_create_nonce( 'wpnlweb_validate_' . $license_key ),
		);

		$response = wp_remote_post( $this->server_url . '/validate', array(
			'body'    => wp_json_encode( $request_data ),
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'WPNLWeb/' . WPNLWEB_VERSION . ' WordPress/' . get_bloginfo( 'version' ),
			),
			'timeout' => $this->timeout,
			'sslverify' => true,
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'WPNLWeb: Server validation failed - ' . $response->get_error_message() );
			return $this->create_denial_response( 'server_error', 'Validation server unavailable' );
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( $http_code !== 200 ) {
			error_log( "WPNLWeb: Server returned HTTP {$http_code}" );
			return $this->create_denial_response( 'server_error', 'Invalid server response' );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'WPNLWeb: Invalid JSON response from server' );
			return $this->create_denial_response( 'server_error', 'Invalid server response format' );
		}

		// Validate response structure
		if ( ! isset( $data['access_granted'] ) || ! isset( $data['license_status'] ) ) {
			error_log( 'WPNLWeb: Incomplete server response' );
			return $this->create_denial_response( 'server_error', 'Incomplete server response' );
		}

		// Log successful validation
		if ( $data['access_granted'] ) {
			$this->log_feature_access( $feature, $context, true );
		}

		return $data;
	}

	/**
	 * Use credits for feature (server-side only).
	 *
	 * @since  1.1.0
	 * @param  string $feature      Feature using credits.
	 * @param  float  $credit_cost  Cost in credits.
	 * @param  array  $context      Usage context.
	 * @return array  Credit usage result.
	 */
	public function use_credits( $feature, $credit_cost, $context = array() ) {
		$license_key = $this->get_stored_license_key();
		
		if ( empty( $license_key ) ) {
			return array( 'success' => false, 'message' => 'No license key' );
		}

		$request_data = array(
			'action'      => 'use_credits',
			'license_key' => $license_key,
			'domain'      => $this->get_current_domain(),
			'feature'     => $feature,
			'cost'        => $credit_cost,
			'context'     => $context,
			'timestamp'   => time(),
			'nonce'       => wp_create_nonce( 'wpnlweb_credits_' . $license_key ),
		);

		$response = wp_remote_post( $this->server_url . '/credits/use', array(
			'body'    => wp_json_encode( $request_data ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => $this->timeout,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => 'Server unavailable' );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( isset( $data['success'] ) && $data['success'] ) {
			// Clear cache on successful credit usage
			$this->clear_cache();
			
			// Log usage locally for analytics
			$this->log_credit_usage( $feature, $credit_cost, $context );
		}

		return $data;
	}

	/**
	 * Get license status (read-only from server).
	 *
	 * @since  1.1.0
	 * @return array License status information.
	 */
	public function get_license_status() {
		$license_key = $this->get_stored_license_key();
		
		if ( empty( $license_key ) ) {
			return array( 'status' => 'no_license', 'tier' => 'free' );
		}

		$request_data = array(
			'action'      => 'get_status',
			'license_key' => $license_key,
			'domain'      => $this->get_current_domain(),
		);

		$response = wp_remote_post( $this->server_url . '/status', array(
			'body'    => wp_json_encode( $request_data ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => $this->timeout,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'status' => 'unknown', 'tier' => 'free' );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return $data ?? array( 'status' => 'unknown', 'tier' => 'free' );
	}

	/**
	 * Activate license (server-side transaction).
	 *
	 * @since  1.1.0
	 * @param  string $license_key License key to activate.
	 * @return array  Activation result.
	 */
	public function activate_license( $license_key ) {
		$request_data = array(
			'action'      => 'activate',
			'license_key' => sanitize_text_field( $license_key ),
			'domain'      => $this->get_current_domain(),
			'site_data'   => array(
				'name'         => get_bloginfo( 'name' ),
				'url'          => get_site_url(),
				'wp_version'   => get_bloginfo( 'version' ),
				'plugin_version' => WPNLWEB_VERSION,
			),
		);

		$response = wp_remote_post( $this->server_url . '/activate', array(
			'body'    => wp_json_encode( $request_data ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 15, // Longer timeout for activation
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => 'Activation server unavailable: ' . $response->get_error_message(),
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( isset( $data['success'] ) && $data['success'] ) {
			// Store license key locally (encrypted)
			update_option( 'wpnlweb_license_key', $this->encrypt_license_key( $license_key ) );
			
			// Clear any cache
			$this->clear_cache();
			
			// Trigger activation hooks
			do_action( 'wpnlweb_license_activated', $license_key, $data );
		}

		return $data;
	}

	/**
	 * Deactivate license (server-side transaction).
	 *
	 * @since  1.1.0
	 * @return array Deactivation result.
	 */
	public function deactivate_license() {
		$license_key = $this->get_stored_license_key();
		
		if ( empty( $license_key ) ) {
			return array( 'success' => true, 'message' => 'No license to deactivate' );
		}

		$request_data = array(
			'action'      => 'deactivate',
			'license_key' => $license_key,
			'domain'      => $this->get_current_domain(),
		);

		$response = wp_remote_post( $this->server_url . '/deactivate', array(
			'body'    => wp_json_encode( $request_data ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => $this->timeout,
		) );

		// Always remove license locally regardless of server response
		delete_option( 'wpnlweb_license_key' );
		$this->clear_cache();

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => true, // Still successful locally
				'message' => 'License removed locally (server unavailable)',
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		
		do_action( 'wpnlweb_license_deactivated', $license_key, $data );
		
		return $data ?? array( 'success' => true, 'message' => 'License deactivated' );
	}

	/**
	 * Create denial response.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $code    Error code.
	 * @param  string $message Error message.
	 * @return array  Denial response.
	 */
	private function create_denial_response( $code, $message ) {
		return array(
			'access_granted'  => false,
			'license_status'  => 'denied',
			'error_code'     => $code,
			'error_message'  => $message,
			'timestamp'      => time(),
		);
	}

	/**
	 * Get cache key for feature validation.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $feature Feature name.
	 * @param  array  $context Context data.
	 * @return string Cache key.
	 */
	private function get_cache_key( $feature, $context ) {
		return 'wpnlweb_validation_' . md5( $feature . serialize( $context ) );
	}

	/**
	 * Check if rate limited.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return bool True if rate limited.
	 */
	private function is_rate_limited() {
		$key = 'wpnlweb_rate_limit';
		$attempts = get_transient( $key );
		
		if ( false === $attempts ) {
			set_transient( $key, 1, MINUTE_IN_SECONDS );
			return false;
		}

		if ( $attempts >= 30 ) { // 30 requests per minute max
			return true;
		}

		set_transient( $key, $attempts + 1, MINUTE_IN_SECONDS );
		return false;
	}

	/**
	 * Get stored license key (encrypted).
	 *
	 * @since  1.1.0
	 * @access private
	 * @return string Decrypted license key.
	 */
	private function get_stored_license_key() {
		$encrypted = get_option( 'wpnlweb_license_key', '' );
		return ! empty( $encrypted ) ? $this->decrypt_license_key( $encrypted ) : '';
	}

	/**
	 * Encrypt license key for local storage.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $license_key License key to encrypt.
	 * @return string Encrypted license key.
	 */
	private function encrypt_license_key( $license_key ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $license_key ); // Fallback
		}

		$key = wp_salt( 'auth' );
		$iv = openssl_random_pseudo_bytes( 16 );
		$encrypted = openssl_encrypt( $license_key, 'AES-256-CBC', $key, 0, $iv );
		
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt license key from local storage.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $encrypted_key Encrypted license key.
	 * @return string Decrypted license key.
	 */
	private function decrypt_license_key( $encrypted_key ) {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return base64_decode( $encrypted_key ); // Fallback
		}

		$data = base64_decode( $encrypted_key );
		$iv = substr( $data, 0, 16 );
		$encrypted = substr( $data, 16 );
		$key = wp_salt( 'auth' );
		
		return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
	}

	/**
	 * Get current domain.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return string Current domain.
	 */
	private function get_current_domain() {
		$parsed = wp_parse_url( get_site_url() );
		return $parsed['host'] ?? '';
	}

	/**
	 * Clear validation cache.
	 *
	 * @since 1.1.0
	 */
	public function clear_cache() {
		$this->cache = array();
	}

	/**
	 * Log feature access for analytics.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $feature Feature accessed.
	 * @param  array  $context Access context.
	 * @param  bool   $granted Whether access was granted.
	 */
	private function log_feature_access( $feature, $context, $granted ) {
		// Send to server in background (non-blocking)
		wp_schedule_single_event( time(), 'wpnlweb_log_feature_access', array(
			'feature' => $feature,
			'context' => $context,
			'granted' => $granted,
			'timestamp' => time(),
		) );
	}

	/**
	 * Log credit usage for analytics.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $feature Feature that used credits.
	 * @param  float  $cost    Credit cost.
	 * @param  array  $context Usage context.
	 */
	private function log_credit_usage( $feature, $cost, $context ) {
		// Send to server in background (non-blocking)
		wp_schedule_single_event( time(), 'wpnlweb_log_credit_usage', array(
			'feature' => $feature,
			'cost'    => $cost,
			'context' => $context,
			'timestamp' => time(),
		) );
	}

	/**
	 * Schedule background validation.
	 *
	 * @since 1.1.0
	 */
	public function schedule_background_validation() {
		if ( ! wp_next_scheduled( 'wpnlweb_background_license_check' ) ) {
			wp_schedule_event( time(), 'hourly', 'wpnlweb_background_license_check' );
		}
	}

	/**
	 * Background license validation.
	 *
	 * @since 1.1.0
	 */
	public function background_validation() {
		$license_key = $this->get_stored_license_key();
		
		if ( ! empty( $license_key ) ) {
			// Validate license is still active
			$status = $this->get_license_status();
			
			if ( isset( $status['status'] ) && $status['status'] !== 'active' ) {
				// License no longer valid - clear cache
				$this->clear_cache();
				
				do_action( 'wpnlweb_license_status_changed', $status );
			}
		}
	}
} 