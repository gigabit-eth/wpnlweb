<?php
/**
 * Feature Gates System
 *
 * Implements coarse-grained feature access control based on license tiers.
 * Provides server-side validation and WordPress capability integration.
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
 * Feature Gates Class
 *
 * Controls access to features based on license tiers and WordPress capabilities.
 * Implements coarse-grained gating for clear feature boundaries.
 *
 * @since 1.1.0
 */
class Wpnlweb_Feature_Gates {

	/**
	 * License Manager instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_License_Manager
	 */
	private $license_manager;

	/**
	 * Feature Registry instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_Feature_Registry
	 */
	private $registry;

	/**
	 * Denied features cache.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    array
	 */
	private $denied_cache = array();

	/**
	 * Initialize Feature Gates.
	 *
	 * @since 1.1.0
	 * @param Wpnlweb_License_Manager  $license_manager License manager instance.
	 * @param Wpnlweb_Feature_Registry $registry        Feature registry instance.
	 */
	public function __construct( $license_manager, $registry ) {
		$this->license_manager = $license_manager;
		$this->registry        = $registry;
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		// Feature access hooks.
		add_filter( 'wpnlweb_can_access_feature', array( $this, 'check_feature_access' ), 10, 2 );
		add_action( 'wpnlweb_feature_access_denied', array( $this, 'handle_access_denied' ), 10, 3 );

		// Admin hooks for feature management.
		add_action( 'admin_init', array( $this, 'register_feature_capabilities' ) );
		add_filter( 'user_has_cap', array( $this, 'filter_user_capabilities' ), 10, 4 );

		// AJAX hooks for feature validation.
		add_action( 'wp_ajax_wpnlweb_validate_feature', array( $this, 'ajax_validate_feature' ) );
		add_action( 'wp_ajax_nopriv_wpnlweb_validate_feature', array( $this, 'ajax_validate_feature' ) );
	}

	/**
	 * Check if current user can access specific feature.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature identifier to check.
	 * @param  int    $user_id Optional. User ID to check. Defaults to current user.
	 * @return bool   True if user can access feature.
	 */
	public function can_access_feature( $feature, $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Check cache first.
		$cache_key = $feature . '_' . $user_id;
		if ( isset( $this->denied_cache[ $cache_key ] ) ) {
			return ! $this->denied_cache[ $cache_key ];
		}

		// Check WordPress capability first.
		if ( ! $this->check_capability( $feature, $user_id ) ) {
			$this->denied_cache[ $cache_key ] = true;
			return false;
		}

		// Check license tier access.
		$has_access = $this->license_manager->validate_feature_access( $feature );

		if ( ! $has_access ) {
			$this->denied_cache[ $cache_key ] = true;
			
			/**
			 * Fires when feature access is denied.
			 *
			 * @since 1.1.0
			 * @param string $feature Feature that was denied.
			 * @param int    $user_id User ID that was denied.
			 * @param string $reason  Reason for denial.
			 */
			do_action( 'wpnlweb_feature_access_denied', $feature, $user_id, 'license_tier' );
		}

		return $has_access;
	}

	/**
	 * Require feature access or die with error.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature identifier to require.
	 * @param  string $message Optional. Custom error message.
	 */
	public function require_feature_access( $feature, $message = '' ) {
		if ( ! $this->can_access_feature( $feature ) ) {
			if ( empty( $message ) ) {
				$feature_info = $this->registry->get_feature_info( $feature );
				$required_tier = isset( $feature_info['required_tier'] ) ? $feature_info['required_tier'] : 'pro';
				
				$message = sprintf(
					/* translators: %1$s: feature name, %2$s: required tier */
					__( 'This feature (%1$s) requires a %2$s license or higher.', 'wpnlweb' ),
					$feature_info['name'] ?? $feature,
					ucfirst( $required_tier )
				);
			}

			wp_die( 
				esc_html( $message ),
				esc_html__( 'Feature Access Denied', 'wpnlweb' ),
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Get upgrade prompt for denied feature.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature identifier.
	 * @return array  Upgrade prompt information.
	 */
	public function get_upgrade_prompt( $feature ) {
		$feature_info = $this->registry->get_feature_info( $feature );
		$required_tier = isset( $feature_info['required_tier'] ) ? $feature_info['required_tier'] : 'pro';
		$current_tier = $this->license_manager->get_tier();

		return array(
			'feature'       => $feature,
			'feature_name'  => $feature_info['name'] ?? $feature,
			'current_tier'  => $current_tier,
			'required_tier' => $required_tier,
			'upgrade_url'   => $this->get_upgrade_url( $required_tier ),
			'message'       => $this->get_upgrade_message( $feature, $required_tier ),
		);
	}

	/**
	 * Display upgrade notice for feature.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature identifier.
	 * @param  array  $args    Optional. Display arguments.
	 */
	public function display_upgrade_notice( $feature, $args = array() ) {
		$prompt = $this->get_upgrade_prompt( $feature );
		
		$defaults = array(
			'type'        => 'notice',
			'dismissible' => true,
			'class'       => 'wpnlweb-upgrade-notice',
		);
		
		$args = wp_parse_args( $args, $defaults );

		$notice_class = sprintf( 
			'notice notice-%s %s %s',
			esc_attr( $args['type'] ),
			$args['dismissible'] ? 'is-dismissible' : '',
			esc_attr( $args['class'] )
		);

		?>
		<div class="<?php echo esc_attr( $notice_class ); ?>">
			<p>
				<?php echo esc_html( $prompt['message'] ); ?>
				<a href="<?php echo esc_url( $prompt['upgrade_url'] ); ?>" class="button button-primary">
					<?php 
					printf(
						/* translators: %s: required tier name */
						esc_html__( 'Upgrade to %s', 'wpnlweb' ),
						esc_html( ucfirst( $prompt['required_tier'] ) )
					);
					?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Filter WordPress user capabilities for features.
	 *
	 * @since  1.1.0
	 * @param  array $allcaps All capabilities of the user.
	 * @param  array $caps    Required capabilities.
	 * @param  array $args    Capability arguments.
	 * @param  WP_User $user  User object.
	 * @return array Modified capabilities.
	 */
	public function filter_user_capabilities( $allcaps, $caps, $args, $user ) {
		// Check for WPNLWeb feature capabilities.
		foreach ( $caps as $cap ) {
			if ( strpos( $cap, 'wpnlweb_' ) === 0 ) {
				$feature = str_replace( 'wpnlweb_', '', $cap );
				
				if ( $this->registry->is_registered_feature( $feature ) ) {
					$allcaps[ $cap ] = $this->license_manager->validate_feature_access( $feature );
				}
			}
		}

		return $allcaps;
	}

	/**
	 * Register feature capabilities with WordPress.
	 *
	 * @since  1.1.0
	 */
	public function register_feature_capabilities() {
		$features = $this->registry->get_all_features();
		
		foreach ( $features as $feature => $info ) {
			$capability = 'wpnlweb_' . $feature;
			
			// Add capability to administrator role if not exists.
			$admin_role = get_role( 'administrator' );
			if ( $admin_role && ! $admin_role->has_cap( $capability ) ) {
				$admin_role->add_cap( $capability );
			}
		}
	}

	/**
	 * Handle AJAX feature validation request.
	 *
	 * @since  1.1.0
	 */
	public function ajax_validate_feature() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wpnlweb_feature_validation' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wpnlweb' ), '', array( 'response' => 403 ) );
		}

		$feature = sanitize_text_field( $_POST['feature'] ?? '' );
		
		if ( empty( $feature ) ) {
			wp_send_json_error( array( 'message' => __( 'Feature not specified.', 'wpnlweb' ) ) );
		}

		$can_access = $this->can_access_feature( $feature );
		
		if ( $can_access ) {
			wp_send_json_success( array( 'access' => true ) );
		} else {
			$prompt = $this->get_upgrade_prompt( $feature );
			wp_send_json_error( array( 
				'access' => false,
				'prompt' => $prompt,
			) );
		}
	}

	/**
	 * Check WordPress capability for feature.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $feature Feature identifier.
	 * @param  int    $user_id User ID to check.
	 * @return bool   True if user has capability.
	 */
	private function check_capability( $feature, $user_id ) {
		$capability = 'wpnlweb_' . $feature;
		return user_can( $user_id, $capability );
	}

	/**
	 * Get upgrade URL for tier.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $tier Required tier.
	 * @return string Upgrade URL.
	 */
	private function get_upgrade_url( $tier ) {
		// TODO: Replace with actual upgrade URL once EDD store is setup.
		$base_url = 'https://wpnlweb.com/pricing/';
		
		return add_query_arg( array(
			'tier' => $tier,
			'utm_source' => 'plugin',
			'utm_medium' => 'upgrade_prompt',
			'utm_campaign' => 'feature_gate',
		), $base_url );
	}

	/**
	 * Get upgrade message for feature.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $feature      Feature identifier.
	 * @param  string $required_tier Required tier.
	 * @return string Upgrade message.
	 */
	private function get_upgrade_message( $feature, $required_tier ) {
		$feature_info = $this->registry->get_feature_info( $feature );
		$feature_name = $feature_info['name'] ?? $feature;

		return sprintf(
			/* translators: %1$s: feature name, %2$s: required tier */
			__( 'The %1$s feature requires a %2$s license or higher. Upgrade now to unlock this powerful functionality!', 'wpnlweb' ),
			$feature_name,
			ucfirst( $required_tier )
		);
	}

	/**
	 * Handle feature access denied event.
	 *
	 * @since  1.1.0
	 * @param  string $feature Feature that was denied.
	 * @param  int    $user_id User ID that was denied.
	 * @param  string $reason  Reason for denial.
	 */
	public function handle_access_denied( $feature, $user_id, $reason ) {
		// Log access denial for analytics.
		error_log( sprintf(
			'WPNLWeb: Feature access denied - Feature: %s, User: %d, Reason: %s',
			$feature,
			$user_id,
			$reason
		) );

		// Track for conversion analytics.
		$this->track_upgrade_opportunity( $feature, $user_id, $reason );
	}

	/**
	 * Track upgrade opportunity for analytics.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $feature Feature that was denied.
	 * @param  int    $user_id User ID that was denied.
	 * @param  string $reason  Reason for denial.
	 */
	private function track_upgrade_opportunity( $feature, $user_id, $reason ) {
		// Store upgrade opportunity for analytics.
		$opportunities = get_option( 'wpnlweb_upgrade_opportunities', array() );
		
		$opportunity = array(
			'feature'   => $feature,
			'user_id'   => $user_id,
			'reason'    => $reason,
			'timestamp' => time(),
			'site_url'  => get_site_url(),
		);

		$opportunities[] = $opportunity;
		
		// Keep only last 100 opportunities to avoid database bloat.
		if ( count( $opportunities ) > 100 ) {
			$opportunities = array_slice( $opportunities, -100 );
		}

		update_option( 'wpnlweb_upgrade_opportunities', $opportunities );
	}

	/**
	 * Get feature access statistics.
	 *
	 * @since  1.1.0
	 * @return array Feature access statistics.
	 */
	public function get_access_stats() {
		$opportunities = get_option( 'wpnlweb_upgrade_opportunities', array() );
		$stats = array(
			'total_denials' => count( $opportunities ),
			'features_denied' => array(),
			'recent_denials' => 0,
		);

		$week_ago = time() - WEEK_IN_SECONDS;

		foreach ( $opportunities as $opportunity ) {
			$feature = $opportunity['feature'];
			
			if ( ! isset( $stats['features_denied'][ $feature ] ) ) {
				$stats['features_denied'][ $feature ] = 0;
			}
			
			$stats['features_denied'][ $feature ]++;
			
			if ( $opportunity['timestamp'] > $week_ago ) {
				$stats['recent_denials']++;
			}
		}

		return $stats;
	}

	/**
	 * Clear denied features cache.
	 *
	 * @since  1.1.0
	 */
	public function clear_cache() {
		$this->denied_cache = array();
	}

	/**
	 * Check filter for feature access.
	 *
	 * @since  1.1.0
	 * @param  bool   $can_access Current access status.
	 * @param  string $feature    Feature identifier.
	 * @return bool   Modified access status.
	 */
	public function check_feature_access( $can_access, $feature ) {
		if ( $can_access ) {
			return $this->can_access_feature( $feature );
		}

		return $can_access;
	}
} 