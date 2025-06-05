<?php
/**
 * License Manager Core
 *
 * Orchestrates license validation, caching, and tier management for WPNLWeb.
 * Implements hybrid validation approach with background sync.
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
 * Core License Manager Class
 *
 * Manages license validation, caching, and tier enforcement across the plugin.
 * Supports multi-site installations and provides background sync capabilities.
 *
 * @since 1.1.0
 */
class Wpnlweb_License_Manager {

	/**
	 * License validator instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_License_Validator
	 */
	private $validator;

	/**
	 * License cache instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_License_Cache
	 */
	private $cache;

	/**
	 * License tiers instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_License_Tiers
	 */
	private $tiers;

	/**
	 * EDD integration instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_Edd_Integration
	 */
	private $edd;

	/**
	 * Addon Manager instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_Addon_Manager
	 */
	private $addon_manager;

	/**
	 * Current license data.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $current_license;

	/**
	 * Initialize the License Manager.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_components();
		$this->setup_hooks();
	}

	/**
	 * Load required dependencies.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-wpnlweb-license-validator.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wpnlweb-license-cache.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wpnlweb-license-tiers.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wpnlweb-edd-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wpnlweb-addon-manager.php';
	}

	/**
	 * Initialize component instances.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function init_components() {
		$this->cache         = new Wpnlweb_License_Cache();
		$this->edd           = new Wpnlweb_Edd_Integration();
		$this->validator     = new Wpnlweb_License_Validator( $this->cache, $this->edd );
		$this->tiers         = new Wpnlweb_License_Tiers();
		$this->addon_manager = new Wpnlweb_Addon_Manager( $this->edd, $this->cache );
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		// Background sync hooks.
		add_action( 'wpnlweb_license_sync', array( $this, 'background_sync' ) );
		add_action( 'init', array( $this, 'schedule_background_sync' ) );

		// License management hooks.
		add_action( 'wpnlweb_license_activated', array( $this, 'on_license_activated' ) );
		add_action( 'wpnlweb_license_deactivated', array( $this, 'on_license_deactivated' ) );

		// Multi-site hooks.
		if ( is_multisite() ) {
			add_action( 'wp_initialize_site', array( $this, 'on_site_created' ) );
			add_action( 'wp_delete_site', array( $this, 'on_site_deleted' ) );
		}
	}

	/**
	 * Get current license information.
	 *
	 * @since  1.1.0
	 * @return array License data including tier, status, and expiration.
	 */
	public function get_license() {
		if ( null === $this->current_license ) {
			$this->current_license = $this->load_license();
		}

		return $this->current_license;
	}

	/**
	 * Validate license for specific feature access.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature identifier to check access for.
	 * @return bool   True if license allows feature access.
	 */
	public function validate_feature_access( $feature ) {
		$license = $this->get_license();
		
		if ( empty( $license ) || 'active' !== $license['status'] ) {
			return $this->tiers->is_free_feature( $feature );
		}

		return $this->tiers->has_feature_access( $license['tier'], $feature );
	}

	/**
	 * Get license tier information.
	 *
	 * @since  1.1.0
	 * @return string Current license tier (free, pro, enterprise, agency).
	 */
	public function get_tier() {
		// Get current license status.
		$license = $this->validator->get_license_status();

		if ( ! $license || ! isset( $license['tier'] ) ) {
			return 'free';
		}

		return $license['tier'];
	}

	/**
	 * Check if license is valid and active.
	 *
	 * @since  1.1.0
	 * @return bool True if license is valid and active.
	 */
	public function is_valid() {
		$license = $this->get_license();
		return ! empty( $license ) && 'active' === $license['status'];
	}

	/**
	 * Activate license.
	 *
	 * @since  1.1.0
	 * @param  string $license_key License key.
	 * @return array  Activation result.
	 */
	public function activate_license( $license_key ) {
		if ( empty( $license_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'License key is required.', 'wpnlweb' ),
			);
		}

		$result = $this->validator->activate_license( $license_key );

		if ( $result['success'] ) {
			// Clear cache to force fresh validation.
			$this->cache->invalidate_license();
			
			/**
			 * Fires when license is successfully activated.
			 *
			 * @since 1.1.0
			 * @param string $license_key Activated license key.
			 * @param array  $result      Activation result.
			 */
			do_action( 'wpnlweb_license_activated', $license_key, $result );
		}

		return $result;
	}

	/**
	 * Deactivate license.
	 *
	 * @since  1.1.0
	 * @return array Deactivation result.
	 */
	public function deactivate_license() {
		$result = $this->validator->deactivate_license();

		if ( $result['success'] ) {
			// Clear all cached data.
			$this->cache->invalidate_license();
			$this->addon_manager->clear_addon_cache();
			
			/**
			 * Fires when license is successfully deactivated.
			 *
			 * @since 1.1.0
			 * @param array $result Deactivation result.
			 */
			do_action( 'wpnlweb_license_deactivated', $result );
		}

		return $result;
	}

	/**
	 * Load license data using hybrid validation approach.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return array License data or empty array if no license.
	 */
	private function load_license() {
		// Try cache first (5-minute cache).
		$license = $this->cache->get_license();
		
		if ( false !== $license ) {
			return $license;
		}

		// Cache miss - validate with EDD and cache result.
		$license = $this->validator->get_license_status();
		
		if ( ! empty( $license ) ) {
			$this->cache->set_license( $license );
		}

		return $license;
	}

	/**
	 * Schedule background sync for license status.
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public function schedule_background_sync() {
		if ( ! wp_next_scheduled( 'wpnlweb_license_sync' ) ) {
			wp_schedule_event( time(), 'hourly', 'wpnlweb_license_sync' );
		}
	}

	/**
	 * Perform background license sync.
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public function background_sync() {
		$current_license = $this->cache->get_license();
		
		if ( false === $current_license ) {
			return; // No license to sync.
		}

		// Validate current license status with EDD.
		$updated_license = $this->validator->get_license_status(); // Get fresh status
		
		if ( ! empty( $updated_license ) && $updated_license !== $current_license ) {
			$this->cache->set_license( $updated_license );
			
			/**
			 * Fires when license is updated via background sync.
			 *
			 * @since 1.1.0
			 * @param array $updated_license Updated license data.
			 * @param array $current_license Previous license data.
			 */
			do_action( 'wpnlweb_license_updated', $updated_license, $current_license );
		}
	}

	/**
	 * Handle license activation event.
	 *
	 * @since  1.1.0
	 * @param  array $result License activation result.
	 */
	public function on_license_activated( $result ) {
		// Log license activation.
		error_log( sprintf( 
			'WPNLWeb: License activated for site %s - Tier: %s', 
			get_site_url(), 
			isset( $result['tier'] ) ? $result['tier'] : 'unknown'
		) );
	}

	/**
	 * Handle license deactivation event.
	 *
	 * @since  1.1.0
	 * @param  array $result License deactivation result.
	 */
	public function on_license_deactivated( $result ) {
		// Log license deactivation.
		error_log( sprintf( 
			'WPNLWeb: License deactivated for site %s', 
			get_site_url()
		) );
	}

	/**
	 * Handle new site creation in multisite.
	 *
	 * @since  1.1.0
	 * @param  WP_Site $new_site New site object.
	 */
	public function on_site_created( $new_site ) {
		// Inherit network license if available.
		if ( is_network_admin() && $this->is_valid() ) {
			switch_to_blog( $new_site->blog_id );
			$this->cache->invalidate_license();
			restore_current_blog();
		}
	}

	/**
	 * Handle site deletion in multisite.
	 *
	 * @since  1.1.0
	 * @param  WP_Site $old_site Deleted site object.
	 */
	public function on_site_deleted( $old_site ) {
		// Clean up license cache for deleted site.
		switch_to_blog( $old_site->blog_id );
		$this->cache->invalidate_license();
		restore_current_blog();
	}

	/**
	 * Get license statistics for admin dashboard.
	 *
	 * @since  1.1.0
	 * @return array License statistics and usage information.
	 */
	public function get_license_stats() {
		$license = $this->get_license();
		$stats   = array(
			'tier'         => $this->get_tier(),
			'status'       => isset( $license['status'] ) ? $license['status'] : 'inactive',
			'expires_at'   => isset( $license['expires_at'] ) ? $license['expires_at'] : null,
			'sites_used'   => 1,
			'sites_limit'  => $this->tiers->get_sites_limit( $this->get_tier() ),
			'features'     => $this->tiers->get_tier_features( $this->get_tier() ),
		);

		if ( is_multisite() && is_network_admin() ) {
			$stats['sites_used'] = get_blog_count();
		}

		return $stats;
	}

	/**
	 * Get addon manager instance.
	 *
	 * @since  1.1.0
	 * @return Wpnlweb_Addon_Manager Addon manager instance.
	 */
	public function get_addon_manager() {
		return $this->addon_manager;
	}

	/**
	 * Get current license status.
	 *
	 * @since  1.1.0
	 * @return array License status information.
	 */
	public function get_license_status() {
		// Try cache first.
		$cached_license = $this->cache->get_license();
		
		if ( false !== $cached_license ) {
			return $cached_license;
		}

		// Get fresh status from validator.
		$status = $this->validator->get_license_status();
		
		// Cache the status.
		if ( ! empty( $status ) ) {
			$this->cache->set_license( $status );
		}

		return $status;
	}

	/**
	 * Refresh license from server.
	 *
	 * @since  1.1.0
	 * @return array Updated license data.
	 */
	public function refresh_license() {
		// Clear cache first.
		$this->cache->invalidate_license();
		
		// Force fresh validation.
		$updated_license = $this->validator->get_license_status(); // Get fresh status

		if ( ! empty( $updated_license ) ) {
			$this->cache->set_license( $updated_license );
			$this->current_license = $updated_license;

			/**
			 * Fires when license is refreshed.
			 *
			 * @since 1.1.0
			 * @param array $license_data Updated license data.
			 */
			do_action( 'wpnlweb_license_refreshed', $updated_license );
		}

		return $updated_license;
	}
} 