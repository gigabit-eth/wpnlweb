<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * If this file is called directly, abort.
 *
 * @link       https://wpnlweb.com
 * @since      1.0.0
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * The admin-specific functionality coordinator.
 *
 * Coordinates admin functionality by delegating to specialized components.
 * This class serves as the main entry point and orchestrator for all admin features.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin
 * @author     wpnlweb <hey@wpnlweb.com>
 */
class Wpnlweb_Admin {

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
	 * The settings manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wpnlweb_Admin_Settings    $settings    The settings manager.
	 */
	private $settings;

	/**
	 * The interface manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wpnlweb_Admin_Interface    $interface    The interface manager.
	 */
	private $interface;

	/**
	 * The assets manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wpnlweb_Admin_Assets    $assets    The assets manager.
	 */
	private $assets;

	/**
	 * The preview manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Wpnlweb_Admin_Preview    $preview    The preview manager.
	 */
	private $preview;

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

		// Load admin component classes.
		$this->load_dependencies();

		// Initialize admin components.
		$this->init_components();

		// Register admin hooks.
		$this->register_hooks();
	}

	/**
	 * Load admin component dependencies.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {
		// Load settings manager.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpnlweb-admin-settings.php';

		// Load interface manager.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpnlweb-admin-interface.php';

		// Load assets manager.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpnlweb-admin-assets.php';

		// Load preview manager.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpnlweb-admin-preview.php';
	}

	/**
	 * Initialize admin components.
	 *
	 * @since    1.0.0
	 */
	private function init_components() {
		// Initialize settings manager.
		$this->settings = new Wpnlweb_Admin_Settings( $this->plugin_name, $this->version );

		// Initialize assets manager.
		$this->assets = new Wpnlweb_Admin_Assets( $this->plugin_name, $this->version );

		// Initialize preview manager.
		$this->preview = new Wpnlweb_Admin_Preview( $this->plugin_name, $this->version );

		// Initialize interface manager (depends on other components).
		$this->interface = new Wpnlweb_Admin_Interface( 
			$this->plugin_name, 
			$this->version, 
			$this->settings,
			$this->preview
		);
	}

	/**
	 * Register admin hooks.
	 *
	 * @since    1.0.0
	 */
	private function register_hooks() {
		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_wpnlweb/wpnlweb.php', array( $this, 'add_settings_link' ) );

		// Register component hooks.
		$this->settings->register_hooks();
		$this->assets->register_hooks();
		$this->preview->register_hooks();
		$this->interface->register_hooks();
	}

	/**
	 * Add admin menu page.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'WPNLWeb Settings', 'wpnlweb' ),
			__( 'WPNLWeb', 'wpnlweb' ),
			'manage_options',
			'wpnlweb-settings',
			array( $this->interface, 'render_settings_page' )
		);
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @since    1.0.0
	 * @param    array $links Existing plugin action links.
	 * @return   array Modified plugin action links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=wpnlweb-settings' ) ),
			esc_html__( 'Settings', 'wpnlweb' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Get the settings manager instance.
	 *
	 * @since    1.0.0
	 * @return   Wpnlweb_Admin_Settings    The settings manager instance.
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get the interface manager instance.
	 *
	 * @since    1.0.0
	 * @return   Wpnlweb_Admin_Interface    The interface manager instance.
	 */
	public function get_interface() {
		return $this->interface;
	}

	/**
	 * Get the assets manager instance.
	 *
	 * @since    1.0.0
	 * @return   Wpnlweb_Admin_Assets    The assets manager instance.
	 */
	public function get_assets() {
		return $this->assets;
	}

	/**
	 * Get the preview manager instance.
	 *
	 * @since    1.0.0
	 * @return   Wpnlweb_Admin_Preview    The preview manager instance.
	 */
	public function get_preview() {
		return $this->preview;
	}

	/**
	 * Legacy method for backward compatibility.
	 * 
	 * @deprecated 1.1.0 Use get_assets()->enqueue_styles() instead.
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		_deprecated_function( __METHOD__, '1.1.0', 'Wpnlweb_Admin_Assets::enqueue_styles()' );
		$this->assets->enqueue_styles();
	}

	/**
	 * Legacy method for backward compatibility.
	 * 
	 * @deprecated 1.1.0 Use get_assets()->enqueue_scripts() instead.
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		_deprecated_function( __METHOD__, '1.1.0', 'Wpnlweb_Admin_Assets::enqueue_scripts()' );
		$this->assets->enqueue_scripts();
	}
}
