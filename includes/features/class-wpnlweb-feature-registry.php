<?php
/**
 * Feature Registry System
 *
 * Manages feature definitions, tier-to-feature mapping, and dynamic feature loading.
 * Provides centralized feature management for the WPNLWeb licensing system.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/includes/features
 * @since      1.1.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feature Registry Class
 *
 * Centralized registry for all WPNLWeb features with tier management.
 * Provides feature definition, validation, and dynamic loading capabilities.
 *
 * @since 1.1.0
 */
class Wpnlweb_Feature_Registry {

	/**
	 * Registered features.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $features = array();

	/**
	 * Feature groups.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $groups = array();

	/**
	 * Loaded features cache.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $loaded_features = array();

	/**
	 * Initialize the Feature Registry.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->register_core_features();
		$this->register_feature_groups();
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		add_action( 'init', array( $this, 'register_third_party_features' ) );
		add_filter( 'wpnlweb_registered_features', array( $this, 'get_all_features' ) );
	}

	/**
	 * Register core WPNLWeb features.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function register_core_features() {
		// Free Tier Features.
		$this->register_feature( 'api_endpoint', array(
			'name'          => __( 'REST API Endpoint', 'wpnlweb' ),
			'description'   => __( 'Natural language query REST API endpoint', 'wpnlweb' ),
			'required_tier' => 'free',
			'group'         => 'core',
			'priority'      => 10,
		) );

		$this->register_feature( 'search_shortcode', array(
			'name'          => __( 'Search Shortcode', 'wpnlweb' ),
			'description'   => __( 'Frontend search shortcode for natural language queries', 'wpnlweb' ),
			'required_tier' => 'free',
			'group'         => 'core',
			'priority'      => 10,
		) );

		$this->register_feature( 'admin_interface', array(
			'name'          => __( 'Admin Interface', 'wpnlweb' ),
			'description'   => __( 'WordPress admin settings and management interface', 'wpnlweb' ),
			'required_tier' => 'free',
			'group'         => 'core',
			'priority'      => 10,
		) );

		$this->register_feature( 'schema_org_responses', array(
			'name'          => __( 'Schema.org Responses', 'wpnlweb' ),
			'description'   => __( 'Structured data responses compatible with AI agents', 'wpnlweb' ),
			'required_tier' => 'free',
			'group'         => 'core',
			'priority'      => 10,
		) );

		$this->register_feature( 'query_enhancement', array(
			'name'          => __( 'Query Enhancement', 'wpnlweb' ),
			'description'   => __( 'Enhanced WordPress query processing for natural language', 'wpnlweb' ),
			'required_tier' => 'free',
			'group'         => 'core',
			'priority'      => 10,
		) );

		$this->register_feature( 'basic_caching', array(
			'name'          => __( 'Basic Caching', 'wpnlweb' ),
			'description'   => __( 'WordPress transient-based caching for improved performance', 'wpnlweb' ),
			'required_tier' => 'free',
			'group'         => 'performance',
			'priority'      => 8,
		) );

		$this->register_feature( 'security_features', array(
			'name'          => __( 'Security Features', 'wpnlweb' ),
			'description'   => __( 'Input sanitization, rate limiting, and CORS protection', 'wpnlweb' ),
			'required_tier' => 'free',
			'group'         => 'security',
			'priority'      => 10,
		) );

		$this->register_feature( 'mobile_responsive', array(
			'name'          => __( 'Mobile Responsive', 'wpnlweb' ),
			'description'   => __( 'Responsive design optimized for mobile devices', 'wpnlweb' ),
			'required_tier' => 'free',
			'group'         => 'ui',
			'priority'      => 7,
		) );

		// Pro Tier Features.
		$this->register_feature( 'vector_embeddings', array(
			'name'          => __( 'Vector Embeddings', 'wpnlweb' ),
			'description'   => __( 'Semantic search using vector embeddings and similarity scoring', 'wpnlweb' ),
			'required_tier' => 'pro',
			'group'         => 'advanced_search',
			'priority'      => 9,
		) );

		$this->register_feature( 'analytics_dashboard', array(
			'name'          => __( 'Analytics Dashboard', 'wpnlweb' ),
			'description'   => __( 'Search analytics, usage statistics, and performance metrics', 'wpnlweb' ),
			'required_tier' => 'pro',
			'group'         => 'analytics',
			'priority'      => 8,
		) );

		$this->register_feature( 'advanced_filtering', array(
			'name'          => __( 'Advanced Filtering', 'wpnlweb' ),
			'description'   => __( 'Custom filters, faceted search, and advanced query options', 'wpnlweb' ),
			'required_tier' => 'pro',
			'group'         => 'advanced_search',
			'priority'      => 8,
		) );

		$this->register_feature( 'custom_templates', array(
			'name'          => __( 'Custom Templates', 'wpnlweb' ),
			'description'   => __( 'Customizable search result templates and layouts', 'wpnlweb' ),
			'required_tier' => 'pro',
			'group'         => 'ui',
			'priority'      => 6,
		) );

		$this->register_feature( 'priority_support', array(
			'name'          => __( 'Priority Support', 'wpnlweb' ),
			'description'   => __( 'Priority email support and documentation access', 'wpnlweb' ),
			'required_tier' => 'pro',
			'group'         => 'support',
			'priority'      => 5,
		) );

		// Enterprise Tier Features.
		$this->register_feature( 'realtime_suggestions', array(
			'name'          => __( 'Real-time Suggestions', 'wpnlweb' ),
			'description'   => __( 'Live search suggestions and auto-completion', 'wpnlweb' ),
			'required_tier' => 'enterprise',
			'group'         => 'advanced_search',
			'priority'      => 9,
		) );

		$this->register_feature( 'advanced_analytics', array(
			'name'          => __( 'Advanced Analytics', 'wpnlweb' ),
			'description'   => __( 'Detailed analytics with user behavior tracking and reports', 'wpnlweb' ),
			'required_tier' => 'enterprise',
			'group'         => 'analytics',
			'priority'      => 8,
		) );

		$this->register_feature( 'multisite_licenses', array(
			'name'          => __( 'Multi-site Licenses', 'wpnlweb' ),
			'description'   => __( 'License management across WordPress multisite networks', 'wpnlweb' ),
			'required_tier' => 'enterprise',
			'group'         => 'licensing',
			'priority'      => 9,
		) );

		$this->register_feature( 'custom_integrations', array(
			'name'          => __( 'Custom Integrations', 'wpnlweb' ),
			'description'   => __( 'Custom API integrations and third-party connectors', 'wpnlweb' ),
			'required_tier' => 'enterprise',
			'group'         => 'integrations',
			'priority'      => 7,
		) );

		$this->register_feature( 'white_label', array(
			'name'          => __( 'White Label', 'wpnlweb' ),
			'description'   => __( 'Remove WPNLWeb branding and customize interface', 'wpnlweb' ),
			'required_tier' => 'enterprise',
			'group'         => 'branding',
			'priority'      => 6,
		) );

		// Agency Tier Features.
		$this->register_feature( 'automation_agents', array(
			'name'          => __( 'Automation Agents', 'wpnlweb' ),
			'description'   => __( 'AI-powered content automation and workflow agents', 'wpnlweb' ),
			'required_tier' => 'agency',
			'group'         => 'automation',
			'priority'      => 10,
		) );

		$this->register_feature( 'reseller_management', array(
			'name'          => __( 'Reseller Management', 'wpnlweb' ),
			'description'   => __( 'Client management and sub-license creation tools', 'wpnlweb' ),
			'required_tier' => 'agency',
			'group'         => 'reseller',
			'priority'      => 9,
		) );

		$this->register_feature( 'client_dashboard', array(
			'name'          => __( 'Client Dashboard', 'wpnlweb' ),
			'description'   => __( 'Dedicated dashboard for managing multiple client sites', 'wpnlweb' ),
			'required_tier' => 'agency',
			'group'         => 'reseller',
			'priority'      => 8,
		) );

		$this->register_feature( 'bulk_operations', array(
			'name'          => __( 'Bulk Operations', 'wpnlweb' ),
			'description'   => __( 'Bulk configuration and management across multiple sites', 'wpnlweb' ),
			'required_tier' => 'agency',
			'group'         => 'reseller',
			'priority'      => 7,
		) );

		$this->register_feature( 'custom_development', array(
			'name'          => __( 'Custom Development', 'wpnlweb' ),
			'description'   => __( 'Custom feature development and implementation services', 'wpnlweb' ),
			'required_tier' => 'agency',
			'group'         => 'support',
			'priority'      => 8,
		) );
	}

	/**
	 * Register feature groups.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function register_feature_groups() {
		$this->groups = array(
			'core' => array(
				'name'        => __( 'Core Features', 'wpnlweb' ),
				'description' => __( 'Essential WPNLWeb functionality', 'wpnlweb' ),
				'priority'    => 10,
			),
			'advanced_search' => array(
				'name'        => __( 'Advanced Search', 'wpnlweb' ),
				'description' => __( 'Enhanced search capabilities', 'wpnlweb' ),
				'priority'    => 9,
			),
			'analytics' => array(
				'name'        => __( 'Analytics', 'wpnlweb' ),
				'description' => __( 'Usage tracking and reporting', 'wpnlweb' ),
				'priority'    => 8,
			),
			'performance' => array(
				'name'        => __( 'Performance', 'wpnlweb' ),
				'description' => __( 'Speed and optimization features', 'wpnlweb' ),
				'priority'    => 8,
			),
			'security' => array(
				'name'        => __( 'Security', 'wpnlweb' ),
				'description' => __( 'Security and protection features', 'wpnlweb' ),
				'priority'    => 10,
			),
			'ui' => array(
				'name'        => __( 'User Interface', 'wpnlweb' ),
				'description' => __( 'Design and user experience', 'wpnlweb' ),
				'priority'    => 7,
			),
			'integrations' => array(
				'name'        => __( 'Integrations', 'wpnlweb' ),
				'description' => __( 'Third-party integrations', 'wpnlweb' ),
				'priority'    => 7,
			),
			'automation' => array(
				'name'        => __( 'Automation', 'wpnlweb' ),
				'description' => __( 'AI automation and agents', 'wpnlweb' ),
				'priority'    => 9,
			),
			'reseller' => array(
				'name'        => __( 'Reseller Tools', 'wpnlweb' ),
				'description' => __( 'Agency and reseller features', 'wpnlweb' ),
				'priority'    => 8,
			),
			'licensing' => array(
				'name'        => __( 'Licensing', 'wpnlweb' ),
				'description' => __( 'License management features', 'wpnlweb' ),
				'priority'    => 9,
			),
			'branding' => array(
				'name'        => __( 'Branding', 'wpnlweb' ),
				'description' => __( 'Customization and branding', 'wpnlweb' ),
				'priority'    => 6,
			),
			'support' => array(
				'name'        => __( 'Support', 'wpnlweb' ),
				'description' => __( 'Support and services', 'wpnlweb' ),
				'priority'    => 5,
			),
		);
	}

	/**
	 * Register a new feature.
	 *
	 * @since  1.1.0
	 * @param  string $feature_id Feature identifier.
	 * @param  array  $args       Feature arguments.
	 * @return bool   True if registered successfully.
	 */
	public function register_feature( $feature_id, $args ) {
		$defaults = array(
			'name'          => '',
			'description'   => '',
			'required_tier' => 'free',
			'group'         => 'core',
			'priority'      => 5,
			'dependencies'  => array(),
			'conflicts'     => array(),
			'callback'      => null,
		);

		$feature = wp_parse_args( $args, $defaults );
		$feature['id'] = $feature_id;

		// Validate feature data.
		if ( empty( $feature['name'] ) ) {
			return false;
		}

		$this->features[ $feature_id ] = $feature;
		return true;
	}

	/**
	 * Get feature information.
	 *
	 * @since  1.1.0
	 * @param  string $feature_id Feature identifier.
	 * @return array|null Feature information or null if not found.
	 */
	public function get_feature_info( $feature_id ) {
		return isset( $this->features[ $feature_id ] ) ? $this->features[ $feature_id ] : null;
	}

	/**
	 * Check if feature is registered.
	 *
	 * @since  1.1.0
	 * @param  string $feature_id Feature identifier.
	 * @return bool   True if feature is registered.
	 */
	public function is_registered_feature( $feature_id ) {
		return isset( $this->features[ $feature_id ] );
	}

	/**
	 * Get all registered features.
	 *
	 * @since  1.1.0
	 * @return array All registered features.
	 */
	public function get_all_features() {
		return $this->features;
	}

	/**
	 * Get features by group.
	 *
	 * @since  1.1.0
	 * @param  string $group Group identifier.
	 * @return array Features in the specified group.
	 */
	public function get_features_by_group( $group ) {
		return array_filter( $this->features, function( $feature ) use ( $group ) {
			return $feature['group'] === $group;
		} );
	}

	/**
	 * Get features by tier.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier.
	 * @return array Features available in the specified tier.
	 */
	public function get_features_by_tier( $tier ) {
		return array_filter( $this->features, function( $feature ) use ( $tier ) {
			return $feature['required_tier'] === $tier;
		} );
	}

	/**
	 * Get feature groups.
	 *
	 * @since  1.1.0
	 * @return array All feature groups.
	 */
	public function get_groups() {
		return $this->groups;
	}

	/**
	 * Allow third-party feature registration.
	 *
	 * @since  1.1.0
	 */
	public function register_third_party_features() {
		/**
		 * Allow third-party plugins to register features.
		 *
		 * @since 1.1.0
		 * @param Wpnlweb_Feature_Registry $registry Feature registry instance.
		 */
		do_action( 'wpnlweb_register_features', $this );
	}
} 