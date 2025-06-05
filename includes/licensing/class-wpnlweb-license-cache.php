<?php
/**
 * License Cache System
 *
 * Manages WordPress transient-based caching for license validation with performance
 * optimization, memory usage management, and cache warming strategies.
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
 * License Cache Class
 *
 * Handles license caching using WordPress transients with intelligent cache
 * management and performance optimization features.
 *
 * @since 1.1.0
 */
class Wpnlweb_License_Cache {

	/**
	 * Cache key prefix.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    string
	 */
	private $cache_prefix = 'wpnlweb_license_';

	/**
	 * Default cache duration (5 minutes).
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    int
	 */
	private $cache_duration = 300;

	/**
	 * Cache statistics.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $stats = array(
		'hits'   => 0,
		'misses' => 0,
		'sets'   => 0,
		'deletes' => 0,
	);

	/**
	 * Initialize the License Cache.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->setup_hooks();
		$this->load_cache_stats();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		// Cache warming hooks.
		add_action( 'wpnlweb_warm_license_cache', array( $this, 'warm_cache' ) );
		add_action( 'init', array( $this, 'schedule_cache_warming' ) );

		// Cache management hooks.
		add_action( 'wpnlweb_license_updated', array( $this, 'update_cache' ), 10, 2 );
		add_action( 'shutdown', array( $this, 'save_cache_stats' ) );

		// Multisite hooks.
		if ( is_multisite() ) {
			add_action( 'switch_blog', array( $this, 'clear_blog_cache' ) );
		}
	}

	/**
	 * Get cached license data.
	 *
	 * @since  1.1.0
	 * @return array|false License data or false if not cached.
	 */
	public function get_license() {
		$cache_key = $this->get_cache_key();
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			$this->stats['hits']++;
			
			// Validate cache structure.
			if ( $this->is_valid_cache_data( $cached_data ) ) {
				return $cached_data;
			} else {
				// Invalid cache data, delete it.
				$this->invalidate_license();
			}
		}

		$this->stats['misses']++;
		return false;
	}

	/**
	 * Cache license data.
	 *
	 * @since  1.1.0
	 * @param  array $license_data License data to cache.
	 * @return bool  True if cached successfully.
	 */
	public function set_license( $license_data ) {
		if ( empty( $license_data ) || ! is_array( $license_data ) ) {
			return false;
		}

		// Add cache metadata.
		$license_data['cached_at'] = time();
		$license_data['cache_version'] = $this->get_cache_version();

		$cache_key = $this->get_cache_key();
		$duration = $this->get_cache_duration( $license_data );

		$result = set_transient( $cache_key, $license_data, $duration );

		if ( $result ) {
			$this->stats['sets']++;
			
			/**
			 * Fires when license is cached.
			 *
			 * @since 1.1.0
			 * @param array $license_data Cached license data.
			 * @param int   $duration     Cache duration in seconds.
			 */
			do_action( 'wpnlweb_license_cached', $license_data, $duration );
		}

		return $result;
	}

	/**
	 * Invalidate license cache.
	 *
	 * @since  1.1.0
	 * @return bool True if invalidated successfully.
	 */
	public function invalidate_license() {
		$cache_key = $this->get_cache_key();
		$result = delete_transient( $cache_key );

		if ( $result ) {
			$this->stats['deletes']++;
			
			/**
			 * Fires when license cache is invalidated.
			 *
			 * @since 1.1.0
			 * @param string $cache_key Invalidated cache key.
			 */
			do_action( 'wpnlweb_license_cache_invalidated', $cache_key );
		}

		return $result;
	}

	/**
	 * Update cached license data.
	 *
	 * @since  1.1.0
	 * @param  array $new_license New license data.
	 * @param  array $old_license Previous license data.
	 */
	public function update_cache( $new_license, $old_license ) {
		// Only update if data has actually changed.
		if ( $this->license_data_changed( $new_license, $old_license ) ) {
			$this->set_license( $new_license );
		}
	}

	/**
	 * Warm cache by pre-loading license data.
	 *
	 * @since  1.1.0
	 * @return bool True if warming was successful.
	 */
	public function warm_cache() {
		// Skip if cache is already warm.
		if ( false !== $this->get_license() ) {
			return true;
		}

		// Trigger license validation to warm cache.
		$license_manager = $this->get_license_manager();
		if ( $license_manager ) {
			$license_data = $license_manager->get_license();
			return ! empty( $license_data );
		}

		return false;
	}

	/**
	 * Schedule cache warming.
	 *
	 * @since  1.1.0
	 */
	public function schedule_cache_warming() {
		if ( ! wp_next_scheduled( 'wpnlweb_warm_license_cache' ) ) {
			// Warm cache every 4 minutes (before 5-minute expiry).
			wp_schedule_event( time() + 240, 'wpnlweb_cache_warm', 'wpnlweb_warm_license_cache' );
		}
	}

	/**
	 * Clear cache for specific blog in multisite.
	 *
	 * @since  1.1.0
	 * @param  int $blog_id Blog ID to clear cache for.
	 */
	public function clear_blog_cache( $blog_id = null ) {
		if ( ! is_multisite() ) {
			return;
		}

		if ( null === $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		$cache_key = $this->get_cache_key( $blog_id );
		delete_transient( $cache_key );
	}

	/**
	 * Get cache statistics.
	 *
	 * @since  1.1.0
	 * @return array Cache performance statistics.
	 */
	public function get_stats() {
		$total_requests = $this->stats['hits'] + $this->stats['misses'];
		$hit_rate = $total_requests > 0 ? ( $this->stats['hits'] / $total_requests ) * 100 : 0;

		return array(
			'hits'        => $this->stats['hits'],
			'misses'      => $this->stats['misses'],
			'sets'        => $this->stats['sets'],
			'deletes'     => $this->stats['deletes'],
			'hit_rate'    => round( $hit_rate, 2 ),
			'total_requests' => $total_requests,
		);
	}

	/**
	 * Clear all cache statistics.
	 *
	 * @since  1.1.0
	 */
	public function clear_stats() {
		$this->stats = array(
			'hits'   => 0,
			'misses' => 0,
			'sets'   => 0,
			'deletes' => 0,
		);
		
		delete_option( 'wpnlweb_cache_stats' );
	}

	/**
	 * Get cache key for current site.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  int $blog_id Optional. Blog ID for multisite.
	 * @return string Cache key.
	 */
	private function get_cache_key( $blog_id = null ) {
		if ( is_multisite() ) {
			$blog_id = $blog_id ?: get_current_blog_id();
			return $this->cache_prefix . 'site_' . $blog_id;
		}

		return $this->cache_prefix . 'single_site';
	}

	/**
	 * Get cache duration based on license data.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $license_data License data.
	 * @return int   Cache duration in seconds.
	 */
	private function get_cache_duration( $license_data ) {
		$status = $license_data['status'] ?? 'inactive';

		// Cache active licenses longer than error states.
		switch ( $status ) {
			case 'active':
				return $this->cache_duration; // 5 minutes.
			case 'expired':
				return $this->cache_duration / 2; // 2.5 minutes.
			case 'error':
				return 60; // 1 minute for error states.
			default:
				return $this->cache_duration;
		}
	}

	/**
	 * Check if cache data is valid.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $cache_data Cached data.
	 * @return bool  True if valid.
	 */
	private function is_valid_cache_data( $cache_data ) {
		if ( ! is_array( $cache_data ) ) {
			return false;
		}

		// Check required fields.
		$required_fields = array( 'status', 'tier', 'cached_at' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $cache_data[ $field ] ) ) {
				return false;
			}
		}

		// Check cache version compatibility.
		$cache_version = $cache_data['cache_version'] ?? 1;
		if ( $cache_version !== $this->get_cache_version() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if license data has changed.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $new_license New license data.
	 * @param  array $old_license Old license data.
	 * @return bool  True if data has changed.
	 */
	private function license_data_changed( $new_license, $old_license ) {
		// Compare key fields that matter for caching.
		$compare_fields = array( 'status', 'tier', 'expires_at', 'sites_limit' );

		foreach ( $compare_fields as $field ) {
			$new_value = $new_license[ $field ] ?? null;
			$old_value = $old_license[ $field ] ?? null;

			if ( $new_value !== $old_value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get current cache version.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return int Cache version.
	 */
	private function get_cache_version() {
		return 1; // Increment when cache structure changes.
	}

	/**
	 * Load cache statistics from database.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function load_cache_stats() {
		$saved_stats = get_option( 'wpnlweb_cache_stats', array() );
		$this->stats = wp_parse_args( $saved_stats, $this->stats );
	}

	/**
	 * Save cache statistics to database.
	 *
	 * @since  1.1.0
	 */
	public function save_cache_stats() {
		update_option( 'wpnlweb_cache_stats', $this->stats );
	}

	/**
	 * Get license manager instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @return Wpnlweb_License_Manager|null License manager instance or null.
	 */
	private function get_license_manager() {
		// Avoid circular dependency by checking global instance.
		global $wpnlweb_license_manager;
		return $wpnlweb_license_manager ?? null;
	}

	/**
	 * Register custom cron interval for cache warming.
	 *
	 * @since  1.1.0
	 * @param  array $schedules Existing cron schedules.
	 * @return array Modified schedules.
	 */
	public function add_cache_warm_interval( $schedules ) {
		$schedules['wpnlweb_cache_warm'] = array(
			'interval' => 240, // 4 minutes.
			'display'  => __( 'WPNLWeb Cache Warming', 'wpnlweb' ),
		);

		return $schedules;
	}
} 