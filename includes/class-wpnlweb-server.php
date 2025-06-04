<?php
/**
 * The NLWeb server functionality of the plugin.
 *
 * @link       https://wpnlweb.com
 * @since      1.0.0
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * The NLWeb server class.
 *
 * Defines the core NLWeb protocol implementation including REST API endpoints,
 * MCP server compatibility, and enhanced query processing.
 *
 * @since      1.0.0
 * @package    Wpnlweb
 * @subpackage Wpnlweb/includes
 * @author     wpnlweb <hey@wpnlweb.com>
 */
class Wpnlweb_Server {


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

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'rest_api_init', array( $this, 'add_cors_support' ) );

		// Register MCP AJAX handlers.
		add_action( 'wp_ajax_nopriv_mcp_ask', array( $this, 'handle_mcp_ask' ) );
		add_action( 'wp_ajax_mcp_ask', array( $this, 'handle_mcp_ask' ) );
	}

	/**
	 * Register the /ask endpoint
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			'nlweb/v1',
			'/ask',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_ask' ),
				'permission_callback' => '__return_true', // Adjust as needed.
			)
		);
	}

	/**
	 * Add CORS support for AI agents
	 *
	 * @since    1.0.0
	 */
	public function add_cors_support() {
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter(
			'rest_pre_serve_request',
			function ( $value ) {
				// More comprehensive CORS headers for AI agent compatibility.
				header( 'Access-Control-Allow-Origin: *' );
				header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
				header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin' );
				header( 'Access-Control-Allow-Credentials: false' );
				header( 'Access-Control-Max-Age: 86400' ); // 24 hours.
				header( 'Vary: Origin' );

				// Handle OPTIONS preflight request.
				if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
					http_response_code( 200 );
					exit();
				}

				return $value;
			}
		);

		// Also add CORS headers specifically to our endpoint.
		add_action(
			'rest_api_init',
			function () {
				add_filter(
					'rest_post_dispatch',
					function ( $result, $server, $request ) {
						// Check if this is our endpoint.
						if ( strpos( $request->get_route(), '/nlweb/v1/ask' ) !== false ) {
							header( 'Access-Control-Allow-Origin: *' );
							header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS' );
							header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With' );
						}
						return $result;
					},
					10,
					3
				);
			}
		);
	}

	/**
	 * Main NLWeb /ask endpoint handler
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    WordPress REST request object.
	 * @return   array|WP_Error     Schema.org formatted response or error
	 */
	public function handle_ask( $request ) {
		// Add CORS headers for AI agents/browsers.
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );

		// Handle preflight OPTIONS request.
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
			exit( 0 );
		}

		$question = $request->get_param( 'question' );
		$context  = $request->get_param( 'context' ) ?? array();

		if ( empty( $question ) ) {
			return new WP_Error( 'missing_question', 'Question parameter required', array( 'status' => 400 ) );
		}

		// Process the natural language query.
		$results = $this->process_query( $question, $context );

		// Format response according to NLWeb spec.
		return $this->format_nlweb_response( $results, $question );
	}

	/**
	 * Process natural language query into WordPress results
	 *
	 * @since    1.0.0
	 * @param    string $question    Natural language question.
	 * @param    array  $context     Additional query context.
	 * @return   array     Array of matching post objects
	 */
	public function process_query( $question, $context = array() ) {
		// Simple keyword extraction (you'd enhance this with LLM/vector search).
		$keywords = $this->extract_keywords( $question );

		// Build WordPress query.
		$query_args = array(
			'post_status'    => 'publish',
			'posts_per_page' => isset( $context['limit'] ) ? intval( $context['limit'] ) : 10,
			's'              => implode( ' ', $keywords ),
		);

		// Enhance with context if provided.
		if ( ! empty( $context['post_type'] ) ) {
			$query_args['post_type'] = sanitize_text_field( $context['post_type'] );
		} else {
			// Search all public post types by default.
			$query_args['post_type'] = array( 'post', 'page' );
		}

		if ( ! empty( $context['category'] ) ) {
			$query_args['category_name'] = sanitize_text_field( $context['category'] );
		}

		// First attempt: Full keyword search.
		$query = new WP_Query( $query_args );
		$posts = $query->posts;

		// If no results found, try fallback searches.
		if ( empty( $posts ) && ! empty( $keywords ) ) {
			// Fallback 1: Try with individual keywords.
			foreach ( $keywords as $keyword ) {
				$fallback_args      = $query_args;
				$fallback_args['s'] = $keyword;
				$fallback_query     = new WP_Query( $fallback_args );
				if ( $fallback_query->have_posts() ) {
					$posts = $fallback_query->posts;
					break;
				}
			}
		}

		// If still no results, try a more general search.
		if ( empty( $posts ) ) {
			// Fallback 2: Get latest posts if no search matches.
			$latest_args  = array(
				'post_status'    => 'publish',
				'posts_per_page' => min( 5, isset( $context['limit'] ) ? intval( $context['limit'] ) : 5 ),
				'post_type'      => array( 'post', 'page' ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			);
			$latest_query = new WP_Query( $latest_args );
			$posts        = $latest_query->posts;
		}

		return $posts;
	}

	/**
	 * Format response according to NLWeb/Schema.org standards
	 *
	 * @since    1.0.0
	 * @param    array  $posts       Array of post objects.
	 * @param    string $question    Original question.
	 * @return   array     Schema.org formatted response
	 */
	private function format_nlweb_response( $posts, $question ) {
		$items = array();

		foreach ( $posts as $post ) {
			$items[] = array(
				'@type'         => 'Article', // Could be dynamic based on post type.
				'@id'           => get_permalink( $post->ID ),
				'name'          => $post->post_title,
				'description'   => wp_trim_words( $post->post_content, 30 ),
				'url'           => get_permalink( $post->ID ),
				'datePublished' => $post->post_date,
				'author'        => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				),
			);
		}

		return array(
			'@context'     => 'https://schema.org',
			'@type'        => 'SearchResultsPage',
			'query'        => $question,
			'totalResults' => count( $items ),
			'items'        => $items,
		);
	}

	/**
	 * Simple keyword extraction (enhance with NLP)
	 *
	 * @since    1.0.0
	 * @param    string $question    Natural language question.
	 * @return   array     Array of extracted keywords
	 */
	private function extract_keywords( $question ) {
		// Remove common words, extract meaningful terms.
		$stop_words = array( 'what', 'where', 'when', 'how', 'is', 'are', 'the', 'a', 'an', 'and', 'or', 'but' );
		$words      = explode( ' ', strtolower( sanitize_text_field( $question ) ) );

		return array_filter(
			$words,
			function ( $word ) use ( $stop_words ) {
				return ! in_array( $word, $stop_words, true ) && strlen( $word ) > 2;
			}
		);
	}

	/**
	 * Handle MCP (Model Context Protocol) AJAX requests
	 *
	 * @since    1.0.0
	 */
	public function handle_mcp_ask() {
		$input = json_decode( file_get_contents( 'php://input' ), true );

		if ( isset( $input['method'] ) && 'ask' === $input['method'] ) {
			$question = sanitize_text_field( $input['params']['question'] );

			// Create a proper WP_REST_Request object.
			$request = new WP_REST_Request( 'POST' );
			$request->set_param( 'question', $question );

			$response = $this->handle_ask( $request );

			wp_send_json(
				array(
					'jsonrpc' => '2.0',
					'id'      => $input['id'],
					'result'  => $response,
				)
			);
		}

		wp_die();
	}
}
