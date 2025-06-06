<?php
/**
 * Authentication Manager for FastAPI Server
 *
 * Handles authentication tokens, API keys, and secure communication
 * between WordPress plugin and FastAPI server.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/includes/api
 * @since      1.0.3
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Authentication Manager Class
 *
 * Manages authentication tokens, API keys, and secure communication
 * protocols for the FastAPI server integration.
 *
 * @since 1.0.3
 */
class Wpnlweb_Auth_Manager {

	/**
	 * Option names for storing authentication data.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    array
	 */
	private $option_names = array(
		'api_key'      => 'wpnlweb_api_key',
		'access_token' => 'wpnlweb_access_token',
		'refresh_token' => 'wpnlweb_refresh_token',
		'token_expires' => 'wpnlweb_token_expires',
		'site_registration' => 'wpnlweb_site_registration',
	);

	/**
	 * Token refresh buffer in seconds (refresh 5 minutes before expiry).
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    int
	 */
	private $refresh_buffer = 300;

	/**
	 * Initialize Authentication Manager.
	 *
	 * @since 1.0.3
	 */
	public function __construct() {
		// Setup hooks for token management.
		add_action( 'wpnlweb_refresh_token_cron', array( $this, 'refresh_token_cron' ) );
		add_action( 'init', array( $this, 'schedule_token_refresh' ) );
	}

	/**
	 * Check if we have a valid authentication token.
	 *
	 * @since  1.0.3
	 * @return bool True if valid token exists.
	 */
	public function has_valid_token() {
		$token = $this->get_access_token();
		$expires = get_option( $this->option_names['token_expires'], 0 );
		
		// Check if token exists and hasn't expired.
		if ( empty( $token ) || time() >= $expires ) {
			return false;
		}

		return true;
	}

	/**
	 * Get current access token.
	 *
	 * @since  1.0.3
	 * @return string|null Access token or null if not available.
	 */
	public function get_access_token() {
		// Try to refresh token if it's about to expire.
		if ( $this->should_refresh_token() ) {
			$this->refresh_access_token();
		}

		return get_option( $this->option_names['access_token'], null );
	}

	/**
	 * Store API key received from server registration.
	 *
	 * @since  1.0.3
	 * @param  string $api_key API key from server.
	 * @return bool   True if stored successfully.
	 */
	public function store_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		// Store API key securely.
		$result = update_option( $this->option_names['api_key'], $this->encrypt_data( $api_key ) );
		
		if ( $result ) {
			// Log successful API key storage.
			error_log( 'WPNLWeb: API key stored successfully' );
		}

		return $result;
	}

	/**
	 * Get stored API key.
	 *
	 * @since  1.0.3
	 * @return string|null Decrypted API key or null if not found.
	 */
	public function get_api_key() {
		$encrypted_key = get_option( $this->option_names['api_key'], null );
		
		if ( empty( $encrypted_key ) ) {
			return null;
		}

		return $this->decrypt_data( $encrypted_key );
	}

	/**
	 * Store authentication tokens from registration response.
	 *
	 * @since  1.0.3
	 * @param  array $token_data Token data from server response.
	 * @return bool Success status.
	 */
	private function store_tokens( $token_data ) {
		try {
			// Prepare token storage.
			$tokens = array(
				'access_token'  => $token_data['access_token'] ?? '',
				'refresh_token' => $token_data['refresh_token'] ?? '',
				'expires_in'    => $token_data['expires_in'] ?? 3600,
				'token_type'    => $token_data['token_type'] ?? 'bearer',
				'created_at'    => time(),
			);

			// Calculate expiry time.
			$tokens['expires_at'] = $tokens['created_at'] + $tokens['expires_in'];

			// Store tokens encrypted.
			$encrypted_tokens = $this->encrypt_data( wp_json_encode( $tokens ) );
			if ( ! $encrypted_tokens ) {
				error_log( 'WPNLWeb: Failed to encrypt tokens' );
				return false;
			}

			update_option( $this->option_names['tokens'], $encrypted_tokens );

			// Schedule token refresh.
			$this->schedule_token_refresh();

			return true;

		} catch ( Exception $e ) {
			error_log( sprintf( 'WPNLWeb: Token storage failed: %s', $e->getMessage() ) );
			return false;
		}
	}

	/**
	 * Refresh access token using refresh token.
	 *
	 * @since  1.0.3
	 * @return array Refresh result.
	 */
	public function refresh_access_token() {
		$refresh_token = $this->get_refresh_token();
		
		if ( empty( $refresh_token ) ) {
			return array(
				'success' => false,
				'error'   => 'No refresh token available',
			);
		}

		$server_url = get_option( 'wpnlweb_api_server_url', '' );
		if ( empty( $server_url ) ) {
			return array(
				'success' => false,
				'error'   => 'Server URL not configured',
			);
		}

		$url = rtrim( $server_url, '/' ) . '/v1/auth/refresh';
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $refresh_token,
		);

		$response = wp_remote_post( $url, array(
			'headers' => $headers,
			'body'    => wp_json_encode( array( 'refresh_token' => $refresh_token ) ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $response_data['detail'] ) 
				? $response_data['detail'] 
				: 'Token refresh failed';
			
			return array(
				'success' => false,
				'error'   => $error_message,
			);
		}

		// Store new tokens.
		if ( isset( $response_data['access_token'] ) ) {
			$this->store_tokens( $response_data );
		}

		return array(
			'success' => true,
			'data'    => $response_data,
		);
	}

	/**
	 * Refresh token (public interface for manual refresh).
	 *
	 * @since  1.0.3
	 * @return array Refresh result.
	 */
	public function refresh_token() {
		// Try automatic token refresh first.
		$result = $this->refresh_access_token();
		
		if ( $result['success'] ) {
			// Schedule next refresh.
			$this->schedule_token_refresh();
			
			return array(
				'success' => true,
				'data'    => $result['data'],
			);
		}

		// If refresh failed, try re-registration as fallback.
		error_log( 'WPNLWeb: Token refresh failed, attempting re-registration: ' . $result['error'] );
		
		// Clear existing tokens to force fresh registration.
		delete_option( $this->option_names['access_token'] );
		delete_option( $this->option_names['refresh_token'] );
		delete_option( $this->option_names['token_expires'] );
		
		// Attempt to re-register site (this will generate new tokens).
		$registration_result = $this->register_site();
		
		if ( $registration_result['success'] ) {
			return array(
				'success' => true,
				'data'    => $registration_result['data'],
			);
		}

		return array(
			'success' => false,
			'error'   => 'Token refresh and re-registration both failed: ' . $registration_result['error'],
		);
	}

	/**
	 * Authenticate with FastAPI server using WordPress site credentials.
	 *
	 * @since  1.0.3
	 * @param  array $auth_data Authentication data (optional additional params).
	 * @return array Authentication result.
	 */
	public function authenticate( $auth_data = array() ) {
		$server_url = get_option( 'wpnlweb_api_server_url', '' );
		if ( empty( $server_url ) ) {
			return array(
				'success' => false,
				'error'   => 'Server URL not configured',
			);
		}

		$url = rtrim( $server_url, '/' ) . '/v1/wordpress/authenticate';
		$headers = array(
			'Content-Type' => 'application/json',
		);

		$request_data = wp_parse_args( $auth_data, array(
			'site_url' => get_site_url(),
			'site_name' => get_bloginfo( 'name' ),
			'admin_email' => get_option( 'admin_email' ),
		) );

		$response = wp_remote_post( $url, array(
			'headers' => $headers,
			'body'    => wp_json_encode( $request_data ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $response_data['detail'] ) 
				? $response_data['detail'] 
				: 'Authentication failed';
			
			return array(
				'success' => false,
				'error'   => $error_message,
			);
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array(
				'success' => false,
				'error'   => 'Invalid response from server',
			);
		}

		// Store tokens if authentication successful.
		if ( isset( $response_data['access_token'] ) ) {
			$this->store_tokens( $response_data );
		}

		return array(
			'success' => true,
			'data'    => $response_data,
		);
	}

	/**
	 * Register site with FastAPI server.
	 *
	 * @since  1.0.3
	 * @param  array $registration_data Site registration data.
	 * @return array Registration result.
	 */
	public function register_site( $registration_data = array() ) {
		$server_url = get_option( 'wpnlweb_api_server_url', '' );
		if ( empty( $server_url ) ) {
			return array(
				'success' => false,
				'error'   => 'Server URL not configured',
			);
		}

		$default_data = array(
			'site_url'       => get_site_url(),
			'site_name'      => get_bloginfo( 'name' ),
			'admin_email'    => get_option( 'admin_email' ),
			'wp_version'     => get_bloginfo( 'version' ),
			'plugin_version' => WPNLWEB_VERSION,
			'php_version'    => PHP_VERSION,
		);

		$data = wp_parse_args( $registration_data, $default_data );

		$url = rtrim( $server_url, '/' ) . '/v1/wordpress/register-site';
		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $data ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $response_data['detail'] ) 
				? $response_data['detail'] 
				: 'Site registration failed';
			
			return array(
				'success' => false,
				'error'   => $error_message,
			);
		}

		// Store registration data.
		if ( isset( $response_data['site_id'] ) ) {
			update_option( $this->option_names['site_registration'], $response_data );
		}

		// Store API key if provided.
		if ( isset( $response_data['api_key'] ) ) {
			$this->store_api_key( $response_data['api_key'] );
		}

		// Store access tokens if provided (new in v1.0.3).
		if ( isset( $response_data['access_token'] ) ) {
			$this->store_tokens( $response_data );
		}

		return array(
			'success' => true,
			'data'    => $response_data,
		);
	}

	/**
	 * Clear all stored authentication data.
	 *
	 * @since  1.0.3
	 */
	public function clear_auth_data() {
		foreach ( $this->option_names as $option_name ) {
			delete_option( $option_name );
		}

		// Clear scheduled token refresh.
		wp_clear_scheduled_hook( 'wpnlweb_refresh_token_cron' );
		
		error_log( 'WPNLWeb: Authentication data cleared' );
	}

	/**
	 * Get stored refresh token.
	 *
	 * @since  1.0.3
	 * @access private
	 * @return string|null Refresh token or null if not found.
	 */
	private function get_refresh_token() {
		$encrypted_token = get_option( $this->option_names['refresh_token'], null );
		
		if ( empty( $encrypted_token ) ) {
			return null;
		}

		return $this->decrypt_data( $encrypted_token );
	}

	/**
	 * Check if token should be refreshed.
	 *
	 * @since  1.0.3
	 * @access private
	 * @return bool True if token should be refreshed.
	 */
	private function should_refresh_token() {
		$expires = get_option( $this->option_names['token_expires'], 0 );
		$refresh_time = $expires - $this->refresh_buffer;
		
		return time() >= $refresh_time;
	}

	/**
	 * Schedule token refresh before expiry.
	 *
	 * @since  1.0.3
	 * @access public
	 */
	public function schedule_token_refresh() {
		$expires = get_option( $this->option_names['token_expires'], 0 );
		
		if ( $expires > 0 ) {
			$refresh_time = $expires - $this->refresh_buffer;
			
			// Clear existing schedule.
			wp_clear_scheduled_hook( 'wpnlweb_refresh_token_cron' );
			
			// Schedule new refresh.
			if ( $refresh_time > time() ) {
				wp_schedule_single_event( $refresh_time, 'wpnlweb_refresh_token_cron' );
			}
		}
	}

	/**
	 * Cron job handler for token refresh.
	 *
	 * @since  1.0.3
	 */
	public function refresh_token_cron() {
		$result = $this->refresh_access_token();
		
		if ( $result['success'] ) {
			error_log( 'WPNLWeb: Automatic token refresh successful' );
			$this->schedule_token_refresh();
		} else {
			error_log( 'WPNLWeb: Automatic token refresh failed: ' . $result['error'] );
		}
	}

	/**
	 * Encrypt sensitive data for storage.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $data Data to encrypt.
	 * @return string Encrypted data.
	 */
	private function encrypt_data( $data ) {
		// Use WordPress authentication salts for encryption key.
		$key = wp_salt( 'auth' );
		
		// Simple XOR encryption (consider using stronger encryption for production).
		$encrypted = '';
		$key_length = strlen( $key );
		$data_length = strlen( $data );
		
		for ( $i = 0; $i < $data_length; $i++ ) {
			$encrypted .= chr( ord( $data[ $i ] ) ^ ord( $key[ $i % $key_length ] ) );
		}
		
		return base64_encode( $encrypted );
	}

	/**
	 * Decrypt sensitive data from storage.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $encrypted_data Encrypted data to decrypt.
	 * @return string Decrypted data.
	 */
	private function decrypt_data( $encrypted_data ) {
		$key = wp_salt( 'auth' );
		$encrypted = base64_decode( $encrypted_data );
		
		if ( $encrypted === false ) {
			return '';
		}
		
		$decrypted = '';
		$key_length = strlen( $key );
		$encrypted_length = strlen( $encrypted );
		
		for ( $i = 0; $i < $encrypted_length; $i++ ) {
			$decrypted .= chr( ord( $encrypted[ $i ] ) ^ ord( $key[ $i % $key_length ] ) );
		}
		
		return $decrypted;
	}

	/**
	 * Get authentication status for admin display.
	 *
	 * @since  1.0.3
	 * @return array Authentication status information.
	 */
	public function get_auth_status() {
		$has_api_key = ! empty( $this->get_api_key() );
		$has_token = $this->has_valid_token();
		$expires = get_option( $this->option_names['token_expires'], 0 );
		$registration = get_option( $this->option_names['site_registration'], array() );

		return array(
			'has_api_key'     => $has_api_key,
			'has_valid_token' => $has_token,
			'token_expires'   => $expires > 0 ? date( 'Y-m-d H:i:s', $expires ) : null,
			'is_registered'   => ! empty( $registration ),
			'site_id'         => isset( $registration['site_id'] ) ? $registration['site_id'] : null,
		);
	}
} 