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
 * modern tabbed settings page for theme customization and custom CSS.
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

		// Add admin scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

		// AJAX handlers for live preview
		add_action('wp_ajax_wpnlweb_preview_shortcode', array($this, 'handle_preview_shortcode'));

		// Add settings link to plugins page
		add_filter('plugin_action_links_wpnlweb/wpnlweb.php', array($this, 'add_settings_link'));
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
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since    1.0.0
	 */
	public function enqueue_admin_assets($hook)
	{
		// Only load on our settings page
		if ($hook !== 'settings_page_wpnlweb-settings') {
			return;
		}

		// Enqueue admin CSS
		$css_file_path = plugin_dir_path(__FILE__) . 'css/wpnlweb-admin.css';
		$css_file_url = plugin_dir_url(__FILE__) . 'css/wpnlweb-admin.css';

		if (file_exists($css_file_path)) {
			wp_enqueue_style(
				$this->plugin_name . '-admin',
				$css_file_url,
				array(),
				$this->version,
				'all'
			);
		} else {
			// Fallback: Enqueue minimal inline styles if CSS file is missing
			add_action('admin_head', array($this, 'enqueue_fallback_styles'));
		}

		// Enqueue admin JS
		$js_file_path = plugin_dir_path(__FILE__) . 'js/wpnlweb-admin.js';
		$js_file_url = plugin_dir_url(__FILE__) . 'js/wpnlweb-admin.js';

		if (file_exists($js_file_path)) {
			wp_enqueue_script(
				$this->plugin_name . '-admin',
				$js_file_url,
				array('jquery', 'wp-color-picker'),
				$this->version,
				true
			);

			// Enqueue WordPress color picker
			wp_enqueue_style('wp-color-picker');

			// Localize script for AJAX
			wp_localize_script($this->plugin_name . '-admin', 'wpnlweb_admin', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('wpnlweb_admin_nonce')
			));
		} else {
			// Fallback: Enqueue minimal inline JS if JS file is missing
			add_action('admin_footer', array($this, 'enqueue_fallback_scripts'));
		}
	}

	/**
	 * Enqueue fallback inline styles when CSS file is missing
	 *
	 * @since    1.0.0
	 */
	public function enqueue_fallback_styles()
	{
		echo '<style type="text/css">
			.wpnlweb-admin-wrapper { 
				background: #fff; 
				border: 1px solid #ccd0d4; 
				border-radius: 4px; 
				margin: 20px 0; 
				padding: 20px; 
			}
			.wpnlweb-admin-header h1 { 
				margin: 0 0 10px 0; 
				font-size: 24px; 
			}
			.wpnlweb-admin-container { 
				display: flex; 
				gap: 20px; 
			}
			.wpnlweb-admin-sidebar { 
				width: 200px; 
				background: #f9f9f9; 
				padding: 15px; 
				border-radius: 4px; 
			}
			.wpnlweb-nav-item { 
				display: block; 
				padding: 8px 12px; 
				margin: 2px 0; 
				text-decoration: none; 
				border-radius: 3px; 
			}
			.wpnlweb-nav-item.active { 
				background: #0073aa; 
				color: #fff; 
			}
			.wpnlweb-admin-main { 
				flex: 1; 
			}
			.wpnlweb-tab-content { 
				display: none; 
			}
			.wpnlweb-tab-content.active { 
				display: block; 
			}
			.wpnlweb-setting-group { 
				margin: 20px 0; 
				padding: 15px; 
				background: #f9f9f9; 
				border-radius: 4px; 
			}
			.wpnlweb-color-picker, .wpnlweb-color-text { 
				margin: 5px; 
			}
			.wpnlweb-preset-color { 
				display: inline-block; 
				width: 30px; 
				height: 30px; 
				margin: 3px; 
				border-radius: 3px; 
				cursor: pointer; 
			}
		</style>';
	}

	/**
	 * Enqueue fallback inline scripts when JS file is missing
	 *
	 * @since    1.0.0
	 */
	public function enqueue_fallback_scripts()
	{
		echo '<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Basic tab functionality
				$(".wpnlweb-nav-item").on("click", function(e) {
					e.preventDefault();
					var tabId = $(this).data("tab");
					$(".wpnlweb-nav-item").removeClass("active");
					$(this).addClass("active");
					$(".wpnlweb-tab-content").removeClass("active");
					$("#" + tabId + "-tab").addClass("active");
				});
				
				// Basic color picker sync
				$("#wpnlweb_primary_color").on("change", function() {
					$("#wpnlweb_primary_color_text").val($(this).val());
				});
				
				$("#wpnlweb_primary_color_text").on("change", function() {
					$("#wpnlweb_primary_color").val($(this).val());
				});
			});
		</script>';
	}

	/**
	 * Settings page HTML with modern tabbed interface
	 *
	 * @since    1.0.0
	 */
	public function settings_page()
	{
		// Get current settings
		$theme_mode = get_option('wpnlweb_theme_mode', 'auto');
		$primary_color = get_option('wpnlweb_primary_color', '#3b82f6');
		$custom_css = get_option('wpnlweb_custom_css', '');

		// Preset colors for color picker
		$preset_colors = array(
			'#3b82f6', // Blue (default)
			'#ef4444', // Red
			'#10b981', // Green  
			'#f59e0b', // Orange
			'#8b5cf6', // Purple
			'#06b6d4', // Teal
			'#f97316', // Orange variant
			'#84cc16'  // Lime
		);

		// Diagnostic information for troubleshooting
		$css_file_path = plugin_dir_path(__FILE__) . 'css/wpnlweb-admin.css';
		$css_file_url = plugin_dir_url(__FILE__) . 'css/wpnlweb-admin.css';
		$js_file_path = plugin_dir_path(__FILE__) . 'js/wpnlweb-admin.js';
		$js_file_url = plugin_dir_url(__FILE__) . 'js/wpnlweb-admin.js';

		// Check if files exist
		$css_exists = file_exists($css_file_path);
		$js_exists = file_exists($js_file_path);

		// Show diagnostic info if styles aren't loading properly
		$show_diagnostic = !$css_exists || isset($_GET['debug']);
?>

		<div class="wrap">
			<!-- Standard WordPress admin page title -->
			<h1>
				<span class="wpnlweb-admin-icon">⚙️</span>
				<?php echo esc_html(get_admin_page_title()); ?>
				<?php if (!$css_exists): ?><span style="color: #dc3545; font-size: 16px; font-weight: normal;"> - Missing Styles</span><?php endif; ?>
			</h1>

			<!-- WordPress admin notices will appear here automatically -->

			<?php if ($show_diagnostic): ?>
				<!-- Diagnostic Information - WordPress standard notice -->
				<div class="notice notice-warning">
					<h3 style="margin-top: 0;">🔍 WPNLWeb Diagnostic Information</h3>
					<p><strong>CSS File Path:</strong> <code><?php echo esc_html($css_file_path); ?></code></p>
					<p><strong>CSS File URL:</strong> <code><?php echo esc_html($css_file_url); ?></code></p>
					<p><strong>CSS File Exists:</strong> <?php echo $css_exists ? '✅ Yes' : '❌ No'; ?></p>

					<p><strong>JS File Path:</strong> <code><?php echo esc_html($js_file_path); ?></code></p>
					<p><strong>JS File URL:</strong> <code><?php echo esc_html($js_file_url); ?></code></p>
					<p><strong>JS File Exists:</strong> <?php echo $js_exists ? '✅ Yes' : '❌ No'; ?></p>

					<p><strong>Plugin Dir Path:</strong> <code><?php echo esc_html(plugin_dir_path(__FILE__)); ?></code></p>
					<p><strong>Plugin Dir URL:</strong> <code><?php echo esc_html(plugin_dir_url(__FILE__)); ?></code></p>

					<p><strong>Current Hook:</strong> <code><?php echo esc_html(current_filter()); ?></code></p>
					<p><strong>WordPress Version:</strong> <code><?php echo esc_html(get_bloginfo('version')); ?></code></p>

					<?php if (!$css_exists): ?>
						<div class="notice notice-error inline">
							<p><strong>⚠️ CSS File Missing:</strong> The admin CSS file is not found at the expected location.
								Please check your file upload and ensure all plugin files were transferred correctly.</p>
						</div>
					<?php endif; ?>

					<?php if (!$js_exists): ?>
						<div class="notice notice-error inline">
							<p><strong>⚠️ JS File Missing:</strong> The admin JavaScript file is not found at the expected location.
								Please check your file upload and ensure all plugin files were transferred correctly.</p>
						</div>
					<?php endif; ?>

					<p><em>To hide this diagnostic info once files are working, remove <code>?debug</code> from the URL.</em></p>
				</div>
			<?php endif; ?>

			<!-- Custom Admin Interface starts here -->
			<div class="wpnlweb-admin-wrapper" <?php if (!$css_exists): ?> style="border: 2px solid #dc3545; background: #fff5f5;" <?php endif; ?>>
				<div class="wpnlweb-admin-header" <?php if (!$css_exists): ?> style="background: #fee; padding: 20px; border-bottom: 1px solid #fcc;" <?php endif; ?>>
					<p class="wpnlweb-admin-subtitle" <?php if (!$css_exists): ?> style="color: #666; margin: 5px 0 0 0;" <?php endif; ?>>
						<?php _e('Customize the appearance of your WPNLWeb search forms and interface.', 'wpnlweb'); ?>
					</p>
				</div>

				<div class="wpnlweb-admin-container">
					<!-- Sidebar Navigation -->
					<div class="wpnlweb-admin-sidebar">
						<div class="wpnlweb-sidebar-header">
							<h2><?php _e('Settings', 'wpnlweb'); ?></h2>
							<p><?php _e('Configure your preferences', 'wpnlweb'); ?></p>
						</div>

						<nav class="wpnlweb-sidebar-nav">
							<a href="#theme" class="wpnlweb-nav-item active" data-tab="theme">
								<span class="wpnlweb-nav-icon">🎨</span>
								<?php _e('Theme', 'wpnlweb'); ?>
							</a>
							<a href="#custom-css" class="wpnlweb-nav-item" data-tab="custom-css">
								<span class="wpnlweb-nav-icon">📝</span>
								<?php _e('Custom CSS', 'wpnlweb'); ?>
							</a>
							<a href="#live-preview" class="wpnlweb-nav-item" data-tab="live-preview">
								<span class="wpnlweb-nav-icon">👁️</span>
								<?php _e('Live Preview', 'wpnlweb'); ?>
							</a>
						</nav>
					</div>

					<!-- Main Content Area -->
					<div class="wpnlweb-admin-main">
						<form action="options.php" method="post" id="wpnlweb-settings-form">
							<?php settings_fields('wpnlweb_settings'); ?>

							<!-- Theme Tab -->
							<div id="theme-tab" class="wpnlweb-tab-content active">
								<div class="wpnlweb-tab-header">
									<h2>
										<span class="wpnlweb-tab-icon">🎨</span>
										<?php _e('Theme Customization', 'wpnlweb'); ?>
									</h2>
									<p><?php _e('Customize the appearance of your WPNLWeb search forms.', 'wpnlweb'); ?></p>
								</div>

								<div class="wpnlweb-setting-group">
									<label class="wpnlweb-setting-label">
										<?php _e('Theme Mode', 'wpnlweb'); ?>
									</label>
									<p class="wpnlweb-setting-description">
										<?php _e('Choose the theme mode for the search interface.', 'wpnlweb'); ?>
									</p>
									<select name="wpnlweb_theme_mode" id="wpnlweb_theme_mode" class="wpnlweb-select">
										<option value="auto" <?php selected($theme_mode, 'auto'); ?>>
											☀️ <?php _e('Auto (Follow System)', 'wpnlweb'); ?>
										</option>
										<option value="light" <?php selected($theme_mode, 'light'); ?>>
											☀️ <?php _e('Light Mode', 'wpnlweb'); ?>
										</option>
										<option value="dark" <?php selected($theme_mode, 'dark'); ?>>
											🌙 <?php _e('Dark Mode', 'wpnlweb'); ?>
										</option>
									</select>
								</div>

								<div class="wpnlweb-setting-group">
									<label class="wpnlweb-setting-label">
										<?php _e('Primary Color', 'wpnlweb'); ?>
									</label>
									<p class="wpnlweb-setting-description">
										<?php _e('Choose the primary color for buttons and focus states.', 'wpnlweb'); ?>
									</p>

									<div class="wpnlweb-color-picker-wrapper">
										<div class="wpnlweb-color-input-group">
											<input type="color"
												name="wpnlweb_primary_color"
												id="wpnlweb_primary_color"
												value="<?php echo esc_attr($primary_color); ?>"
												class="wpnlweb-color-picker" />
											<input type="text"
												id="wpnlweb_primary_color_text"
												value="<?php echo esc_attr($primary_color); ?>"
												placeholder="#3b82f6"
												class="wpnlweb-color-text" />
										</div>
									</div>

									<div class="wpnlweb-preset-colors">
										<label class="wpnlweb-preset-label"><?php _e('Preset Colors', 'wpnlweb'); ?></label>
										<div class="wpnlweb-preset-grid">
											<?php foreach ($preset_colors as $color): ?>
												<button type="button"
													class="wpnlweb-preset-color <?php echo ($color === $primary_color) ? 'active' : ''; ?>"
													data-color="<?php echo esc_attr($color); ?>"
													style="background-color: <?php echo esc_attr($color); ?>"
													title="<?php echo esc_attr($color); ?>">
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>

							<!-- Custom CSS Tab -->
							<div id="custom-css-tab" class="wpnlweb-tab-content">
								<div class="wpnlweb-tab-header">
									<h2>
										<span class="wpnlweb-tab-icon">📝</span>
										<?php _e('Custom CSS', 'wpnlweb'); ?>
									</h2>
									<p><?php _e('Add custom CSS to further customize the appearance. This CSS will be applied to all WPNLWeb shortcodes.', 'wpnlweb'); ?></p>
								</div>

								<div class="wpnlweb-setting-group">
									<div class="wpnlweb-css-editor-header">
										<label class="wpnlweb-setting-label">
											<?php _e('Custom CSS', 'wpnlweb'); ?>
										</label>
										<div class="wpnlweb-css-actions">
											<button type="button" class="wpnlweb-button-secondary" id="wpnlweb-copy-example">
												📋 <?php _e('Copy Example', 'wpnlweb'); ?>
											</button>
											<button type="button" class="wpnlweb-button-secondary" id="wpnlweb-reset-css">
												🔄 <?php _e('Reset', 'wpnlweb'); ?>
											</button>
										</div>
									</div>

									<textarea name="wpnlweb_custom_css"
										id="wpnlweb_custom_css"
										rows="12"
										class="wpnlweb-css-editor"
										placeholder="<?php esc_attr_e('Add your custom CSS here...', 'wpnlweb'); ?>"><?php echo esc_textarea($custom_css); ?></textarea>

									<div class="wpnlweb-css-example">
										<p><?php _e('Add custom CSS to override default styles. Example:', 'wpnlweb'); ?></p>
										<pre class="wpnlweb-code-block"><code>.wpnlweb-search-container { border-radius: 20px; }
.wpnlweb-search-button { background: var(--wpnlweb-primary-color); }</code></pre>
									</div>

									<div class="wpnlweb-css-reference">
										<h3><?php _e('CSS Custom Properties Reference', 'wpnlweb'); ?></h3>
										<p><?php _e('You can use these CSS custom properties in your custom CSS to maintain consistency:', 'wpnlweb'); ?></p>

										<div class="wpnlweb-css-properties">
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-primary-color</code>
												<span class="wpnlweb-css-desc"><?php _e('Main brand color', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-primary-hover</code>
												<span class="wpnlweb-css-desc"><?php _e('Hover state color', 'wpnlweb'); ?></span>
											</div>
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-bg-primary</code>
												<span class="wpnlweb-css-desc"><?php _e('Main background color', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-bg-secondary</code>
												<span class="wpnlweb-css-desc"><?php _e('Secondary background', 'wpnlweb'); ?></span>
											</div>
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-text-primary</code>
												<span class="wpnlweb-css-desc"><?php _e('Main text color', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-text-secondary</code>
												<span class="wpnlweb-css-desc"><?php _e('Secondary text color', 'wpnlweb'); ?></span>
											</div>
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-border-radius</code>
												<span class="wpnlweb-css-desc"><?php _e('Border radius', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-spacing-sm</code>
												<span class="wpnlweb-css-desc"><?php _e('Small spacing (12px)', 'wpnlweb'); ?></span>
											</div>
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-spacing-md</code>
												<span class="wpnlweb-css-desc"><?php _e('Medium spacing (20px)', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-spacing-lg</code>
												<span class="wpnlweb-css-desc"><?php _e('Large spacing (30px)', 'wpnlweb'); ?></span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- Live Preview Tab -->
							<div id="live-preview-tab" class="wpnlweb-tab-content">
								<div class="wpnlweb-tab-header">
									<h2>
										<span class="wpnlweb-tab-icon">👁️</span>
										<?php _e('Live Preview', 'wpnlweb'); ?>
									</h2>
									<p><?php _e('Use the shortcode [wpnlweb] to see your customizations in action.', 'wpnlweb'); ?></p>
								</div>

								<div class="wpnlweb-setting-group">
									<label class="wpnlweb-setting-label">
										<?php _e('Shortcode Usage', 'wpnlweb'); ?>
									</label>

									<div class="wpnlweb-shortcode-display">
										<span class="wpnlweb-shortcode-label"><?php _e('Shortcode:', 'wpnlweb'); ?></span>
										<code class="wpnlweb-shortcode-code">[wpnlweb]</code>
									</div>

									<div class="wpnlweb-preview-section">
										<h3><?php _e('Preview', 'wpnlweb'); ?></h3>
										<div class="wpnlweb-preview-container">
											<div class="wpnlweb-preview-header"><?php _e('Live Preview', 'wpnlweb'); ?></div>
											<div id="wpnlweb-live-preview" class="wpnlweb-live-preview">
												<!-- Live preview will be loaded here via AJAX -->
												<div class="wpnlweb-preview-placeholder">
													<div class="wpnlweb-preview-search">
														<input type="text" placeholder="<?php esc_attr_e('Search...', 'wpnlweb'); ?>" class="wpnlweb-preview-input">
														<button type="button" class="wpnlweb-preview-button">🔍</button>
													</div>
													<p class="wpnlweb-preview-text"><?php _e('Search results would appear here...', 'wpnlweb'); ?></p>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- Save Button -->
							<div class="wpnlweb-form-actions">
								<button type="submit" class="wpnlweb-button-primary">
									💾 <?php _e('Save Settings', 'wpnlweb'); ?>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
<?php
	}

	/**
	 * Handle AJAX preview shortcode request
	 *
	 * @since    1.0.0
	 */
	public function handle_preview_shortcode()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'wpnlweb_admin_nonce')) {
			wp_die(__('Security check failed', 'wpnlweb'));
		}

		// Get settings from request
		$theme_mode = sanitize_text_field($_POST['theme_mode'] ?? get_option('wpnlweb_theme_mode', 'auto'));
		$primary_color = sanitize_text_field($_POST['primary_color'] ?? get_option('wpnlweb_primary_color', '#3b82f6'));
		$custom_css = wp_strip_all_tags($_POST['custom_css'] ?? get_option('wpnlweb_custom_css', ''));

		// Temporarily set options for preview
		$original_theme = get_option('wpnlweb_theme_mode');
		$original_color = get_option('wpnlweb_primary_color');
		$original_css = get_option('wpnlweb_custom_css');

		update_option('wpnlweb_theme_mode', $theme_mode);
		update_option('wpnlweb_primary_color', $primary_color);
		update_option('wpnlweb_custom_css', $custom_css);

		// Generate shortcode output
		$shortcode_output = do_shortcode('[wpnlweb placeholder="Search..." button_text="Search"]');

		// Restore original options
		update_option('wpnlweb_theme_mode', $original_theme);
		update_option('wpnlweb_primary_color', $original_color);
		update_option('wpnlweb_custom_css', $original_css);

		wp_send_json_success(array('html' => $shortcode_output));
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
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpnlweb-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpnlweb-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 * Add settings link to plugins page
	 *
	 * @since    1.0.0
	 * @param    array    $links    Array of existing plugin action links
	 * @return   array    Modified array of plugin action links
	 */
	public function add_settings_link($links)
	{
		$settings_link = '<a href="' . admin_url('admin.php?page=wpnlweb-settings') . '">' . __('Settings', 'wpnlweb') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
}
