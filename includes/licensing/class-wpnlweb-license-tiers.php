<?php
/**
 * License Tiers Management
 *
 * Defines license tiers, feature access matrix, and tier-specific limitations.
 * Implements coarse-grained feature gating for clear tier boundaries.
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
 * License Tiers Class
 *
 * Manages license tiers and feature access control for WPNLWeb.
 * Provides coarse-grained feature gating with clear tier boundaries.
 *
 * @since 1.1.0
 */
class Wpnlweb_License_Tiers {

	/**
	 * Available license tiers.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $tiers = array(
		'free'       => 'Free',
		'pro'        => 'Pro',
		'enterprise' => 'Enterprise',
		'agency'     => 'Agency',
	);

	/**
	 * Tier feature matrix.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $features;

	/**
	 * Tier limitations matrix.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $limitations;

	/**
	 * Initialize the License Tiers.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->init_features();
		$this->init_limitations();
	}

	/**
	 * Initialize feature access matrix.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function init_features() {
		$this->features = array(
			'free' => array(
				'api_endpoint',
				'search_shortcode',
				'admin_interface',
				'schema_org_responses',
				'query_enhancement',
				'basic_caching',
				'security_features',
				'mobile_responsive',
			),
			'pro' => array(
				'vector_embeddings',
				'analytics_dashboard',
				'advanced_filtering',
				'custom_templates',
				'priority_support',
			),
			'enterprise' => array(
				'realtime_suggestions',
				'advanced_analytics',
				'multisite_licenses',
				'custom_integrations',
				'white_label',
			),
			'agency' => array(
				'automation_agents',
				'reseller_management',
				'client_dashboard',
				'bulk_operations',
				'custom_development',
			),
		);
	}

	/**
	 * Initialize tier limitations matrix.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function init_limitations() {
		$this->limitations = array(
			'free' => array(
				'sites_limit'      => 1,
				'api_calls_month'  => 1000,
				'storage_mb'       => 10,
				'support_level'    => 'community',
			),
			'pro' => array(
				'sites_limit'      => 1,
				'api_calls_month'  => 10000,
				'storage_mb'       => 100,
				'support_level'    => 'priority',
			),
			'enterprise' => array(
				'sites_limit'      => 100,
				'api_calls_month'  => 100000,
				'storage_mb'       => 1000,
				'support_level'    => 'dedicated',
			),
			'agency' => array(
				'sites_limit'      => 1000,
				'api_calls_month'  => 1000000,
				'storage_mb'       => 10000,
				'support_level'    => 'white_glove',
			),
		);
	}

	/**
	 * Get all available tiers.
	 *
	 * @since  1.1.0
	 * @return array Available license tiers.
	 */
	public function get_tiers() {
		return $this->tiers;
	}

	/**
	 * Get features for specific tier.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier to get features for.
	 * @return array  Features available for the tier.
	 */
	public function get_tier_features( $tier ) {
		if ( ! isset( $this->features[ $tier ] ) ) {
			return $this->features['free'];
		}

		// Include all features from lower tiers.
		$all_features = array();
		foreach ( $this->tiers as $tier_key => $tier_name ) {
			if ( isset( $this->features[ $tier_key ] ) ) {
				$all_features = array_merge( $all_features, $this->features[ $tier_key ] );
			}
			if ( $tier_key === $tier ) {
				break;
			}
		}

		return array_unique( $all_features );
	}

	/**
	 * Check if tier has access to specific feature.
	 *
	 * @since  1.1.0
	 * @param  string $tier    License tier to check.
	 * @param  string $feature Feature to check access for.
	 * @return bool   True if tier has access to feature.
	 */
	public function has_feature_access( $tier, $feature ) {
		$tier_features = $this->get_tier_features( $tier );
		return in_array( $feature, $tier_features, true );
	}

	/**
	 * Check if feature is available in free tier.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature to check.
	 * @return bool   True if feature is available in free tier.
	 */
	public function is_free_feature( $feature ) {
		return $this->has_feature_access( 'free', $feature );
	}

	/**
	 * Get tier limitations.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier to get limitations for.
	 * @return array  Limitations for the tier.
	 */
	public function get_tier_limitations( $tier ) {
		return isset( $this->limitations[ $tier ] ) 
			? $this->limitations[ $tier ] 
			: $this->limitations['free'];
	}

	/**
	 * Get sites limit for tier.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier.
	 * @return int    Maximum sites allowed for tier.
	 */
	public function get_sites_limit( $tier ) {
		$limitations = $this->get_tier_limitations( $tier );
		return isset( $limitations['sites_limit'] ) ? $limitations['sites_limit'] : 1;
	}

	/**
	 * Get API calls limit for tier.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier.
	 * @return int    Monthly API calls limit for tier.
	 */
	public function get_api_calls_limit( $tier ) {
		$limitations = $this->get_tier_limitations( $tier );
		return isset( $limitations['api_calls_month'] ) ? $limitations['api_calls_month'] : 1000;
	}

	/**
	 * Get storage limit for tier.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier.
	 * @return int    Storage limit in MB for tier.
	 */
	public function get_storage_limit( $tier ) {
		$limitations = $this->get_tier_limitations( $tier );
		return isset( $limitations['storage_mb'] ) ? $limitations['storage_mb'] : 10;
	}

	/**
	 * Get support level for tier.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier.
	 * @return string Support level for tier.
	 */
	public function get_support_level( $tier ) {
		$limitations = $this->get_tier_limitations( $tier );
		return isset( $limitations['support_level'] ) ? $limitations['support_level'] : 'community';
	}

	/**
	 * Get tier display information.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier.
	 * @return array  Tier display information.
	 */
	public function get_tier_info( $tier ) {
		$features    = $this->get_tier_features( $tier );
		$limitations = $this->get_tier_limitations( $tier );

		return array(
			'name'        => isset( $this->tiers[ $tier ] ) ? $this->tiers[ $tier ] : 'Unknown',
			'features'    => $features,
			'limitations' => $limitations,
			'pricing'     => $this->get_tier_pricing( $tier ),
		);
	}

	/**
	 * Get tier pricing information.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier.
	 * @return array  Pricing information for tier.
	 */
	public function get_tier_pricing( $tier ) {
		$pricing = array(
			'free' => array(
				'price'    => 0,
				'currency' => 'USD',
				'period'   => 'lifetime',
			),
			'pro' => array(
				'price'    => 29,
				'currency' => 'USD',
				'period'   => 'monthly',
			),
			'enterprise' => array(
				'price'    => 99,
				'currency' => 'USD',
				'period'   => 'monthly',
			),
			'agency' => array(
				'price'    => 299,
				'currency' => 'USD',
				'period'   => 'monthly',
			),
		);

		return isset( $pricing[ $tier ] ) ? $pricing[ $tier ] : $pricing['free'];
	}

	/**
	 * Get upgrade path for tier.
	 *
	 * @since  1.1.0
	 * @param  string $tier Current license tier.
	 * @return string Next available tier for upgrade.
	 */
	public function get_upgrade_tier( $tier ) {
		$tier_order = array_keys( $this->tiers );
		$current_index = array_search( $tier, $tier_order, true );
		
		if ( false === $current_index || $current_index >= count( $tier_order ) - 1 ) {
			return null; // Already at highest tier or invalid tier.
		}

		return $tier_order[ $current_index + 1 ];
	}

	/**
	 * Get downgrade path for tier.
	 *
	 * @since  1.1.0
	 * @param  string $tier Current license tier.
	 * @return string Previous tier for downgrade.
	 */
	public function get_downgrade_tier( $tier ) {
		$tier_order = array_keys( $this->tiers );
		$current_index = array_search( $tier, $tier_order, true );
		
		if ( false === $current_index || $current_index <= 0 ) {
			return null; // Already at lowest tier or invalid tier.
		}

		return $tier_order[ $current_index - 1 ];
	}

	/**
	 * Check if tier supports multisite.
	 *
	 * @since  1.1.0
	 * @param  string $tier License tier to check.
	 * @return bool   True if tier supports multisite.
	 */
	public function supports_multisite( $tier ) {
		return $this->has_feature_access( $tier, 'multisite_licenses' );
	}

	/**
	 * Get tier comparison matrix.
	 *
	 * @since  1.1.0
	 * @return array Comparison matrix for all tiers.
	 */
	public function get_tier_comparison() {
		$comparison = array();
		
		foreach ( $this->tiers as $tier_key => $tier_name ) {
			$comparison[ $tier_key ] = $this->get_tier_info( $tier_key );
		}

		return $comparison;
	}

	/**
	 * Validate tier name.
	 *
	 * @since  1.1.0
	 * @param  string $tier Tier name to validate.
	 * @return bool   True if tier is valid.
	 */
	public function is_valid_tier( $tier ) {
		return isset( $this->tiers[ $tier ] );
	}

	/**
	 * Get feature requirements for upgrade.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature to check upgrade requirements for.
	 * @return string Minimum tier required for feature.
	 */
	public function get_feature_tier_requirement( $feature ) {
		foreach ( $this->tiers as $tier_key => $tier_name ) {
			if ( $this->has_feature_access( $tier_key, $feature ) ) {
				return $tier_key;
			}
		}

		return null; // Feature not found.
	}
} 