<?php
/**
 * Addon Manager
 *
 * Manages multiple EDD product licenses, addon validation, and feature access control.
 * Supports granular addon licensing alongside base tier permissions.
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
 * Addon Manager Class
 *
 * Handles multiple addon licenses, credit tracking foundation, and
 * modular feature loading based on addon availability.
 *
 * @since 1.1.0
 */
class Wpnlweb_Addon_Manager {

	/**
	 * EDD integration instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_Edd_Integration
	 */
	private $edd;

	/**
	 * License cache instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_License_Cache
	 */
	private $cache;

	/**
	 * Available addons registry.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $addon_registry = array();

	/**
	 * Active addons cache.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array|null
	 */
	private $active_addons = null;

	/**
	 * Initialize Addon Manager.
	 *
	 * @since 1.1.0
	 * @param Wpnlweb_Edd_Integration $edd   EDD integration instance.
	 * @param Wpnlweb_License_Cache   $cache License cache instance.
	 */
	public function __construct( $edd, $cache ) {
		$this->edd   = $edd;
		$this->cache = $cache;
		$this->init_addon_registry();
		$this->setup_hooks();
	}

	/**
	 * Initialize addon registry with available addons.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function init_addon_registry() {
		$this->addon_registry = array(
			'automation_agents' => array(
				'name'           => __( 'Automation Agents', 'wpnlweb' ),
				'description'    => __( 'AI-powered content automation and bulk operations', 'wpnlweb' ),
				'edd_item_name'  => 'WPNLWeb Automation Agents',
				'edd_item_id'    => 2, // Set when EDD product created
				'required_tier'  => 'pro',
				'type'           => 'credit_based',
				'features'       => array( 'content_automation', 'bulk_operations', 'workflow_triggers' ),
				'credit_cost'    => array(
					'content_generation' => 10,
					'bulk_operation'     => 5,
					'workflow_trigger'   => 2,
				),
			),
			'ai_content_generation' => array(
				'name'           => __( 'AI Content Generation', 'wpnlweb' ),
				'description'    => __( 'Advanced AI content writing and SEO optimization', 'wpnlweb' ),
				'edd_item_name'  => 'WPNLWeb AI Content Generation',
				'edd_item_id'    => 3, // Set when EDD product created
				'required_tier'  => 'pro',
				'type'           => 'credit_based',
				'features'       => array( 'ai_writing', 'seo_optimization', 'content_enhancement' ),
				'credit_cost'    => array(
					'generate_post'    => 25,
					'seo_optimization' => 15,
					'content_rewrite'  => 20,
				),
			),
			'advanced_analytics' => array(
				'name'           => __( 'Advanced Analytics Pro', 'wpnlweb' ),
				'description'    => __( 'Custom reports, data export, and advanced insights', 'wpnlweb' ),
				'edd_item_name'  => 'WPNLWeb Advanced Analytics Pro',
				'edd_item_id'    => 4, // Set when EDD product created
				'required_tier'  => 'pro',
				'type'           => 'feature_based',
				'features'       => array( 'custom_reports', 'data_export', 'advanced_insights', 'real_time_analytics' ),
			),
		);

		/**
		 * Filter available addons registry.
		 *
		 * @since 1.1.0
		 * @param array $addon_registry Available addons configuration.
		 */
		$this->addon_registry = apply_filters( 'wpnlweb_addon_registry', $this->addon_registry );
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		// Addon license management hooks.
		add_action( 'wpnlweb_addon_activated', array( $this, 'on_addon_activated' ), 10, 2 );
		add_action( 'wpnlweb_addon_deactivated', array( $this, 'on_addon_deactivated' ), 10, 2 );

		// Credit system hooks (foundation for future implementation).
		add_action( 'wpnlweb_consume_credits', array( $this, 'consume_credits' ), 10, 3 );
		add_filter( 'wpnlweb_get_credit_balance', array( $this, 'get_credit_balance' ), 10, 2 );

		// Cache invalidation.
		add_action( 'wpnlweb_license_updated', array( $this, 'clear_addon_cache' ) );
	}

	/**
	 * Get all active addons for current site.
	 *
	 * @since  1.1.0
	 * @return array Active addon configurations.
	 */
	public function get_active_addons() {
		if ( null !== $this->active_addons ) {
			return $this->active_addons;
		}

		// Check cache first.
		$cache_key = 'wpnlweb_active_addons';
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached ) {
			$this->active_addons = $cached;
			return $this->active_addons;
		}

		// Validate each addon license.
		$active_addons = array();
		
		foreach ( $this->addon_registry as $addon_id => $addon_config ) {
			if ( $this->validate_addon_license( $addon_id ) ) {
				$active_addons[ $addon_id ] = $addon_config;
			}
		}

		// Cache results for 1 hour (non-critical).
		set_transient( $cache_key, $active_addons, HOUR_IN_SECONDS );
		$this->active_addons = $active_addons;

		return $active_addons;
	}

	/**
	 * Check if specific addon is active and licensed.
	 *
	 * @since  1.1.0
	 * @param  string $addon_id Addon identifier.
	 * @return bool   True if addon is active and licensed.
	 */
	public function has_addon( $addon_id ) {
		if ( ! isset( $this->addon_registry[ $addon_id ] ) ) {
			return false;
		}

		$active_addons = $this->get_active_addons();
		return isset( $active_addons[ $addon_id ] );
	}

	/**
	 * Validate addon access including base tier requirements.
	 *
	 * @since  1.1.0
	 * @param  string $addon_id Addon identifier.
	 * @param  string $base_tier Current base license tier.
	 * @return bool   True if addon access is valid.
	 */
	public function validate_addon_access( $addon_id, $base_tier = null ) {
		if ( ! isset( $this->addon_registry[ $addon_id ] ) ) {
			return false;
		}

		$addon_config = $this->addon_registry[ $addon_id ];
		
		// Check base tier requirement.
		if ( null === $base_tier ) {
			// Get base tier from license manager (will implement integration).
			$base_tier = 'free'; // Placeholder - integrate with license manager.
		}

		$required_tier = $addon_config['required_tier'];
		$tier_hierarchy = array( 'free' => 0, 'pro' => 1, 'enterprise' => 2, 'agency' => 3 );

		if ( ! isset( $tier_hierarchy[ $base_tier ] ) || ! isset( $tier_hierarchy[ $required_tier ] ) ) {
			return false;
		}

		// Check if base tier meets addon requirement.
		if ( $tier_hierarchy[ $base_tier ] < $tier_hierarchy[ $required_tier ] ) {
			return false;
		}

		// Check addon license.
		return $this->validate_addon_license( $addon_id );
	}

	/**
	 * Get addon configuration.
	 *
	 * @since  1.1.0
	 * @param  string $addon_id Addon identifier.
	 * @return array|null Addon configuration or null if not found.
	 */
	public function get_addon_config( $addon_id ) {
		return isset( $this->addon_registry[ $addon_id ] ) ? $this->addon_registry[ $addon_id ] : null;
	}

	/**
	 * Get credit balance for addon (foundation for future implementation).
	 *
	 * @since  1.1.0
	 * @param  string $addon_id Addon identifier.
	 * @param  int    $user_id  Optional. User ID for user-specific credits.
	 * @return int    Credit balance.
	 */
	public function get_credit_balance( $addon_id, $user_id = null ) {
		if ( ! $this->has_addon( $addon_id ) ) {
			return 0;
		}

		$addon_config = $this->get_addon_config( $addon_id );
		
		if ( 'credit_based' !== $addon_config['type'] ) {
			return 0; // Feature-based addons don't use credits.
		}

		// TODO: Implement actual credit tracking.
		// For now, return default credits for development.
		$option_key = 'wpnlweb_addon_credits_' . $addon_id;
		if ( $user_id ) {
			$option_key .= '_user_' . $user_id;
		}

		return intval( get_option( $option_key, 1000 ) ); // Default 1000 credits.
	}

	/**
	 * Consume credits for addon operation (foundation for future implementation).
	 *
	 * @since  1.1.0
	 * @param  string $addon_id  Addon identifier.
	 * @param  string $operation Operation type.
	 * @param  int    $cost      Credit cost (optional, will lookup from config).
	 */
	public function consume_credits( $addon_id, $operation, $cost = null ) {
		if ( ! $this->has_addon( $addon_id ) ) {
			return false;
		}

		$addon_config = $this->get_addon_config( $addon_id );
		
		if ( 'credit_based' !== $addon_config['type'] ) {
			return true; // Feature-based addons don't consume credits.
		}

		// Determine cost from configuration.
		if ( null === $cost && isset( $addon_config['credit_cost'][ $operation ] ) ) {
			$cost = $addon_config['credit_cost'][ $operation ];
		}

		if ( null === $cost || $cost <= 0 ) {
			return true; // Free operation.
		}

		$current_balance = $this->get_credit_balance( $addon_id );
		
		if ( $current_balance < $cost ) {
			/**
			 * Fires when insufficient credits for operation.
			 *
			 * @since 1.1.0
			 * @param string $addon_id  Addon identifier.
			 * @param string $operation Operation type.
			 * @param int    $cost      Required credits.
			 * @param int    $balance   Current balance.
			 */
			do_action( 'wpnlweb_insufficient_credits', $addon_id, $operation, $cost, $current_balance );
			return false;
		}

		// TODO: Implement actual credit deduction.
		// For now, store in options for development.
		$option_key = 'wpnlweb_addon_credits_' . $addon_id;
		$new_balance = $current_balance - $cost;
		update_option( $option_key, $new_balance );

		/**
		 * Fires when credits are consumed.
		 *
		 * @since 1.1.0
		 * @param string $addon_id     Addon identifier.
		 * @param string $operation    Operation type.
		 * @param int    $cost         Credits consumed.
		 * @param int    $new_balance  Remaining balance.
		 */
		do_action( 'wpnlweb_credits_consumed', $addon_id, $operation, $cost, $new_balance );

		return true;
	}

	/**
	 * Activate addon license.
	 *
	 * @since  1.1.0
	 * @param  string $addon_id    Addon identifier.
	 * @param  string $license_key License key for addon.
	 * @return array  Activation result.
	 */
	public function activate_addon( $addon_id, $license_key ) {
		if ( ! isset( $this->addon_registry[ $addon_id ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid addon identifier.', 'wpnlweb' ),
			);
		}

		$addon_config = $this->addon_registry[ $addon_id ];
		
		// Set EDD configuration for addon.
		$this->edd->set_store_config(
			$this->edd->get_store_url(),
			$addon_config['edd_item_name'],
			$addon_config['edd_item_id']
		);

		$result = $this->edd->activate_license( $license_key );

		if ( $result['success'] ) {
			// Store addon license key.
			$option_key = 'wpnlweb_addon_license_' . $addon_id;
			update_option( $option_key, $license_key );

			// Clear cache.
			$this->clear_addon_cache();

			/**
			 * Fires when addon is activated.
			 *
			 * @since 1.1.0
			 * @param string $addon_id Addon identifier.
			 * @param array  $result   Activation result.
			 */
			do_action( 'wpnlweb_addon_activated', $addon_id, $result );
		}

		return $result;
	}

	/**
	 * Deactivate addon license.
	 *
	 * @since  1.1.0
	 * @param  string $addon_id Addon identifier.
	 * @return array  Deactivation result.
	 */
	public function deactivate_addon( $addon_id ) {
		if ( ! isset( $this->addon_registry[ $addon_id ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid addon identifier.', 'wpnlweb' ),
			);
		}

		$option_key = 'wpnlweb_addon_license_' . $addon_id;
		$license_key = get_option( $option_key, '' );

		if ( empty( $license_key ) ) {
			return array(
				'success' => true,
				'message' => __( 'Addon license already inactive.', 'wpnlweb' ),
			);
		}

		$addon_config = $this->addon_registry[ $addon_id ];
		
		// Set EDD configuration for addon.
		$this->edd->set_store_config(
			$this->edd->get_store_url(),
			$addon_config['edd_item_name'],
			$addon_config['edd_item_id']
		);

		$result = $this->edd->deactivate_license( $license_key );

		// Always remove local license regardless of server response.
		delete_option( $option_key );
		$this->clear_addon_cache();

		/**
		 * Fires when addon is deactivated.
		 *
		 * @since 1.1.0
		 * @param string $addon_id Addon identifier.
		 * @param array  $result   Deactivation result.
		 */
		do_action( 'wpnlweb_addon_deactivated', $addon_id, $result );

		return $result;
	}

	/**
	 * Validate addon license with EDD.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $addon_id Addon identifier.
	 * @return bool   True if addon license is valid.
	 */
	private function validate_addon_license( $addon_id ) {
		$option_key = 'wpnlweb_addon_license_' . $addon_id;
		$license_key = get_option( $option_key, '' );

		if ( empty( $license_key ) ) {
			return false;
		}

		$addon_config = $this->addon_registry[ $addon_id ];
		
		// Set EDD configuration for addon.
		$this->edd->set_store_config(
			$this->edd->get_store_url(),
			$addon_config['edd_item_name'],
			$addon_config['edd_item_id']
		);

		$result = $this->edd->validate_license( $license_key );
		
		return isset( $result['success'] ) && $result['success'];
	}

	/**
	 * Clear addon cache.
	 *
	 * @since  1.1.0
	 */
	public function clear_addon_cache() {
		$this->active_addons = null;
		delete_transient( 'wpnlweb_active_addons' );
	}

	/**
	 * Handle addon activation event.
	 *
	 * @since  1.1.0
	 * @param  string $addon_id Addon identifier.
	 * @param  array  $result   Activation result.
	 */
	public function on_addon_activated( $addon_id, $result ) {
		error_log( sprintf(
			'WPNLWeb: Addon activated - %s for site %s',
			$addon_id,
			get_site_url()
		) );
	}

	/**
	 * Handle addon deactivation event.
	 *
	 * @since  1.1.0
	 * @param  string $addon_id Addon identifier.
	 * @param  array  $result   Deactivation result.
	 */
	public function on_addon_deactivated( $addon_id, $result ) {
		error_log( sprintf(
			'WPNLWeb: Addon deactivated - %s for site %s',
			$addon_id,
			get_site_url()
		) );
	}

	/**
	 * Get addon pricing information for upgrade prompts.
	 *
	 * @since  1.1.0
	 * @param  string $addon_id Addon identifier.
	 * @param  string $base_tier Current base tier for discount calculation.
	 * @return array  Pricing information.
	 */
	public function get_addon_pricing( $addon_id, $base_tier = 'pro' ) {
		$addon_config = $this->get_addon_config( $addon_id );
		
		if ( ! $addon_config ) {
			return array();
		}

		// TODO: Implement actual pricing API when EDD store is setup.
		$base_price = 99; // Placeholder pricing.
		$discount = 0;

		// Agency tier gets volume discounts.
		if ( 'agency' === $base_tier ) {
			$discount = ( 'automation_agents' === $addon_id ) ? 20 : 15;
		}

		$final_price = $base_price * ( 1 - $discount / 100 );

		return array(
			'addon_id'    => $addon_id,
			'name'        => $addon_config['name'],
			'base_price'  => $base_price,
			'discount'    => $discount,
			'final_price' => $final_price,
			'currency'    => 'USD',
			'purchase_url' => $this->get_addon_purchase_url( $addon_id, $base_tier ),
		);
	}

	/**
	 * Get addon purchase URL.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $addon_id  Addon identifier.
	 * @param  string $base_tier Current base tier.
	 * @return string Purchase URL.
	 */
	private function get_addon_purchase_url( $addon_id, $base_tier ) {
		// TODO: Replace with actual EDD store URLs.
		$base_url = 'https://wpnlweb.com/addons/' . $addon_id . '/';
		
		return add_query_arg( array(
			'tier' => $base_tier,
			'utm_source' => 'plugin',
			'utm_medium' => 'addon_prompt',
			'utm_campaign' => 'addon_purchase',
		), $base_url );
	}
} 