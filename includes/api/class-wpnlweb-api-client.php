<?php
/**
 * API Client for FastAPI Server Communication
 *
 * Handles secure HTTP communication between WordPress plugin and FastAPI server.
 * Implements authentication, retry logic, and graceful fallback mechanisms.
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
 * API Client Class
 *
 * Manages all communication with the FastAPI server including authentication,
 * request signing, retry logic, and error handling with fallback support.
 *
 * @since 1.0.3
 */
class Wpnlweb_Api_Client {

	/**
	 * FastAPI server base URL.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    string
	 */
	private $server_url;

	/**
	 * Authentication manager instance.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    Wpnlweb_Auth_Manager
	 */
	private $auth_manager;

	/**
	 * HTTP timeout in seconds.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    int
	 */
	private $timeout = 30;

	/**
	 * Maximum retry attempts.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    int
	 */
	private $max_retries = 3;

	/**
	 * Cache for successful requests.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    array
	 */
	private $cache = array();

	/**
	 * Initialize API Client.
	 *
	 * @since 1.0.3
	 * @param Wpnlweb_Auth_Manager $auth_manager Authentication manager instance.
	 */
	public function __construct( $auth_manager ) {
		$this->auth_manager = $auth_manager;
		$this->server_url = get_option( 'wpnlweb_api_server_url', '' );
		
		// Apply filters for configuration.
		$this->timeout = apply_filters( 'wpnlweb_api_timeout', $this->timeout );
		$this->max_retries = apply_filters( 'wpnlweb_api_max_retries', $this->max_retries );
	}

	/**
	 * Check if API client is configured and available.
	 *
	 * @since  1.0.3
	 * @return bool True if client is ready for use.
	 */
	public function is_available() {
		return ! empty( $this->server_url ) && $this->auth_manager->has_valid_token();
	}

	/**
	 * Test connection to FastAPI server.
	 *
	 * @since  1.0.3
	 * @return array Connection test results.
	 */
	public function test_connection() {
		if ( empty( $this->server_url ) ) {
			return array(
				'success' => false,
				'error'   => 'Server URL not configured',
				'code'    => 'missing_url',
			);
		}

		$response = $this->make_request( 'GET', '/health', array(), false );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'code'    => $response->get_error_code(),
			);
		}

		return array(
			'success'      => true,
			'server_info'  => $response,
			'response_time' => $this->get_last_response_time(),
		);
	}

	/**
	 * Validate feature access with server.
	 *
	 * @since  1.0.3
	 * @param  string $feature Feature identifier to validate.
	 * @param  string $user_id WordPress user ID.
	 * @return array  Validation result with access status.
	 */
	public function validate_feature_access( $feature, $user_id = null ) {
		$cache_key = 'feature_access_' . $feature . '_' . ( $user_id ?: get_current_user_id() );
		
		// Check cache first (5-minute TTL).
		$cached = $this->get_cached_response( $cache_key );
		if ( $cached ) {
			return $cached;
		}

		$data = array(
			'feature' => $feature,
			'user_id' => $user_id ?: get_current_user_id(),
			'site_url' => get_site_url(),
		);

		$response = $this->make_request( 'POST', '/v1/wordpress/validate-feature', $data );

		if ( is_wp_error( $response ) ) {
			// Fallback to local validation.
			return $this->fallback_feature_validation( $feature );
		}

		// Cache successful response.
		$this->cache_response( $cache_key, $response, 300 );

		return $response;
	}

	/**
	 * Register WordPress site with FastAPI server.
	 *
	 * @since  1.0.3
	 * @param  array $site_data Site registration data.
	 * @return array Registration result.
	 */
	public function register_site( $site_data ) {
		$default_data = array(
			'site_url'     => get_site_url(),
			'site_name'    => get_bloginfo( 'name' ),
			'admin_email'  => get_option( 'admin_email' ),
			'wp_version'   => get_bloginfo( 'version' ),
			'plugin_version' => WPNLWEB_VERSION,
		);

		$data = wp_parse_args( $site_data, $default_data );

		$response = $this->make_request( 'POST', '/v1/wordpress/register-site', $data );

		if ( ! is_wp_error( $response ) && isset( $response['api_key'] ) ) {
			// Store the API key for future requests.
			$this->auth_manager->store_api_key( $response['api_key'] );
		}

		return $response;
	}

	/**
	 * Request content automation from AI agents.
	 *
	 * @since  1.0.3
	 * @param  array $request_data Content automation request data.
	 * @return array Automation result or error.
	 */
	public function request_content_automation( $request_data ) {
		if ( ! $this->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'API client not available',
				'fallback' => true,
			);
		}

		$response = $this->make_request( 'POST', '/v1/content/automation', $request_data );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'fallback' => true,
			);
		}

		return $response;
	}

	/**
	 * Index content for semantic search.
	 *
	 * @since  1.1.0
	 * @param  array  $content_items Array of content items to index.
	 * @param  string $site_id       Site identifier.
	 * @return array  Indexing result.
	 */
	public function index_content( $content_items, $site_id = null ) {
		if ( ! $this->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'API client not available',
				'fallback' => true,
			);
		}

		if ( ! $site_id ) {
			$site_id = $this->get_site_id();
		}

		$data = array(
			'content_items' => $content_items,
		);

		$endpoint = "/v1/semantic/{$site_id}/index";
		$response = $this->make_request( 'POST', $endpoint, $data );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'fallback' => true,
			);
		}

		return $response;
	}

	/**
	 * Perform semantic search within site content.
	 *
	 * @since  1.1.0
	 * @param  string $query          Search query.
	 * @param  array  $search_options Search options (content_types, limit, score_threshold).
	 * @param  string $site_id        Site identifier.
	 * @return array  Search results.
	 */
	public function semantic_search( $query, $search_options = array(), $site_id = null ) {
		if ( ! $this->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'API client not available',
				'fallback' => true,
				'results' => array(),
			);
		}

		if ( ! $site_id ) {
			$site_id = $this->get_site_id();
		}

		$defaults = array(
			'content_types'    => null,
			'limit'           => 10,
			'score_threshold' => 0.7,
		);

		$options = wp_parse_args( $search_options, $defaults );

		$data = array(
			'query'           => $query,
			'content_types'   => $options['content_types'],
			'limit'           => $options['limit'],
			'score_threshold' => $options['score_threshold'],
		);

		$endpoint = "/v1/semantic/{$site_id}/search";
		$response = $this->make_request( 'POST', $endpoint, $data );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'fallback' => true,
				'results' => array(),
			);
		}

		return $response;
	}

	/**
	 * Get content recommendations based on semantic similarity.
	 *
	 * @since  1.1.0
	 * @param  string $content_id Content ID for recommendations.
	 * @param  int    $limit      Number of recommendations to return.
	 * @param  string $site_id    Site identifier.
	 * @return array  Recommendations result.
	 */
	public function get_content_recommendations( $content_id, $limit = 5, $site_id = null ) {
		if ( ! $this->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'API client not available',
				'fallback' => true,
				'recommendations' => array(),
			);
		}

		if ( ! $site_id ) {
			$site_id = $this->get_site_id();
		}

		$data = array(
			'content_id' => $content_id,
			'limit'      => $limit,
		);

		$endpoint = "/v1/semantic/{$site_id}/recommendations";
		$response = $this->make_request( 'POST', $endpoint, $data );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'fallback' => true,
				'recommendations' => array(),
			);
		}

		return $response;
	}

	/**
	 * Delete all indexed content for the current site.
	 *
	 * @since  1.1.0
	 * @param  string $site_id Site identifier.
	 * @return array  Deletion result.
	 */
	public function delete_site_content( $site_id = null ) {
		if ( ! $this->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'API client not available',
				'fallback' => true,
			);
		}

		if ( ! $site_id ) {
			$site_id = $this->get_site_id();
		}

		$endpoint = "/v1/semantic/site/{$site_id}";
		$response = $this->make_request( 'DELETE', $endpoint );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'fallback' => true,
			);
		}

		return $response;
	}

	/**
	 * Get semantic search service health status.
	 *
	 * @since  1.1.0
	 * @return array Health status information.
	 */
	public function get_semantic_health() {
		if ( ! $this->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'API client not available',
				'fallback' => true,
			);
		}

		$response = $this->make_request( 'GET', '/v1/semantic/health', array(), false );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'fallback' => true,
			);
		}

		return $response;
	}

	/**
	 * Get site-specific statistics about indexed content.
	 *
	 * @since  1.1.0
	 * @param  string $site_id Site identifier.
	 * @return array  Site statistics.
	 */
	public function get_site_stats( $site_id = null ) {
		if ( ! $this->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'API client not available',
				'fallback' => true,
			);
		}

		if ( ! $site_id ) {
			$site_id = $this->get_site_id();
		}

		$endpoint = "/v1/semantic/{$site_id}/stats";
		$response = $this->make_request( 'GET', $endpoint );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'fallback' => true,
			);
		}

		return $response;
	}

	/**
	 * Get unique site identifier for API requests.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return string Site identifier.
	 */
	private function get_site_id() {
		// Generate a unique identifier for this WordPress site.
		// Use site URL hash to ensure consistency across requests.
		$site_url = get_site_url();
		return md5( $site_url );
	}

	/**
	 * Make authenticated HTTP request to FastAPI server.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $method HTTP method (GET, POST, PUT, DELETE).
	 * @param  string $endpoint API endpoint path.
	 * @param  array  $data Request data.
	 * @param  bool   $require_auth Whether authentication is required.
	 * @return array|WP_Error Response data or error.
	 */
	private function make_request( $method, $endpoint, $data = array(), $require_auth = true ) {
		$url = rtrim( $this->server_url, '/' ) . $endpoint;
		$headers = array(
			'Content-Type' => 'application/json',
			'User-Agent'   => 'WPNLWeb/' . WPNLWEB_VERSION,
		);

		// Add authentication if required.
		if ( $require_auth ) {
			$token = $this->auth_manager->get_access_token();
			if ( ! $token ) {
				return new WP_Error( 'no_auth_token', 'No valid authentication token available' );
			}
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		// Add site verification.
		$headers['X-WordPress-Site'] = get_site_url();
		$headers['X-WordPress-Nonce'] = wp_create_nonce( 'wpnlweb_api_request' );

		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => $this->timeout,
		);

		if ( ! empty( $data ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $data );
		} elseif ( ! empty( $data ) && 'GET' === $method ) {
			$url = add_query_arg( $data, $url );
		}

		$start_time = microtime( true );
		$response = $this->make_request_with_retry( $url, $args );
		$this->last_response_time = ( microtime( true ) - $start_time ) * 1000; // Convert to milliseconds.

		if ( is_wp_error( $response ) ) {
			error_log( sprintf(
				'WPNLWeb API Error: %s - %s',
				$response->get_error_code(),
				$response->get_error_message()
			) );
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code >= 400 ) {
			$error_data = json_decode( $body, true );
			$error_message = isset( $error_data['detail'] ) ? $error_data['detail'] : 'API request failed';
			
			return new WP_Error( 
				'api_error', 
				$error_message, 
				array( 'status' => $status_code ) 
			);
		}

		$decoded = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_decode_error', 'Invalid JSON response from server' );
		}

		return $decoded;
	}

	/**
	 * Make HTTP request with retry logic.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $url Request URL.
	 * @param  array  $args Request arguments.
	 * @return array|WP_Error Response or error.
	 */
	private function make_request_with_retry( $url, $args ) {
		$retry_count = 0;
		
		while ( $retry_count <= $this->max_retries ) {
			$response = wp_remote_request( $url, $args );
			
			if ( ! is_wp_error( $response ) ) {
				$status_code = wp_remote_retrieve_response_code( $response );
				
				// Success or client error (don't retry 4xx).
				if ( $status_code < 500 ) {
					return $response;
				}
			}
			
			$retry_count++;
			
			// Don't sleep after the last attempt.
			if ( $retry_count <= $this->max_retries ) {
				// Exponential backoff: 1s, 2s, 4s.
				sleep( pow( 2, $retry_count - 1 ) );
			}
		}
		
		return $response; // Return the last error.
	}

	/**
	 * Fallback feature validation for offline mode.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $feature Feature identifier.
	 * @return array  Local validation result.
	 */
	private function fallback_feature_validation( $feature ) {
		// Check if feature is available in free tier.
		$license_tiers = new Wpnlweb_License_Tiers();
		$is_free_feature = $license_tiers->is_free_feature( $feature );

		return array(
			'has_access' => $is_free_feature,
			'tier'       => $is_free_feature ? 'free' : 'pro',
			'fallback'   => true,
			'message'    => $is_free_feature 
				? 'Feature available offline' 
				: 'Premium feature requires server connection',
		);
	}

	/**
	 * Get cached response if available and not expired.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $cache_key Cache identifier.
	 * @return array|null Cached response or null if not found/expired.
	 */
	private function get_cached_response( $cache_key ) {
		$cached = get_transient( 'wpnlweb_api_' . $cache_key );
		return $cached ? $cached : null;
	}

	/**
	 * Cache response for specified duration.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $cache_key Cache identifier.
	 * @param  array  $response Response data to cache.
	 * @param  int    $duration Cache duration in seconds.
	 */
	private function cache_response( $cache_key, $response, $duration = 300 ) {
		set_transient( 'wpnlweb_api_' . $cache_key, $response, $duration );
	}

	/**
	 * Get last response time in milliseconds.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    float
	 */
	private $last_response_time = 0;

	/**
	 * Get the response time of the last request.
	 *
	 * @since  1.0.3
	 * @return float Response time in milliseconds.
	 */
	public function get_last_response_time() {
		return $this->last_response_time;
	}

	/**
	 * Clear all cached API responses.
	 *
	 * @since  1.0.3
	 */
	public function clear_cache() {
		global $wpdb;
		
		$wpdb->query( 
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_wpnlweb_api_%'
			)
		);
	}
} 