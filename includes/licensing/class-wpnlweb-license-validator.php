<?php
/**
 * License Validator
 *
 * Handles real-time license validation, domain binding verification, and EDD API integration.
 * Provides <50ms validation performance with comprehensive security checks.
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
 * License Validator Class
 *
 * Validates licenses with EDD integration and domain binding verification.
 * Implements performance optimization and comprehensive error handling.
 *
 * @since 1.1.0
 */
class Wpnlweb_License_Validator {

	/**
	 * License cache instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_License_Cache
	 */
	private $cache;

	/**
	 * EDD integration instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_Edd_Integration
	 */
	private $edd;

	/**
	 * Rate limiting cache.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $rate_limit_cache = array();

	/**
	 * Validation timeout in seconds.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    int
	 */
	private $timeout = 5;

	/**
	 * Initialize the License Validator.
	 *
	 * @since 1.1.0
	 * @param Wpnlweb_License_Cache  $cache License cache instance.
	 * @param Wpnlweb_Edd_Integration $edd   EDD integration instance.
	 */
	public function __construct( $cache, $edd ) {
		$this->cache = $cache;
		$this->edd   = $edd;
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		add_action( 'wpnlweb_license_validation_failed', array( $this, 'handle_validation_failure' ), 10, 2 );
		add_filter( 'wpnlweb_license_validation_timeout', array( $this, 'get_validation_timeout' ) );
	}

	/**
	 * Validate license key with EDD API.
	 *
	 * @since  1.1.0
	 * @param  bool $force_remote Force remote validation bypassing cache.
	 * @return array License validation result.
	 */
	public function validate( $force_remote = false ) {
		$license_key = $this->get_stored_license_key();
		
		if ( empty( $license_key ) ) {
			return $this->create_empty_license();
		}

		// Check rate limiting.
		if ( ! $force_remote && $this->is_rate_limited() ) {
			// Return cached result during rate limiting.
			$cached = $this->cache->get_license();
			return false !== $cached ? $cached : $this->create_empty_license();
		}

		$start_time = microtime( true );
		
		try {
			$result = $this->perform_validation( $license_key, $force_remote );
			
			// Log performance metrics.
			$duration = ( microtime( true ) - $start_time ) * 1000;
			if ( $duration > 50 ) {
				error_log( sprintf( 'WPNLWeb: License validation took %sms (target: <50ms)', number_format( $duration, 2 ) ) );
			}
			
			return $result;
			
		} catch ( Exception $e ) {
			error_log( sprintf( 'WPNLWeb: License validation error: %s', $e->getMessage() ) );
			
			/**
			 * Fires when license validation fails.
			 *
			 * @since 1.1.0
			 * @param string $license_key License key that failed.
			 * @param string $error       Error message.
			 */
			do_action( 'wpnlweb_license_validation_failed', $license_key, $e->getMessage() );
			
			return $this->create_error_license( $e->getMessage() );
		}
	}

	/**
	 * Activate license key.
	 *
	 * @since  1.1.0
	 * @param  string $license_key License key to activate.
	 * @return array  Activation result.
	 */
	public function activate( $license_key ) {
		if ( empty( $license_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'License key is required.', 'wpnlweb' ),
			);
		}

		// Validate license key format.
		if ( ! $this->is_valid_license_format( $license_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid license key format.', 'wpnlweb' ),
			);
		}

		// Check rate limiting.
		if ( $this->is_rate_limited() ) {
			return array(
				'success' => false,
				'message' => __( 'Too many validation attempts. Please try again later.', 'wpnlweb' ),
			);
		}

		try {
			$result = $this->edd->activate_license( $license_key );
			
			if ( $result['success'] ) {
				// Store activated license.
				update_option( 'wpnlweb_license_key', sanitize_text_field( $license_key ) );
				
				// Clear cache to force fresh validation.
				$this->cache->invalidate_license();
				
				// Log successful activation.
				error_log( sprintf( 'WPNLWeb: License activated successfully for site %s', get_site_url() ) );
			}
			
			$this->record_rate_limit_attempt();
			return $result;
			
		} catch ( Exception $e ) {
			error_log( sprintf( 'WPNLWeb: License activation error: %s', $e->getMessage() ) );
			
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Activation failed: %s', 'wpnlweb' ),
					$e->getMessage()
				),
			);
		}
	}

	/**
	 * Deactivate license.
	 *
	 * @since  1.1.0
	 * @return array Deactivation result.
	 */
	public function deactivate() {
		$license_key = $this->get_stored_license_key();
		
		if ( empty( $license_key ) ) {
			return array(
				'success' => true,
				'message' => __( 'No license to deactivate.', 'wpnlweb' ),
			);
		}

		try {
			$result = $this->edd->deactivate_license( $license_key );
			
			if ( $result['success'] ) {
				// Remove stored license.
				delete_option( 'wpnlweb_license_key' );
				
				// Clear cache.
				$this->cache->invalidate_license();
				
				// Log deactivation.
				error_log( sprintf( 'WPNLWeb: License deactivated for site %s', get_site_url() ) );
			}
			
			return $result;
			
		} catch ( Exception $e ) {
			error_log( sprintf( 'WPNLWeb: License deactivation error: %s', $e->getMessage() ) );
			
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Deactivation failed: %s', 'wpnlweb' ),
					$e->getMessage()
				),
			);
		}
	}

	/**
	 * Perform actual license validation.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $license_key  License key to validate.
	 * @param  bool   $force_remote Force remote validation.
	 * @return array  Validation result.
	 */
	private function perform_validation( $license_key, $force_remote ) {
		// Try cache first if not forcing remote.
		if ( ! $force_remote ) {
			$cached = $this->cache->get_license();
			if ( false !== $cached && $this->is_cache_valid( $cached ) ) {
				return $cached;
			}
		}

		// Validate with EDD.
		$edd_result = $this->edd->validate_license( $license_key );
		
		if ( ! $edd_result['success'] ) {
			return $this->create_error_license( $edd_result['message'] );
		}

		// Check domain binding.
		$domain_check = $this->verify_domain_binding( $edd_result['data'] );
		if ( ! $domain_check['valid'] ) {
			return $this->create_error_license( $domain_check['message'] );
		}

		// Create valid license data.
		$license = $this->create_valid_license( $edd_result['data'] );
		
		// Record rate limit attempt.
		$this->record_rate_limit_attempt();
		
		return $license;
	}

	/**
	 * Verify domain binding for license.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $license_data License data from EDD.
	 * @return array Domain verification result.
	 */
	private function verify_domain_binding( $license_data ) {
		$current_domain = $this->get_current_domain();
		$allowed_domains = $license_data['sites'] ?? array();

		// If no domains specified, allow any domain (for backward compatibility).
		if ( empty( $allowed_domains ) ) {
			return array( 'valid' => true );
		}

		// Check if current domain is in allowed list.
		foreach ( $allowed_domains as $domain ) {
			if ( $this->domains_match( $current_domain, $domain ) ) {
				return array( 'valid' => true );
			}
		}

		return array(
			'valid'   => false,
			'message' => sprintf(
				/* translators: %s: current domain */
				__( 'License not valid for domain: %s', 'wpnlweb' ),
				$current_domain
			),
		);
	}

	/**
	 * Check if domains match (supporting wildcards).
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $current_domain Current site domain.
	 * @param  string $allowed_domain Allowed domain (may include wildcards).
	 * @return bool   True if domains match.
	 */
	private function domains_match( $current_domain, $allowed_domain ) {
		// Exact match.
		if ( $current_domain === $allowed_domain ) {
			return true;
		}

		// Wildcard subdomain match (*.example.com).
		if ( strpos( $allowed_domain, '*.' ) === 0 ) {
			$base_domain = substr( $allowed_domain, 2 );
			return str_ends_with( $current_domain, '.' . $base_domain ) || $current_domain === $base_domain;
		}

		return false;
	}

	/**
	 * Get current site domain.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return string Current site domain.
	 */
	private function get_current_domain() {
		$site_url = get_site_url();
		$parsed = wp_parse_url( $site_url );
		return $parsed['host'] ?? '';
	}

	/**
	 * Check if validation is rate limited.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return bool True if rate limited.
	 */
	private function is_rate_limited() {
		$attempts = get_transient( 'wpnlweb_validation_attempts' );
		
		if ( false === $attempts ) {
			return false;
		}

		// Allow 10 attempts per hour.
		return $attempts >= 10;
	}

	/**
	 * Record rate limit attempt.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function record_rate_limit_attempt() {
		$attempts = get_transient( 'wpnlweb_validation_attempts' );
		$attempts = false !== $attempts ? $attempts + 1 : 1;
		
		set_transient( 'wpnlweb_validation_attempts', $attempts, HOUR_IN_SECONDS );
	}

	/**
	 * Check if cached license is still valid.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $cached_license Cached license data.
	 * @return bool  True if cache is valid.
	 */
	private function is_cache_valid( $cached_license ) {
		if ( empty( $cached_license ) || ! isset( $cached_license['cached_at'] ) ) {
			return false;
		}

		// Cache valid for 5 minutes.
		$cache_duration = 5 * MINUTE_IN_SECONDS;
		return ( time() - $cached_license['cached_at'] ) < $cache_duration;
	}

	/**
	 * Create empty license (no license key).
	 *
	 * @since  1.1.0
	 * @access private
	 * @return array Empty license data.
	 */
	private function create_empty_license() {
		return array(
			'status'    => 'inactive',
			'tier'      => 'free',
			'cached_at' => time(),
		);
	}

	/**
	 * Create error license.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $message Error message.
	 * @return array  Error license data.
	 */
	private function create_error_license( $message ) {
		return array(
			'status'    => 'error',
			'tier'      => 'free',
			'message'   => $message,
			'cached_at' => time(),
		);
	}

	/**
	 * Create valid license data.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $edd_data EDD license data.
	 * @return array Valid license data.
	 */
	private function create_valid_license( $edd_data ) {
		return array(
			'status'     => 'active',
			'tier'       => $edd_data['tier'] ?? 'pro',
			'expires_at' => $edd_data['expires_at'] ?? null,
			'sites_used' => $edd_data['sites_used'] ?? 1,
			'sites_limit' => $edd_data['sites_limit'] ?? 1,
			'cached_at'  => time(),
		);
	}

	/**
	 * Get stored license key.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return string Stored license key or empty string.
	 */
	private function get_stored_license_key() {
		return get_option( 'wpnlweb_license_key', '' );
	}

	/**
	 * Validate license key format.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $license_key License key to validate.
	 * @return bool   True if format is valid.
	 */
	private function is_valid_license_format( $license_key ) {
		// Basic format validation - adjust based on EDD license key format.
		return ! empty( $license_key ) && strlen( $license_key ) >= 32;
	}

	/**
	 * Handle validation failure.
	 *
	 * @since  1.1.0
	 * @param  string $license_key License key that failed.
	 * @param  string $error       Error message.
	 */
	public function handle_validation_failure( $license_key, $error ) {
		// Log failure details.
		error_log( sprintf(
			'WPNLWeb: License validation failed - Key: %s, Error: %s, Site: %s',
			substr( $license_key, 0, 8 ) . '...',
			$error,
			get_site_url()
		) );
	}

	/**
	 * Get validation timeout.
	 *
	 * @since  1.1.0
	 * @return int Timeout in seconds.
	 */
	public function get_validation_timeout() {
		return $this->timeout;
	}
} 