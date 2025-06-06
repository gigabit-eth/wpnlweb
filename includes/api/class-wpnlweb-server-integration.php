<?php
/**
 * Server Integration Coordinator
 *
 * Coordinates integration between WordPress plugin and FastAPI server,
 * managing feature access, fallback mechanisms, and premium functionality.
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
 * Server Integration Class
 *
 * Coordinates all aspects of the FastAPI server integration including
 * feature access control, premium functionality, and fallback handling.
 *
 * @since 1.0.3
 */
class Wpnlweb_Server_Integration {

	/**
	 * API Client instance.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    Wpnlweb_Api_Client
	 */
	private $api_client;

	/**
	 * Authentication Manager instance.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    Wpnlweb_Auth_Manager
	 */
	private $auth_manager;

	/**
	 * Feature Gates instance.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    Wpnlweb_Feature_Gates
	 */
	private $feature_gates;

	/**
	 * Cache for server responses.
	 *
	 * @since  1.0.3
	 * @access private
	 * @var    array
	 */
	private $response_cache = array();

	/**
	 * Initialize Server Integration.
	 *
	 * @since 1.0.3
	 */
	public function __construct() {
		$this->auth_manager = new Wpnlweb_Auth_Manager();
		$this->api_client = new Wpnlweb_Api_Client( $this->auth_manager );
		
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks and filters.
	 *
	 * @since  1.0.3
	 * @access private
	 */
	private function setup_hooks() {
		// Integration with existing feature gates.
		add_filter( 'wpnlweb_validate_feature_access', array( $this, 'validate_server_feature_access' ), 10, 3 );
		
		// Premium feature enhancement hooks.
		add_filter( 'wpnlweb_search_results', array( $this, 'enhance_search_results' ), 10, 2 );
		
		// Admin integration hooks.
		add_action( 'wpnlweb_admin_server_status', array( $this, 'display_server_status' ) );
		add_action( 'wp_ajax_wpnlweb_test_server_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_wpnlweb_register_site', array( $this, 'ajax_register_site' ) );
		add_action( 'wp_ajax_wpnlweb_refresh_token', array( $this, 'ajax_refresh_token' ) );
		
		// Activation/deactivation hooks.
		add_action( 'wpnlweb_plugin_activated', array( $this, 'on_plugin_activation' ) );
		add_action( 'wpnlweb_plugin_deactivated', array( $this, 'on_plugin_deactivation' ) );
	}

	/**
	 * Validate feature access through server integration.
	 *
	 * @since  1.0.3
	 * @param  bool   $has_access Current access status from local validation.
	 * @param  string $feature    Feature identifier.
	 * @param  int    $user_id    WordPress user ID.
	 * @return bool   Enhanced access status from server validation.
	 */
	public function validate_server_feature_access( $has_access, $feature, $user_id ) {
		// If local validation already denies access, don't check server.
		if ( ! $has_access ) {
			return $has_access;
		}

		// If server is not available, rely on local validation.
		if ( ! $this->api_client->is_available() ) {
			return $has_access;
		}

		// Check with server for premium features.
		$server_response = $this->api_client->validate_feature_access( $feature, $user_id );

		// validate_feature_access always returns an array (never WP_Error due to fallback handling)
		if ( isset( $server_response['fallback'] ) && $server_response['fallback'] ) {
			// Server was unavailable, log but don't deny access if local validation passed.
			error_log( sprintf(
				'WPNLWeb: Server feature validation unavailable for %s, using fallback',
				$feature
			) );
			return $has_access;
		}

		// Use server response if available.
		if ( isset( $server_response['has_access'] ) ) {
			return (bool) $server_response['has_access'];
		}

		return $has_access;
	}

	/**
	 * Enhance NLWeb response with server-powered features.
	 *
	 * @since  1.0.3
	 * @param  array  $response Original NLWeb response.
	 * @param  string $question User's question.
	 * @param  array  $context Query context.
	 * @return array  Enhanced response with premium features.
	 */
	/*
	public function enhance_nlweb_response( $response, $question, $context ) {
		// Only enhance if user has access to premium features and server is available.
		if ( ! $this->can_use_premium_features() ) {
			return $response;
		}

		// Add semantic search enhancement.
		if ( $this->has_feature_access( 'vector_embeddings' ) ) {
			$response = $this->add_semantic_search( $response, $question, $context );
		}

		// Add real-time suggestions.
		if ( $this->has_feature_access( 'realtime_suggestions' ) ) {
			$response = $this->add_realtime_suggestions( $response, $question );
		}

		// Add analytics tracking.
		if ( $this->has_feature_access( 'analytics_dashboard' ) ) {
			$this->track_query_analytics( $question, $response, $context );
		}

		return $response;
	}
	*/

	/**
	 * Enhance search results with premium features.
	 *
	 * @since  1.0.3
	 * @param  array $results Original search results.
	 * @param  string $question User's question.
	 * @return array Enhanced search results.
	 */
	public function enhance_search_results( $results, $question ) {
		if ( ! $this->can_use_premium_features() ) {
			return $results;
		}

		// Add content personalization.
		if ( $this->has_feature_access( 'advanced_filtering' ) ) {
			$results = $this->personalize_results( $results, $question );
		}

		// Add semantic search enhancement for Pro tier.
		if ( $this->has_feature_access( 'vector_embeddings' ) ) {
			$results = $this->add_semantic_search_results( $results, $question );
		}

		return $results;
	}

	/**
	 * Index content for semantic search (Pro tier feature).
	 *
	 * @since  1.1.0
	 * @param  array $content_items Array of content items to index.
	 * @return array Indexing result.
	 */
	public function index_content_for_semantic_search( $content_items ) {
		if ( ! $this->has_feature_access( 'vector_embeddings' ) ) {
			return array(
				'success' => false,
				'error'   => 'Vector embeddings feature requires Pro tier license',
				'upgrade_url' => $this->get_upgrade_url( 'pro' ),
			);
		}

		if ( ! $this->api_client->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'Server connection required for semantic search',
			);
		}

		return $this->api_client->index_content( $content_items );
	}

	/**
	 * Perform semantic search (Pro tier feature).
	 *
	 * @since  1.1.0
	 * @param  string $query          Search query.
	 * @param  array  $search_options Search options.
	 * @return array  Search results.
	 */
	public function semantic_search( $query, $search_options = array() ) {
		if ( ! $this->has_feature_access( 'vector_embeddings' ) ) {
			return array(
				'success' => false,
				'error'   => 'Vector embeddings feature requires Pro tier license',
				'upgrade_url' => $this->get_upgrade_url( 'pro' ),
				'results' => array(),
			);
		}

		if ( ! $this->api_client->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'Server connection required for semantic search',
				'results' => array(),
			);
		}

		return $this->api_client->semantic_search( $query, $search_options );
	}

	/**
	 * Get content recommendations (Pro tier feature).
	 *
	 * @since  1.1.0
	 * @param  string $content_id Content ID for recommendations.
	 * @param  int    $limit      Number of recommendations.
	 * @return array  Recommendations result.
	 */
	public function get_content_recommendations( $content_id, $limit = 5 ) {
		if ( ! $this->has_feature_access( 'vector_embeddings' ) ) {
			return array(
				'success' => false,
				'error'   => 'Vector embeddings feature requires Pro tier license',
				'upgrade_url' => $this->get_upgrade_url( 'pro' ),
				'recommendations' => array(),
			);
		}

		if ( ! $this->api_client->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'Server connection required for content recommendations',
				'recommendations' => array(),
			);
		}

		return $this->api_client->get_content_recommendations( $content_id, $limit );
	}

	/**
	 * Get semantic search service health status.
	 *
	 * @since  1.1.0
	 * @return array Health status information.
	 */
	public function get_semantic_search_health() {
		if ( ! $this->api_client->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'Server connection not available',
				'fallback' => true,
			);
		}

		return $this->api_client->get_semantic_health();
	}

	/**
	 * Request content automation from AI agents.
	 *
	 * @since  1.0.3
	 * @param  array $automation_request Content automation parameters.
	 * @return array Automation result or error.
	 */
	public function request_content_automation( $automation_request ) {
		if ( ! $this->has_feature_access( 'automation_agents' ) ) {
			return array(
				'success' => false,
				'error'   => 'Content automation requires Agency tier license',
				'upgrade_url' => $this->get_upgrade_url( 'agency' ),
			);
		}

		if ( ! $this->api_client->is_available() ) {
			return array(
				'success' => false,
				'error'   => 'Server connection required for content automation',
			);
		}

		return $this->api_client->request_content_automation( $automation_request );
	}

	/**
	 * Get server status information.
	 *
	 * @since  1.0.3
	 * @return array Server status data.
	 */
	public function get_server_status() {
		$auth_status = $this->auth_manager->get_auth_status();
		$connection_test = null;

		if ( $auth_status['has_api_key'] ) {
			$connection_test = $this->api_client->test_connection();
		}

		return array(
			'configured'      => ! empty( get_option( 'wpnlweb_api_server_url' ) ),
			'authentication' => $auth_status,
			'connection'     => $connection_test,
			'features'       => $this->get_available_features(),
		);
	}

	/**
	 * Display server status in admin interface.
	 *
	 * @since  1.0.3
	 */
	public function display_server_status() {
		$status = $this->get_server_status();
		include plugin_dir_path( __DIR__ ) . '../admin/partials/server-status.php';
	}

	/**
	 * AJAX handler for testing server connection.
	 *
	 * @since  1.0.3
	 */
	public function ajax_test_connection() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wpnlweb_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$connection_result = $this->api_client->test_connection();

		if ( $connection_result['success'] ) {
			wp_send_json_success( array(
				'message' => 'Server connection successful',
				'data'    => $connection_result,
			) );
		} else {
			wp_send_json_error( array(
				'message' => 'Connection failed: ' . $connection_result['error'],
				'error'   => $connection_result,
			) );
		}
	}

	/**
	 * AJAX handler for site registration.
	 *
	 * @since  1.0.3
	 */
	public function ajax_register_site() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wpnlweb_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$registration_data = array();
		
		// Add any additional registration data from the form.
		if ( isset( $_POST['license_key'] ) ) {
			$registration_data['license_key'] = sanitize_text_field( $_POST['license_key'] );
		}

		$result = $this->auth_manager->register_site( $registration_data );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => 'Site registered successfully',
				'data'    => $result['data'],
			) );
		} else {
			wp_send_json_error( array(
				'message' => 'Registration failed: ' . $result['error'],
			) );
		}
	}

	/**
	 * AJAX handler for refreshing token.
	 *
	 * @since  1.0.3
	 */
	public function ajax_refresh_token() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wpnlweb_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$result = $this->auth_manager->refresh_token();

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => 'Token refreshed successfully',
				'data'    => $result['data'],
			) );
		} else {
			wp_send_json_error( array(
				'message' => 'Token refresh failed: ' . $result['error'],
			) );
		}
	}

	/**
	 * Handle plugin activation.
	 *
	 * @since  1.0.3
	 */
	public function on_plugin_activation() {
		// Schedule token refresh cron job.
		if ( ! wp_next_scheduled( 'wpnlweb_refresh_token_cron' ) ) {
			wp_schedule_event( time() + 3600, 'hourly', 'wpnlweb_refresh_token_cron' );
		}
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @since  1.0.3
	 */
	public function on_plugin_deactivation() {
		// Clear scheduled events.
		wp_clear_scheduled_hook( 'wpnlweb_refresh_token_cron' );
	}

	/**
	 * Check if user can use premium features.
	 *
	 * @since  1.0.3
	 * @access private
	 * @return bool True if premium features are available.
	 */
	private function can_use_premium_features() {
		return $this->api_client->is_available() && $this->auth_manager->has_valid_token();
	}

	/**
	 * Check if user has access to specific feature.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $feature Feature identifier.
	 * @return bool   True if user has access.
	 */
	private function has_feature_access( $feature ) {
		// Use existing feature gates system.
		return apply_filters( 'wpnlweb_can_access_feature', false, $feature );
	}

	/**
	 * Add semantic search enhancement to response.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  array $response Original response.
	 * @param  string $question User's question.
	 * @param  array $context Query context.
	 * @return array Enhanced response.
	 */
	private function add_semantic_search( $response, $question, $context ) {
		// TODO: Implement semantic search enhancement via server.
		// This would call the FastAPI server's vector search endpoint.
		
		$response['_enhanced'] = true;
		$response['_enhancement_type'] = 'semantic_search';
		
		return $response;
	}

	/**
	 * Add real-time suggestions to response.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  array $response Original response.
	 * @param  string $question User's question.
	 * @return array Enhanced response.
	 */
	private function add_realtime_suggestions( $response, $question ) {
		// TODO: Implement real-time suggestions via server.
		// This would call the FastAPI server's suggestions endpoint.
		
		$response['suggestions'] = array(
			'Try asking: "What are your latest blog posts?"',
			'You might also want to know about our services.',
		);
		
		return $response;
	}

	/**
	 * Track query analytics.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $question User's question.
	 * @param  array $response Search response.
	 * @param  array $context Query context.
	 */
	private function track_query_analytics( $question, $response, $context ) {
		// TODO: Implement analytics tracking via server.
		// This would send analytics data to the FastAPI server.
	}

	/**
	 * Personalize search results.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  array $results Original results.
	 * @param  string $question User's question.
	 * @return array Personalized results.
	 */
	private function personalize_results( $results, $question ) {
		// TODO: Implement result personalization via server.
		// This would call the FastAPI server's personalization endpoint.
		
		return $results;
	}

	/**
	 * Add semantic search results to enhance standard search.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $results Original search results.
	 * @param  string $question User's search question.
	 * @return array Enhanced results with semantic matches.
	 */
	private function add_semantic_search_results( $results, $question ) {
		try {
			// Perform semantic search to find related content.
			$semantic_response = $this->api_client->semantic_search( $question, array(
				'limit' => 5,
				'score_threshold' => 0.5,
			) );

			if ( isset( $semantic_response['results'] ) && ! empty( $semantic_response['results'] ) ) {
				// Add semantic results to the original results with a flag.
				foreach ( $semantic_response['results'] as $semantic_result ) {
					$semantic_result['_source'] = 'semantic_search';
					$semantic_result['_enhanced'] = true;
					$results[] = $semantic_result;
				}
			}
		} catch ( Exception $e ) {
			// Silently fail and return original results.
			error_log( 'WPNLWeb: Semantic search enhancement failed: ' . $e->getMessage() );
		}

		return $results;
	}

	/**
	 * Get available premium features.
	 *
	 * @since  1.0.3
	 * @access private
	 * @return array Available features list.
	 */
	private function get_available_features() {
		if ( ! $this->can_use_premium_features() ) {
			return array();
		}

		$cache_key = 'available_features';
		if ( isset( $this->response_cache[ $cache_key ] ) ) {
			return $this->response_cache[ $cache_key ];
		}

		// Get features from server.
		// TODO: Implement server call to get available features.
		
		$features = array(
			'vector_embeddings'    => $this->has_feature_access( 'vector_embeddings' ),
			'analytics_dashboard'  => $this->has_feature_access( 'analytics_dashboard' ),
			'advanced_filtering'   => $this->has_feature_access( 'advanced_filtering' ),
			'realtime_suggestions' => $this->has_feature_access( 'realtime_suggestions' ),
			'automation_agents'    => $this->has_feature_access( 'automation_agents' ),
		);

		$this->response_cache[ $cache_key ] = $features;
		return $features;
	}

	/**
	 * Get upgrade URL for tier.
	 *
	 * @since  1.0.3
	 * @access private
	 * @param  string $tier Target tier.
	 * @return string Upgrade URL.
	 */
	private function get_upgrade_url( $tier ) {
		// TODO: Get actual upgrade URL from server or configuration.
		return 'https://wpnlweb.com/pricing/?tier=' . $tier;
	}

	/**
	 * Get API Client instance.
	 *
	 * @since  1.0.3
	 * @return Wpnlweb_Api_Client
	 */
	public function get_api_client() {
		return $this->api_client;
	}

	/**
	 * Get Authentication Manager instance.
	 *
	 * @since  1.0.3
	 * @return Wpnlweb_Auth_Manager
	 */
	public function get_auth_manager() {
		return $this->auth_manager;
	}
} 