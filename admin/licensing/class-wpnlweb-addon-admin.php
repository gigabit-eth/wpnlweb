<?php
/**
 * Addon Admin Interface
 *
 * Provides admin interface for managing addons, license activation,
 * and credit tracking for the WPNLWeb addon system.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin/licensing
 * @since      1.1.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Addon Admin Class
 *
 * Handles admin interface for addon management including license
 * activation, deactivation, and status display.
 *
 * @since 1.1.0
 */
class Wpnlweb_Addon_Admin {

	/**
	 * Addon Manager instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_Addon_Manager
	 */
	private $addon_manager;

	/**
	 * License Manager instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_License_Manager
	 */
	private $license_manager;

	/**
	 * Feature Gates instance.
	 *
	 * @since  1.1.0
	 * @access private
	 * @var    Wpnlweb_Feature_Gates
	 */
	private $feature_gates;

	/**
	 * Initialize the Addon Admin.
	 *
	 * @since 1.1.0
	 * @param Wpnlweb_Addon_Manager  $addon_manager   Addon manager instance.
	 * @param Wpnlweb_License_Manager $license_manager License manager instance.
	 * @param Wpnlweb_Feature_Gates   $feature_gates   Feature gates instance.
	 */
	public function __construct( $addon_manager, $license_manager, $feature_gates ) {
		$this->addon_manager   = $addon_manager;
		$this->license_manager = $license_manager;
		$this->feature_gates   = $feature_gates;
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function setup_hooks() {
		// Admin menu hooks.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// AJAX hooks for addon management.
		add_action( 'wp_ajax_wpnlweb_activate_addon', array( $this, 'ajax_activate_addon' ) );
		add_action( 'wp_ajax_wpnlweb_deactivate_addon', array( $this, 'ajax_deactivate_addon' ) );
		add_action( 'wp_ajax_wpnlweb_check_addon_status', array( $this, 'ajax_check_addon_status' ) );

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Add admin menu item.
	 *
	 * @since  1.1.0
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'wpnlweb-settings',
			__( 'WPNLWeb Addons', 'wpnlweb' ),
			__( 'Addons', 'wpnlweb' ),
			'manage_options',
			'wpnlweb-addons',
			array( $this, 'render_addon_page' )
		);
	}

	/**
	 * Register admin settings.
	 *
	 * @since  1.1.0
	 */
	public function register_settings() {
		register_setting( 'wpnlweb_addon_settings', 'wpnlweb_addon_settings' );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since  1.1.0
	 * @param  string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'wpnlweb_page_wpnlweb-addons' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'wpnlweb-addon-admin',
			plugin_dir_url( __FILE__ ) . 'js/addon-admin.js',
			array( 'jquery' ),
			WPNLWEB_VERSION,
			true
		);

		wp_localize_script( 'wpnlweb-addon-admin', 'wpnlwebAddonAjax', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wpnlweb_addon_nonce' ),
			'strings' => array(
				'activating'   => __( 'Activating...', 'wpnlweb' ),
				'deactivating' => __( 'Deactivating...', 'wpnlweb' ),
				'error'        => __( 'An error occurred. Please try again.', 'wpnlweb' ),
				'success'      => __( 'Operation completed successfully.', 'wpnlweb' ),
			),
		) );

		wp_enqueue_style(
			'wpnlweb-addon-admin',
			plugin_dir_url( __FILE__ ) . 'css/addon-admin.css',
			array(),
			WPNLWEB_VERSION
		);
	}

	/**
	 * Render addon management page.
	 *
	 * @since  1.1.0
	 */
	public function render_addon_page() {
		$base_tier = $this->license_manager->get_tier();
		$active_addons = $this->addon_manager->get_active_addons();
		$available_addons = $this->get_available_addons( $base_tier );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WPNLWeb Addons', 'wpnlweb' ); ?></h1>
			
			<?php $this->render_addon_summary( $base_tier, $active_addons ); ?>
			
			<div class="wpnlweb-addon-grid">
				<?php foreach ( $available_addons as $addon_id => $addon_config ) : ?>
					<?php $this->render_addon_card( $addon_id, $addon_config, $active_addons ); ?>
				<?php endforeach; ?>
			</div>
			
			<?php if ( ! empty( $active_addons ) ) : ?>
				<?php $this->render_credit_dashboard( $active_addons ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render addon summary section.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $base_tier     Current base tier.
	 * @param  array  $active_addons Active addons.
	 */
	private function render_addon_summary( $base_tier, $active_addons ) {
		?>
		<div class="wpnlweb-addon-summary">
			<div class="addon-summary-card">
				<h3><?php esc_html_e( 'Your License', 'wpnlweb' ); ?></h3>
				<p class="tier-display">
					<?php
					printf(
						/* translators: %s: current license tier */
						esc_html__( 'Current Tier: %s', 'wpnlweb' ),
						'<strong>' . esc_html( ucfirst( $base_tier ) ) . '</strong>'
					);
					?>
				</p>
			</div>
			
			<div class="addon-summary-card">
				<h3><?php esc_html_e( 'Active Addons', 'wpnlweb' ); ?></h3>
				<p class="addon-count">
					<?php
					printf(
						/* translators: %d: number of active addons */
						esc_html( _n( '%d addon active', '%d addons active', count( $active_addons ), 'wpnlweb' ) ),
						count( $active_addons )
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render individual addon card.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $addon_id      Addon identifier.
	 * @param  array  $addon_config  Addon configuration.
	 * @param  array  $active_addons Active addons list.
	 */
	private function render_addon_card( $addon_id, $addon_config, $active_addons ) {
		$is_active = isset( $active_addons[ $addon_id ] );
		$base_tier = $this->license_manager->get_tier();
		$pricing = $this->addon_manager->get_addon_pricing( $addon_id, $base_tier );
		$can_activate = $this->can_activate_addon( $addon_id, $base_tier );

		?>
		<div class="wpnlweb-addon-card <?php echo $is_active ? 'active' : 'inactive'; ?>">
			<div class="addon-header">
				<h3><?php echo esc_html( $addon_config['name'] ); ?></h3>
				<span class="addon-status">
					<?php echo $is_active ? esc_html__( 'Active', 'wpnlweb' ) : esc_html__( 'Inactive', 'wpnlweb' ); ?>
				</span>
			</div>
			
			<div class="addon-description">
				<p><?php echo esc_html( $addon_config['description'] ); ?></p>
			</div>
			
			<div class="addon-features">
				<h4><?php esc_html_e( 'Features:', 'wpnlweb' ); ?></h4>
				<ul>
					<?php foreach ( $addon_config['features'] as $feature ) : ?>
						<li><?php echo esc_html( $this->get_feature_display_name( $feature ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			
			<?php if ( $is_active && 'credit_based' === $addon_config['type'] ) : ?>
				<?php $this->render_credit_info( $addon_id ); ?>
			<?php endif; ?>
			
			<div class="addon-actions">
				<?php if ( $is_active ) : ?>
					<button type="button" class="button addon-deactivate" data-addon="<?php echo esc_attr( $addon_id ); ?>">
						<?php esc_html_e( 'Deactivate License', 'wpnlweb' ); ?>
					</button>
				<?php else : ?>
					<?php if ( $can_activate ) : ?>
						<div class="addon-activation">
							<input type="text" 
								   class="addon-license-key" 
								   placeholder="<?php esc_attr_e( 'Enter license key...', 'wpnlweb' ); ?>"
								   data-addon="<?php echo esc_attr( $addon_id ); ?>">
							<button type="button" class="button button-primary addon-activate" data-addon="<?php echo esc_attr( $addon_id ); ?>">
								<?php esc_html_e( 'Activate', 'wpnlweb' ); ?>
							</button>
						</div>
					<?php else : ?>
						<div class="addon-upgrade-needed">
							<p><?php esc_html_e( 'Requires upgrade:', 'wpnlweb' ); ?> 
							   <strong><?php echo esc_html( ucfirst( $addon_config['required_tier'] ) ); ?></strong>
							</p>
							<a href="<?php echo esc_url( $pricing['purchase_url'] ); ?>" class="button button-secondary" target="_blank">
								<?php esc_html_e( 'Purchase Addon', 'wpnlweb' ); ?>
							</a>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render credit information for addon.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $addon_id Addon identifier.
	 */
	private function render_credit_info( $addon_id ) {
		$credit_balance = $this->addon_manager->get_credit_balance( $addon_id );
		$addon_config = $this->addon_manager->get_addon_config( $addon_id );

		?>
		<div class="addon-credits">
			<h4><?php esc_html_e( 'Credit Balance', 'wpnlweb' ); ?></h4>
			<div class="credit-balance">
				<span class="credit-amount"><?php echo esc_html( number_format( $credit_balance ) ); ?></span>
				<span class="credit-label"><?php esc_html_e( 'credits', 'wpnlweb' ); ?></span>
			</div>
			
			<?php if ( ! empty( $addon_config['credit_cost'] ) ) : ?>
				<div class="credit-costs">
					<h5><?php esc_html_e( 'Credit Costs:', 'wpnlweb' ); ?></h5>
					<ul>
						<?php foreach ( $addon_config['credit_cost'] as $operation => $cost ) : ?>
							<li>
								<?php echo esc_html( $this->get_operation_display_name( $operation ) ); ?>: 
								<strong><?php echo esc_html( $cost ); ?> <?php esc_html_e( 'credits', 'wpnlweb' ); ?></strong>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render credit dashboard for active addons.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $active_addons Active addons.
	 */
	private function render_credit_dashboard( $active_addons ) {
		$credit_based_addons = array_filter( $active_addons, function( $addon ) {
			return 'credit_based' === $addon['type'];
		} );

		if ( empty( $credit_based_addons ) ) {
			return;
		}

		?>
		<div class="wpnlweb-credit-dashboard">
			<h2><?php esc_html_e( 'Credit Dashboard', 'wpnlweb' ); ?></h2>
			
			<div class="credit-summary">
				<?php foreach ( $credit_based_addons as $addon_id => $addon_config ) : ?>
					<?php $balance = $this->addon_manager->get_credit_balance( $addon_id ); ?>
					<div class="credit-summary-item">
						<h4><?php echo esc_html( $addon_config['name'] ); ?></h4>
						<div class="credit-balance-large">
							<?php echo esc_html( number_format( $balance ) ); ?> 
							<span><?php esc_html_e( 'credits', 'wpnlweb' ); ?></span>
						</div>
						
						<?php if ( $balance < 100 ) : // Low credit warning ?>
							<div class="credit-warning">
								<p><?php esc_html_e( 'Credit balance is running low. Consider purchasing more credits.', 'wpnlweb' ); ?></p>
								<a href="#" class="button button-secondary">
									<?php esc_html_e( 'Purchase Credits', 'wpnlweb' ); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get available addons for current tier.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $base_tier Current base tier.
	 * @return array  Available addons configuration.
	 */
	private function get_available_addons( $base_tier ) {
		// Get all addons from addon manager registry.
		$reflection = new ReflectionClass( $this->addon_manager );
		$property = $reflection->getProperty( 'addon_registry' );
		$property->setAccessible( true );
		
		return $property->getValue( $this->addon_manager );
	}

	/**
	 * Check if addon can be activated for current tier.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $addon_id  Addon identifier.
	 * @param  string $base_tier Current base tier.
	 * @return bool   True if addon can be activated.
	 */
	private function can_activate_addon( $addon_id, $base_tier ) {
		$addon_config = $this->addon_manager->get_addon_config( $addon_id );
		
		if ( ! $addon_config ) {
			return false;
		}

		$tier_hierarchy = array( 'free' => 0, 'pro' => 1, 'enterprise' => 2, 'agency' => 3 );
		$required_tier = $addon_config['required_tier'];

		return $tier_hierarchy[ $base_tier ] >= $tier_hierarchy[ $required_tier ];
	}

	/**
	 * Get display name for feature.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $feature Feature identifier.
	 * @return string Feature display name.
	 */
	private function get_feature_display_name( $feature ) {
		$display_names = array(
			'content_automation'   => __( 'Content Automation', 'wpnlweb' ),
			'bulk_operations'      => __( 'Bulk Operations', 'wpnlweb' ),
			'workflow_triggers'    => __( 'Workflow Triggers', 'wpnlweb' ),
			'ai_writing'           => __( 'AI Writing', 'wpnlweb' ),
			'seo_optimization'     => __( 'SEO Optimization', 'wpnlweb' ),
			'content_enhancement'  => __( 'Content Enhancement', 'wpnlweb' ),
			'custom_reports'       => __( 'Custom Reports', 'wpnlweb' ),
			'data_export'          => __( 'Data Export', 'wpnlweb' ),
			'advanced_insights'    => __( 'Advanced Insights', 'wpnlweb' ),
			'real_time_analytics'  => __( 'Real-time Analytics', 'wpnlweb' ),
		);

		return isset( $display_names[ $feature ] ) ? $display_names[ $feature ] : ucwords( str_replace( '_', ' ', $feature ) );
	}

	/**
	 * Get display name for credit operation.
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  string $operation Operation identifier.
	 * @return string Operation display name.
	 */
	private function get_operation_display_name( $operation ) {
		$display_names = array(
			'content_generation' => __( 'Content Generation', 'wpnlweb' ),
			'bulk_operation'     => __( 'Bulk Operation', 'wpnlweb' ),
			'workflow_trigger'   => __( 'Workflow Trigger', 'wpnlweb' ),
			'generate_post'      => __( 'Generate Post', 'wpnlweb' ),
			'seo_optimization'   => __( 'SEO Optimization', 'wpnlweb' ),
			'content_rewrite'    => __( 'Content Rewrite', 'wpnlweb' ),
		);

		return isset( $display_names[ $operation ] ) ? $display_names[ $operation ] : ucwords( str_replace( '_', ' ', $operation ) );
	}

	/**
	 * Handle addon activation AJAX request.
	 *
	 * @since  1.1.0
	 */
	public function ajax_activate_addon() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wpnlweb_addon_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wpnlweb' ) ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wpnlweb' ) ) );
		}

		$addon_id = sanitize_text_field( $_POST['addon_id'] ?? '' );
		$license_key = sanitize_text_field( $_POST['license_key'] ?? '' );

		if ( empty( $addon_id ) || empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Addon ID and license key are required.', 'wpnlweb' ) ) );
		}

		$result = $this->addon_manager->activate_addon( $addon_id, $license_key );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Handle addon deactivation AJAX request.
	 *
	 * @since  1.1.0
	 */
	public function ajax_deactivate_addon() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wpnlweb_addon_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wpnlweb' ) ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wpnlweb' ) ) );
		}

		$addon_id = sanitize_text_field( $_POST['addon_id'] ?? '' );

		if ( empty( $addon_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Addon ID is required.', 'wpnlweb' ) ) );
		}

		$result = $this->addon_manager->deactivate_addon( $addon_id );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Handle addon status check AJAX request.
	 *
	 * @since  1.1.0
	 */
	public function ajax_check_addon_status() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wpnlweb_addon_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wpnlweb' ) ) );
		}

		$addon_id = sanitize_text_field( $_POST['addon_id'] ?? '' );

		if ( empty( $addon_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Addon ID is required.', 'wpnlweb' ) ) );
		}

		$is_active = $this->addon_manager->has_addon( $addon_id );
		$credit_balance = $is_active ? $this->addon_manager->get_credit_balance( $addon_id ) : 0;

		wp_send_json_success( array(
			'active' => $is_active,
			'credit_balance' => $credit_balance,
		) );
	}

	/**
	 * Display admin notices.
	 *
	 * @since  1.1.0
	 */
	public function display_admin_notices() {
		$screen = get_current_screen();
		
		if ( 'wpnlweb_page_wpnlweb-addons' !== $screen->id ) {
			return;
		}

		// Check for base license requirement.
		$base_tier = $this->license_manager->get_tier();
		
		if ( 'free' === $base_tier ) {
			?>
			<div class="notice notice-info">
				<p>
					<?php esc_html_e( 'Addons require a Pro license or higher. Upgrade your license to access powerful addon features!', 'wpnlweb' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnlweb-license' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Manage License', 'wpnlweb' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}
} 