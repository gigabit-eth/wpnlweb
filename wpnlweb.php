<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpnlweb.com
 * @since             1.0.0
 * @package           Wpnlweb
 *
 * @wordpress-plugin
 * Plugin Name:       WPNLWeb
 * Plugin URI:        https://wpnlweb.com
 * Description:       Turn your WordPress site into a natural language interface for users, and AI agents using Microsoft's NLWeb Protocol.
 * Version:           1.0.2
 * Author:            WPNLWeb
 * Author URI:        https://wpnlweb.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpnlweb
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WPNLWEB_VERSION', '1.0.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpnlweb-activator.php
 */
function activate_wpnlweb() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpnlweb-activator.php';
	Wpnlweb_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpnlweb-deactivator.php
 */
function deactivate_wpnlweb() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpnlweb-deactivator.php';
	Wpnlweb_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpnlweb' );
register_deactivation_hook( __FILE__, 'deactivate_wpnlweb' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpnlweb.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpnlweb() {

	$plugin = new Wpnlweb();
	$plugin->run();
}
run_wpnlweb();
