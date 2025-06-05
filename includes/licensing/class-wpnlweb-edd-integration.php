<?php
/**
 * Easy Digital Downloads (EDD) Integration
 *
 * Handles communication with EDD store for license key generation, remote validation,
 * subscription status synchronization, and customer management.
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
 * EDD Integration Class
 *
 * Manages all interactions with the EDD licensing server including activation,
 * validation, deactivation, and subscription management.
 *
 * @since 1.1.0
 */
class Wpnlweb_Edd_Integration {

	/**
	 * EDD store URL.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    string
	 */
	private $store_url;

	/**
	 * EDD product name.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    string
	 */
	private $item_name;

	/**
	 * EDD product ID.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    int
	 */
	private $item_id;

	/**
	 * API version.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    string
	 */
	private $api_version = '1.0';

	/**
	 * Request timeout in seconds.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    int
	 */
	private $timeout = 15;

	/**
	 * Initialize EDD Integration.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->init_settings();
		$this->setup_hooks();
	}

	/**
	 * Initialize EDD settings.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function init_settings() {
		// TODO: Replace with actual EDD store URL once configured.
		$this->store_url = 'https://wpnlweb.com';
		$this->item_name = 'WPNLWeb Pro';
		$this->item_id   = 1; // Will be set when EDD product is created.
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		add_filter( 'wpnlweb_edd_store_url', array( $this, 'get_store_url' ) );
		add_filter( 'wpnlweb_edd_request_timeout', array( $this, 'get_request_timeout' ) );
	}

	/**
	 * Activate license with EDD store.
	 *
	 * @since  1.1.0
	 * @param  string $license_key License key to activate.
	 * @return array  Activation result.
	 */
	public function activate_license( $license_key ) {
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license_key,
			'item_name'  => $this->item_name,
			'item_id'    => $this->item_id,
			'url'        => home_url(),
		);

		$response = $this->make_api_request( $api_params );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $this->is_valid_response( $license_data ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid response from license server.', 'wpnlweb' ),
			);
		}

		if ( 'valid' === $license_data['license'] ) {
			return array(
				'success' => true,
				'message' => __( 'License activated successfully.', 'wpnlweb' ),
				'data'    => $this->normalize_license_data( $license_data ),
			);
		} else {
			return array(
				'success' => false,
				'message' => $this->get_error_message( $license_data ),
			);
		}
	}

	/**
	 * Deactivate license with EDD store.
	 *
	 * @since  1.1.0
	 * @param  string $license_key License key to deactivate.
	 * @return array  Deactivation result.
	 */
	public function deactivate_license( $license_key ) {
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license_key,
			'item_name'  => $this->item_name,
			'item_id'    => $this->item_id,
			'url'        => home_url(),
		);

		$response = $this->make_api_request( $api_params );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $this->is_valid_response( $license_data ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid response from license server.', 'wpnlweb' ),
			);
		}

		if ( 'deactivated' === $license_data['license'] ) {
			return array(
				'success' => true,
				'message' => __( 'License deactivated successfully.', 'wpnlweb' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => $this->get_error_message( $license_data ),
			);
		}
	}

	/**
	 * Validate license with EDD store.
	 *
	 * @since  1.1.0
	 * @param  string $license_key License key to validate.
	 * @return array  Validation result.
	 */
	public function validate_license( $license_key ) {
		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $license_key,
			'item_name'  => $this->item_name,
			'item_id'    => $this->item_id,
			'url'        => home_url(),
		);

		$response = $this->make_api_request( $api_params );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $this->is_valid_response( $license_data ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid response from license server.', 'wpnlweb' ),
			);
		}

		if ( 'valid' === $license_data['license'] ) {
			return array(
				'success' => true,
				'data'    => $this->normalize_license_data( $license_data ),
			);
		} else {
			return array(
				'success' => false,
				'message' => $this->get_error_message( $license_data ),
			);
		}
	}

	/**
	 * Get version information from EDD store.
	 *
	 * @since  1.1.0
	 * @param  string $license_key License key for version check.
	 * @return array  Version information.
	 */
	public function get_version_info( $license_key ) {
		$api_params = array(
			'edd_action' => 'get_version',
			'license'    => $license_key,
			'item_name'  => $this->item_name,
			'item_id'    => $this->item_id,
			'url'        => home_url(),
			'version'    => WPNLWEB_VERSION,
		);

		$response = $this->make_api_request( $api_params );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$version_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $version_data['new_version'] ) ) {
			return array(
				'success'     => true,
				'new_version' => $version_data['new_version'],
				'package'     => $version_data['package'] ?? '',
				'tested'      => $version_data['tested'] ?? '',
				'homepage'    => $version_data['homepage'] ?? '',
			);
		}

		return array(
			'success' => false,
			'message' => __( 'Unable to retrieve version information.', 'wpnlweb' ),
		);
	}

	/**
	 * Make API request to EDD store.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $api_params API parameters.
	 * @return array|WP_Error API response or error.
	 */
	private function make_api_request( $api_params ) {
		$api_params['version'] = $this->api_version;
		
		$request_url = add_query_arg( $api_params, trailingslashit( $this->store_url ) );

		$response = wp_remote_get( 
			$request_url, 
			array(
				'timeout'   => $this->timeout,
				'sslverify' => true,
				'user-agent' => 'WPNLWeb/' . WPNLWEB_VERSION . '; ' . home_url(),
			)
		);

		// Log API requests for debugging (only in development).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 
				'WPNLWeb EDD API Request: %s',
				$api_params['edd_action'] ?? 'unknown'
			) );
		}

		return $response;
	}

	/**
	 * Check if EDD response is valid.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  mixed $response_data Response data to validate.
	 * @return bool  True if response is valid.
	 */
	private function is_valid_response( $response_data ) {
		return is_array( $response_data ) && isset( $response_data['license'] );
	}

	/**
	 * Normalize license data from EDD.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $edd_data Raw EDD license data.
	 * @return array Normalized license data.
	 */
	private function normalize_license_data( $edd_data ) {
		return array(
			'tier'        => $this->determine_tier( $edd_data ),
			'expires_at'  => $this->parse_expiration_date( $edd_data['expires'] ?? 'lifetime' ),
			'sites_used'  => intval( $edd_data['site_count'] ?? 1 ),
			'sites_limit' => intval( $edd_data['license_limit'] ?? 1 ),
			'customer_name' => $edd_data['customer_name'] ?? '',
			'customer_email' => $edd_data['customer_email'] ?? '',
			'payment_id'  => $edd_data['payment_id'] ?? 0,
			'sites'       => $this->parse_activated_sites( $edd_data['activations_left'] ?? array() ),
		);
	}

	/**
	 * Determine license tier from EDD data.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $edd_data EDD license data.
	 * @return string License tier.
	 */
	private function determine_tier( $edd_data ) {
		$item_name = $edd_data['item_name'] ?? '';
		
		// Map EDD product names to tiers.
		$tier_mapping = array(
			'WPNLWeb Pro'        => 'pro',
			'WPNLWeb Enterprise' => 'enterprise',
			'WPNLWeb Agency'     => 'agency',
		);

		return $tier_mapping[ $item_name ] ?? 'pro';
	}

	/**
	 * Parse expiration date from EDD format.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $expires_string Expiration string from EDD.
	 * @return string|null Formatted expiration date or null for lifetime.
	 */
	private function parse_expiration_date( $expires_string ) {
		if ( 'lifetime' === $expires_string || empty( $expires_string ) ) {
			return null;
		}

		$timestamp = strtotime( $expires_string );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	/**
	 * Parse activated sites from EDD data.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $activations_data Activations data from EDD.
	 * @return array Array of activated site URLs.
	 */
	private function parse_activated_sites( $activations_data ) {
		if ( ! is_array( $activations_data ) ) {
			return array();
		}

		$sites = array();
		foreach ( $activations_data as $activation ) {
			if ( isset( $activation['site_name'] ) ) {
				$sites[] = $activation['site_name'];
			}
		}

		return $sites;
	}

	/**
	 * Get error message from EDD response.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $license_data EDD license data.
	 * @return string Error message.
	 */
	private function get_error_message( $license_data ) {
		$license_status = $license_data['license'] ?? 'unknown';
		
		$error_messages = array(
			'expired'    => __( 'Your license has expired. Please renew your license to continue receiving updates and support.', 'wpnlweb' ),
			'revoked'    => __( 'Your license has been revoked. Please contact support for assistance.', 'wpnlweb' ),
			'missing'    => __( 'License key not found. Please check your license key and try again.', 'wpnlweb' ),
			'invalid'    => __( 'Invalid license key. Please check your license key and try again.', 'wpnlweb' ),
			'site_inactive' => __( 'Your license is not active for this site. Please activate your license first.', 'wpnlweb' ),
			'item_name_mismatch' => __( 'License key is not valid for this product.', 'wpnlweb' ),
			'no_activations_left' => __( 'You have reached the maximum number of activations for this license. Please upgrade your license or deactivate an existing site.', 'wpnlweb' ),
		);

		return $error_messages[ $license_status ] ?? sprintf(
			/* translators: %s: license status */
			__( 'License validation failed: %s', 'wpnlweb' ),
			$license_status
		);
	}

	/**
	 * Get EDD store URL.
	 *
	 * @since  1.1.0
	 * @return string Store URL.
	 */
	public function get_store_url() {
		return $this->store_url;
	}

	/**
	 * Get request timeout.
	 *
	 * @since  1.1.0
	 * @return int Timeout in seconds.
	 */
	public function get_request_timeout() {
		return $this->timeout;
	}

	/**
	 * Set store configuration.
	 *
	 * @since  1.1.0
	 * @param  string $store_url EDD store URL.
	 * @param  string $item_name Product name.
	 * @param  int    $item_id   Product ID.
	 */
	public function set_store_config( $store_url, $item_name, $item_id ) {
		$this->store_url = trailingslashit( $store_url );
		$this->item_name = $item_name;
		$this->item_id   = intval( $item_id );
	}
} 