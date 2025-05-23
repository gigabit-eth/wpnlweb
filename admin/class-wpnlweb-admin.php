<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://typewriter.sh
 * @since      1.0.0
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and admin functionality including
 * settings page for theme customization and custom CSS.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin
 * @author     TypeWriter <team@typewriter.sh>
 */
class Wpnlweb_Admin
{

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add settings page
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'init_settings'));
	}

	/**
	 * Add admin menu page
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu()
	{
		add_options_page(
			__('WPNLWeb Settings', 'wpnlweb'),
			__('WPNLWeb', 'wpnlweb'),
			'manage_options',
			'wpnlweb-settings',
			array($this, 'settings_page')
		);
	}

	/**
	 * Initialize settings
	 *
	 * @since    1.0.0
	 */
	public function init_settings()
	{
		// Register settings
		register_setting('wpnlweb_settings', 'wpnlweb_custom_css');
		register_setting('wpnlweb_settings', 'wpnlweb_theme_mode');
		register_setting('wpnlweb_settings', 'wpnlweb_primary_color');

		// Add a hook to clear caches when settings are saved
		add_action('update_option_wpnlweb_theme_mode', array($this, 'clear_style_caches'));
		add_action('update_option_wpnlweb_primary_color', array($this, 'clear_style_caches'));
		add_action('update_option_wpnlweb_custom_css', array($this, 'clear_style_caches'));

		// Add settings sections
		add_settings_section(
			'wpnlweb_theme_section',
			__('Theme Customization', 'wpnlweb'),
			array($this, 'theme_section_callback'),
			'wpnlweb-settings'
		);

		add_settings_section(
			'wpnlweb_css_section',
			__('Custom CSS', 'wpnlweb'),
			array($this, 'css_section_callback'),
			'wpnlweb-settings'
		);

		// Add settings fields
		add_settings_field(
			'wpnlweb_theme_mode',
			__('Theme Mode', 'wpnlweb'),
			array($this, 'theme_mode_callback'),
			'wpnlweb-settings',
			'wpnlweb_theme_section'
		);

		add_settings_field(
			'wpnlweb_primary_color',
			__('Primary Color', 'wpnlweb'),
			array($this, 'primary_color_callback'),
			'wpnlweb-settings',
			'wpnlweb_theme_section'
		);

		add_settings_field(
			'wpnlweb_custom_css',
			__('Custom CSS', 'wpnlweb'),
			array($this, 'custom_css_callback'),
			'wpnlweb-settings',
			'wpnlweb_css_section'
		);
	}

	/**
	 * Clear style caches when settings are updated
	 *
	 * @since    1.0.0
	 */
	public function clear_style_caches()
	{
		// Clear any object cache
		if (function_exists('wp_cache_flush')) {
			wp_cache_flush();
		}

		// If using a caching plugin, you might want to add hooks here
		do_action('wpnlweb_settings_updated');
	}

	/**
	 * Settings page HTML
	 *
	 * @since    1.0.0
	 */
	public function settings_page()
	{
?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields('wpnlweb_settings');
				do_settings_sections('wpnlweb-settings');
				submit_button(__('Save Settings', 'wpnlweb'));
				?>
			</form>

			<div class="wpnlweb-preview-section">
				<h2><?php _e('Live Preview', 'wpnlweb'); ?></h2>
				<p><?php _e('Use the shortcode <code>[wpnlweb]</code> to see your customizations in action.', 'wpnlweb'); ?></p>

				<h3><?php _e('CSS Custom Properties Reference', 'wpnlweb'); ?></h3>
				<p><?php _e('You can use these CSS custom properties in your custom CSS to maintain consistency:', 'wpnlweb'); ?></p>
				<pre style="background: #f1f1f1; padding: 15px; border-radius: 5px; font-size: 12px;">
--wpnlweb-primary-color: Main brand color
--wpnlweb-primary-hover: Hover state color
--wpnlweb-bg-primary: Main background color
--wpnlweb-bg-secondary: Secondary background
--wpnlweb-text-primary: Main text color
--wpnlweb-text-secondary: Secondary text color
--wpnlweb-border-radius: Border radius
--wpnlweb-spacing-sm: Small spacing (12px)
--wpnlweb-spacing-md: Medium spacing (20px)
--wpnlweb-spacing-lg: Large spacing (30px)
				</pre>
			</div>
		</div>
	<?php
	}

	/**
	 * Theme section callback
	 *
	 * @since    1.0.0
	 */
	public function theme_section_callback()
	{
		echo '<p>' . __('Customize the appearance of your WPNLWeb search forms.', 'wpnlweb') . '</p>';
	}

	/**
	 * CSS section callback
	 *
	 * @since    1.0.0
	 */
	public function css_section_callback()
	{
		echo '<p>' . __('Add custom CSS to further customize the appearance. This CSS will be applied to all WPNLWeb shortcodes.', 'wpnlweb') . '</p>';
	}

	/**
	 * Theme mode field callback
	 *
	 * @since    1.0.0
	 */
	public function theme_mode_callback()
	{
		$value = get_option('wpnlweb_theme_mode', 'auto');
	?>
		<select name="wpnlweb_theme_mode" id="wpnlweb_theme_mode">
			<option value="auto" <?php selected($value, 'auto'); ?>><?php _e('Auto (Follow System)', 'wpnlweb'); ?></option>
			<option value="light" <?php selected($value, 'light'); ?>><?php _e('Light Mode', 'wpnlweb'); ?></option>
			<option value="dark" <?php selected($value, 'dark'); ?>><?php _e('Dark Mode', 'wpnlweb'); ?></option>
		</select>
		<p class="description"><?php _e('Choose the theme mode for the search interface.', 'wpnlweb'); ?></p>
	<?php
	}

	/**
	 * Primary color field callback
	 *
	 * @since    1.0.0
	 */
	public function primary_color_callback()
	{
		$value = get_option('wpnlweb_primary_color', '#3b82f6');
	?>
		<input type="color" name="wpnlweb_primary_color" id="wpnlweb_primary_color" value="<?php echo esc_attr($value); ?>" />
		<input type="text" name="wpnlweb_primary_color_text" id="wpnlweb_primary_color_text" value="<?php echo esc_attr($value); ?>" placeholder="#3b82f6" style="margin-left: 10px; width: 100px;" />
		<p class="description"><?php _e('Choose the primary color for buttons and focus states.', 'wpnlweb'); ?></p>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const colorPicker = document.getElementById('wpnlweb_primary_color');
				const textInput = document.getElementById('wpnlweb_primary_color_text');

				colorPicker.addEventListener('change', function() {
					textInput.value = this.value;
				});

				textInput.addEventListener('change', function() {
					if (/^#[0-9A-F]{6}$/i.test(this.value)) {
						colorPicker.value = this.value;
					}
				});
			});
		</script>
	<?php
	}

	/**
	 * Custom CSS field callback
	 *
	 * @since    1.0.0
	 */
	public function custom_css_callback()
	{
		$value = get_option('wpnlweb_custom_css', '');
	?>
		<textarea name="wpnlweb_custom_css" id="wpnlweb_custom_css" rows="10" cols="80" style="width: 100%; font-family: monospace;"><?php echo esc_textarea($value); ?></textarea>
		<p class="description">
			<?php _e('Add custom CSS to override default styles. Example:', 'wpnlweb'); ?>
			<br>
			<code>
				.wpnlweb-search-container { border-radius: 20px; }<br>
				.wpnlweb-search-button { background: var(--wpnlweb-primary-color); }
			</code>
		</p>
<?php
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpnlweb_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpnlweb_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpnlweb-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpnlweb_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpnlweb_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpnlweb-admin.js', array('jquery'), $this->version, false);
	}
}
